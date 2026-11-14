<?php

/**
 * @copyright 2026 Nicolas CARPi
 * @license AGPL-3.0
 * @package elabftw
 */

declare(strict_types=1);

namespace Elabftw\Commands;

use Elabftw\Models\ReactionComponentFingerprints;
use Elabftw\Services\OpenBabelFingerprinter;
use Elabftw\Services\ReactionFingerprinter;
use Elabftw\Services\ReactionIdentity;
use Override;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

use function count;
use function sprintf;

/**
 * Fingerprints are computed when a reaction is saved, but a structure is saved anyway
 * if the fingerprinter fails, so this command can index the missing ones afterwards.
 */
#[AsCommand(name: 'reactions:fingerprint')]
final class FingerprintReactions extends Command
{
    #[Override]
    protected function configure(): void
    {
        $this->setDescription('Calculate the fingerprints of reaction components missing one.')
            ->setHelp('Reaction components need a fingerprint to be found by a substructure search.')
            ->addOption('dry-run', 'd', InputOption::VALUE_NONE, 'Do not change anything in the database, just report the number of components targeted.')
            ->addOption('force', 'f', InputOption::VALUE_NONE, 'Recompute the fingerprints of ALL reaction components, not just the missing ones.');
    }

    #[Override]
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $Fingerprints = new ReactionComponentFingerprints();
        $components = $Fingerprints->getComponents((bool) $input->getOption('force'));
        $output->writeln(sprintf('Found %d reaction components to process...', count($components)));
        if (count($components) === 0 || $input->getOption('dry-run')) {
            return Command::SUCCESS;
        }
        $Fingerprinter = new ReactionFingerprinter(new OpenBabelFingerprinter());
        $failed = 0;
        foreach ($components as $component) {
            $fingerprint = $Fingerprinter->fingerprint(ReactionIdentity::normalizeSmiles((string) $component['smiles']));
            if ($fingerprint === null) {
                $output->writeln(sprintf('Could not fingerprint the reaction component with ID: %d', $component['id']), OutputInterface::VERBOSITY_VERBOSE);
                $failed++;
                continue;
            }
            $Fingerprints->upsert((int) $component['id'], $fingerprint);
        }
        $output->writeln(sprintf('Processed %d reaction components, %d could not be fingerprinted.', count($components), $failed));
        return Command::SUCCESS;
    }
}
