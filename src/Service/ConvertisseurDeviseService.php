<?php

namespace App\Service;

use Symfony\Contracts\HttpClient\HttpClientInterface;
use Psr\Log\LoggerInterface;

class ConvertisseurDeviseService
{
    private HttpClientInterface $httpClient;
    private LoggerInterface $logger;

    public function __construct(HttpClientInterface $httpClient, LoggerInterface $logger)
    {
        $this->httpClient = $httpClient;
        $this->logger = $logger;
    }

    /**
     * Récupère les données de conversion depuis l'API Visa
     */
    public function convertirAvecVisa(
        float $montant,
        string $deviseSource,
        string $deviseCible,
        \DateTimeInterface $dateTransaction,
        float $frais = 0
    ): array {
        try {
            // Vérifier que les devises sont supportées
            if (!$this->isDeviseVisaSupported($deviseSource) || !$this->isDeviseVisaSupported($deviseCible)) {
                throw new \Exception(sprintf(
                    'Devise non supportée par l\'API Visa. Devises supportées: %s',
                    implode(', ', array_keys($this->getDevisesVisaSupported()))
                ));
            }

            // Vérifier que la date n'est pas dans le futur
            $aujourd_hui = new \DateTime();
            if ($dateTransaction > $aujourd_hui) {
                $dateTransaction = $aujourd_hui;
                $this->logger->warning('Date future détectée, utilisation de la date actuelle');
            }

            // Vérifier que la date n'est pas trop ancienne (max 1 an)
            $dateMin = (clone $aujourd_hui)->modify('-1 year');
            if ($dateTransaction < $dateMin) {
                $dateTransaction = $dateMin;
                $this->logger->warning('Date trop ancienne, utilisation d\'une date dans la limite');
            }

            // Formater la date pour l'API Visa (MM/dd/yyyy)
            $dateFormatee = $dateTransaction->format('m/d/Y');
            
            // Construire les paramètres
            $params = [
                'amount' => number_format($montant, 2, '.', ''),
                'fee' => number_format($frais, 2, '.', ''),
                'utcConvertedDate' => $dateFormatee,
                'exchangedate' => $dateFormatee,
                'fromCurr' => strtoupper($deviseSource),
                'toCurr' => strtoupper($deviseCible)
            ];

            // Construire l'URL
            $baseUrl = 'https://www.visa.fr/cmsapi/fx/rates';
            $queryString = http_build_query($params);
            $url = $baseUrl . '?' . $queryString;

            $this->logger->info('Requête API Visa', [
                'url' => $url,
                'params' => $params
            ]);

            // Headers pour imiter un navigateur
            $headers = [
                'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',
                'Accept' => 'application/json, text/plain, */*',
                'Accept-Language' => 'fr-FR,fr;q=0.9,en;q=0.8',
                'Accept-Encoding' => 'gzip, deflate, br',
                'Referer' => 'https://www.visa.fr/support/consumer/travel-support/exchange-rate-calculator.html',
                'Origin' => 'https://www.visa.fr',
                'DNT' => '1',
                'Connection' => 'keep-alive',
                'Sec-Fetch-Dest' => 'empty',
                'Sec-Fetch-Mode' => 'cors',
                'Sec-Fetch-Site' => 'same-origin',
                'Cache-Control' => 'no-cache',
                'Pragma' => 'no-cache'
            ];

            // Effectuer la requête
            $response = $this->httpClient->request('GET', $url, [
                'headers' => $headers,
                'timeout' => 30,
                'verify_peer' => false,
                'verify_host' => false
            ]);

            $statusCode = $response->getStatusCode();
            $content = $response->getContent(false);

            $this->logger->info('Réponse API Visa', [
                'status_code' => $statusCode,
                'content' => substr($content, 0, 500)
            ]);

            if ($statusCode !== 200) {
                throw new \Exception(sprintf(
                    'Erreur API Visa: %d - %s', 
                    $statusCode, 
                    $content ?: 'Pas de contenu'
                ));
            }

            $data = $response->toArray();

            // Analyser la réponse Visa
            if (isset($data['originalValues'])) {
                // Format de réponse Visa standard
                $montantConverti = (float) $data['originalValues']['toAmountWithVisaRate'];
                $tauxChange = $montantConverti / $montant;
                $dateConversion = isset($data['originalValues']['asOfDate']) 
                    ? date('m/d/Y', $data['originalValues']['asOfDate'])
                    : $dateFormatee;

                return [
                    'success' => true,
                    'montant_original' => $montant,
                    'devise_source' => strtoupper($deviseSource),
                    'devise_cible' => strtoupper($deviseCible),
                    'taux_change' => $tauxChange,
                    'montant_converti' => $montantConverti,
                    'frais' => $frais,
                    'date_conversion' => $dateConversion,
                    'date_transaction' => $dateFormatee,
                    'donnees_brutes' => $data,
                    'url_api' => $url
                ];
            } else {
                throw new \Exception('Format de réponse API Visa inattendu: ' . json_encode($data));
            }

        } catch (\Exception $e) {
            $this->logger->error('Erreur API Visa', [
                'message' => $e->getMessage(),
                'url' => $url ?? null,
                'params' => $params ?? null
            ]);

            // Fallback avec des taux fictifs
            return $this->getFallbackConversion($montant, $deviseSource, $deviseCible, $dateTransaction, $frais, $e->getMessage());
        }
    }

    /**
     * Vérifie si une devise est supportée par l'API Visa
     */
    private function isDeviseVisaSupported(string $devise): bool
    {
        return array_key_exists(strtoupper($devise), $this->getDevisesVisaSupported());
    }

    /**
     * Retourne les devises supportées par l'API Visa
     */
    private function getDevisesVisaSupported(): array
    {
        return [
            'USD' => 'Dollar américain',
            'EUR' => 'Euro',
            'GBP' => 'Livre sterling',
            'JPY' => 'Yen japonais',
            'CAD' => 'Dollar canadien',
            'AUD' => 'Dollar australien',
            'CHF' => 'Franc suisse',
            'CNY' => 'Yuan chinois',
            'HKD' => 'Dollar de Hong Kong',
            'SGD' => 'Dollar de Singapour',
            'SEK' => 'Couronne suédoise',
            'NOK' => 'Couronne norvégienne',
            'DKK' => 'Couronne danoise',
            'PLN' => 'Zloty polonais',
            'CZK' => 'Couronne tchèque',
            'HUF' => 'Forint hongrois',
            'RUB' => 'Rouble russe',
            'TRY' => 'Livre turque',
            'BRL' => 'Real brésilien',
            'MXN' => 'Peso mexicain',
            'ZAR' => 'Rand sud-africain',
            'INR' => 'Roupie indienne',
            'KRW' => 'Won sud-coréen',
            'THB' => 'Baht thaïlandais',
            'MYR' => 'Ringgit malaisien',
            'IDR' => 'Roupie indonésienne',
            'PHP' => 'Peso philippin',
            'VND' => 'Dong vietnamien',
            'NZD' => 'Dollar néo-zélandais'
        ];
    }

    /**
     * Méthode de fallback avec des taux fictifs
     */
    private function getFallbackConversion(
        float $montant,
        string $deviseSource,
        string $deviseCible,
        \DateTimeInterface $dateTransaction,
        float $frais,
        string $erreur
    ): array {
        // Taux fictifs réalistes
        $tauxFictifs = [
            'EUR' => [
                'USD' => 1.08,
                'GBP' => 0.86,
                'JPY' => 160.00,
                'CAD' => 1.47,
                'AUD' => 1.62,
                'CHF' => 0.93,
                'CNY' => 7.85,
                'MGA' => 4850.00, // Devise non Visa
                'XOF' => 655.96,  // Franc CFA
                'MAD' => 10.85    // Dirham marocain
            ],
            'USD' => [
                'EUR' => 0.93,
                'GBP' => 0.80,
                'JPY' => 148.50,
                'MGA' => 4500.00
            ],
            'GBP' => [
                'EUR' => 1.16,
                'USD' => 1.25,
                'JPY' => 185.60
            ]
        ];

        $taux = $tauxFictifs[$deviseSource][$deviseCible] ?? 1.0;
        $montantConverti = $montant * $taux;
        
        // Appliquer les frais
        if ($frais > 0) {
            $montantConverti = $montantConverti * (1 - ($frais / 100));
        }

        return [
            'success' => false,
            'fallback' => true,
            'error' => $erreur,
            'montant_original' => $montant,
            'devise_source' => strtoupper($deviseSource),
            'devise_cible' => strtoupper($deviseCible),
            'taux_change' => $taux,
            'montant_converti' => $montantConverti,
            'frais' => $frais,
            'date_conversion' => $dateTransaction->format('m/d/Y'),
            'date_transaction' => $dateTransaction->format('m/d/Y'),
            'donnees_brutes' => ['fallback' => true, 'taux_utilise' => $taux],
            'url_api' => 'Fallback - ' . $erreur
        ];
    }

    /**
     * Test direct de l'API Visa avec des paramètres valides
     */
    public function testerApiVisa(): array
    {
        try {
            // Test avec des paramètres valides (date passée, devises supportées)
            $hier = (new \DateTime())->modify('-1 day');
            $dateFormatee = $hier->format('m/d/Y');
            
            $url = sprintf(
                'https://www.visa.fr/cmsapi/fx/rates?amount=100&fee=0&utcConvertedDate=%s&exchangedate=%s&fromCurr=EUR&toCurr=USD',
                urlencode($dateFormatee),
                urlencode($dateFormatee)
            );
            
            $response = $this->httpClient->request('GET', $url, [
                'headers' => [
                    'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36',
                    'Accept' => 'application/json',
                    'Referer' => 'https://www.visa.fr/'
                ],
                'timeout' => 10
            ]);

            return [
                'success' => true,
                'status' => $response->getStatusCode(),
                'content' => $response->getContent(false),
                'url' => $url
            ];

        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage(),
                'url' => $url ?? null
            ];
        }
    }

    /**
     * Récupère la liste des devises supportées (Visa + autres)
     */
    public function getDevisesSupportees(): array
    {
        $devisesVisa = $this->getDevisesVisaSupported();
        
        // Ajouter des devises non-Visa avec avertissement
        $autresDevises = [
            'MGA' => 'Ariary malgache (MGA) - Taux fictif',
            'XOF' => 'Franc CFA (XOF) - Taux fictif',
            'MAD' => 'Dirham marocain (MAD) - Taux fictif',
            'TND' => 'Dinar tunisien (TND) - Taux fictif'
        ];

        return array_merge($devisesVisa, $autresDevises);
    }

    /**
     * Formate un montant avec le symbole de la devise
     */
    public function formaterMontant(float $montant, string $devise): string
    {
        $symboles = [
            'EUR' => '€',
            'USD' => '$',
            'GBP' => '£',
            'JPY' => '¥',
            'CHF' => 'CHF',
            'CAD' => 'C$',
            'AUD' => 'A$',
            'MGA' => 'Ar',
            'CNY' => '¥',
            'INR' => '₹',
            'XOF' => 'CFA',
            'MAD' => 'DH'
        ];

        $symbole = $symboles[$devise] ?? $devise;
        
        if (in_array($devise, ['JPY', 'KRW', 'MGA', 'XOF', 'IDR', 'VND'])) {
            return number_format($montant, 0, ',', ' ') . ' ' . $symbole;
        }
        
        return number_format($montant, 2, ',', ' ') . ' ' . $symbole;
    }

    /**
     * Vérifie si une devise utilise l'API Visa ou un taux fictif
     */
    public function isDeviseVisa(string $devise): bool
    {
        return array_key_exists(strtoupper($devise), $this->getDevisesVisaSupported());
    }

    /**
     * Obtient des informations sur une devise
     */
    public function getInfoDevise(string $devise): array
    {
        $devise = strtoupper($devise);
        $devisesVisa = $this->getDevisesVisaSupported();
        
        return [
            'code' => $devise,
            'nom' => $devisesVisa[$devise] ?? 'Devise non reconnue',
            'visa_supported' => $this->isDeviseVisa($devise),
            'symbole' => $this->getSymboleDevise($devise)
        ];
    }

    /**
     * Obtient le symbole d'une devise
     */
    private function getSymboleDevise(string $devise): string
    {
        $symboles = [
            'EUR' => '€',
            'USD' => '$',
            'GBP' => '£',
            'JPY' => '¥',
            'CHF' => 'CHF',
            'CAD' => 'C$',
            'AUD' => 'A$',
            'MGA' => 'Ar',
            'CNY' => '¥',
            'INR' => '₹',
            'XOF' => 'CFA',
            'MAD' => 'DH',
            'TND' => 'DT'
        ];

        return $symboles[$devise] ?? $devise;
    }
}
