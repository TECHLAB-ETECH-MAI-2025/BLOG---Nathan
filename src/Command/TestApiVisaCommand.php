<?php

namespace App\Command;

use App\Service\VisaApiService;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:test-visa-api',
    description: 'Test simple de l\'API Visa'
)]
class TestApiVisaCommand extends Command
{
    private VisaApiService $visaApiService;

    public function __construct(VisaApiService $visaApiService)
    {
        $this->visaApiService = $visaApiService;
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $io->title('Test API Visa');

        try {
            $result = $this->visaApiService->getCalculator(100, 'EUR', 'USD', 0);
            
            $io->success('API Visa fonctionne !');
            $io->text('Résultat:');
            $io->text(json_encode($result, JSON_PRETTY_PRINT));

        } catch (\Exception $e) {
            $io->error('Erreur: ' . $e->getMessage());
            return Command::FAILURE;
        }

        return Command::SUCCESS;
    }
}
