<?php

/**
 * @author Nicolas CARPi from Deltablot
 * @copyright 2025 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */

declare(strict_types=1);

namespace Elabftw\Commands;

use Elabftw\Services\HttpGetter;
use Elabftw\Elabftw\Env;
use Elabftw\Models\Config;
use Elabftw\Models\Fingerprints;
use Elabftw\Services\Fingerprinter;
use GuzzleHttp\Client;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Override;

/**
 * (Re)calculate fingerprints for stored compounds
 */
#[AsCommand(name: 'compounds:fingerprint')]
final class FingerprintCompounds extends Command
{
    #[Override]
    protected function configure(): void
    {
        $this->setDescription('Calculate fingerprint of compounds missing one.')
            ->setHelp('Calculate fingerprints of compounds in the database. Requires fingerprinting service obviously.')
            ->addOption('dry-run', 'd', InputOption::VALUE_NONE, 'Do not change anything in the database, just report number of compounds targeted.')
            ->addOption('force', 'f', InputOption::VALUE_NONE, 'Recompute ALL compounds fingerprints, not just the ones missing.');
    }

    #[Override]
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $Fingerprints = new Fingerprints();
        $compounds = $Fingerprints->getSmilesMissingFp((bool) $input->getOption('force'));
        $output->writeln(sprintf('Found %d compounds to process...', count($compounds)));
        if ($input->getOption('dry-run')) {
            $output->writeln('Dry run mode: not processing anything.');
            return Command::SUCCESS;
        }
        $proxy = Env::asBool('FINGERPRINTER_USE_PROXY') ? Config::getConfig()->configArr['proxy'] : '';
        $fingerPrinterHttpGetter = new HttpGetter(new Client(), $proxy, Env::asBool('DEV_MODE'));
        $Fingerprinter = new Fingerprinter($fingerPrinterHttpGetter, Env::asUrl('FINGERPRINTER_URL'));
        foreach ($compounds as $compound) {
            $output->writeln(sprintf('Processing compound with ID: %d', $compound['id']));
            $fp = $Fingerprinter->calculate('smi', $compound['smiles']);
            new Fingerprints($compound['id'])->upsert($fp['data']);
        }

        return Command::SUCCESS;
    }
}
