<?php

namespace App\Service;

use Symfony\Contracts\HttpClient\HttpClientInterface;

class VisaApiService
{
    private HttpClientInterface $httpClient;

    public function __construct(HttpClientInterface $httpClient)
    {
        $this->httpClient = $httpClient;
    }

    public function getCalculator(int $amount, string $fromCurr = "EUR", string $toCurr = "USD", int $fee = 0): array
    {
        $date = new \DateTimeImmutable('-1 day');
        $dateFormatted = $date->format('m/d/Y');

        $response = $this->httpClient->request('GET', 'https://www.visa.fr/cmsapi/fx/rates', [
            'headers' => [
                'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/125.0.0.0 Safari/537.36',
                'Referer' => 'https://www.visa.fr/',
                'Accept' => 'application/json',
                'Accept-Language' => 'fr-FR,fr;q=0.9',
                'Origin' => 'https://www.visa.fr',
            ],
            'query' => [
                'amount' => $amount,
                'fee' => $fee,
                'utcConvertedDate' => $dateFormatted,
                'exchangedate' => $dateFormatted,
                'fromCurr' => $fromCurr,
                'toCurr' => $toCurr
            ]
        ]);

        return $response->toArray();
    }
}
