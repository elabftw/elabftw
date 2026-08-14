<?php

/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @copyright 2022 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */

declare(strict_types=1);

namespace Elabftw\Commands;

use Elabftw\Enums\EntityType;
use Elabftw\Services\EntityPruner;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Override;
use InvalidArgumentException;

use function sprintf;
use function array_map;
use function explode;

/**
 * To remove deleted files completely
 */
#[AsCommand(name: 'prune:experiments')]
final class PruneExperiments extends Command
{
    #[Override]
    protected function configure(): void
    {
        $this->setDescription('Remove deleted experiments definitively')
            ->setHelp('Remove experiments marked as deleted from the database')
            ->addOption('id', null, InputOption::VALUE_REQUIRED | InputOption::VALUE_IS_ARRAY, 'Only prune experiments with these IDs')
            ->addOption('user', null, InputOption::VALUE_REQUIRED | InputOption::VALUE_IS_ARRAY, 'Only prune experiments belonging to these users')
            ->addOption('team', null, InputOption::VALUE_REQUIRED | InputOption::VALUE_IS_ARRAY, 'Only prune experiments belonging to these teams')
            ->addOption('since', null, InputOption::VALUE_REQUIRED, 'Only prune experiments created since this date')
            ->addOption('only', null, InputOption::VALUE_REQUIRED, 'Only prune specific entity types (e.g., experiments, experiments_templates)');
    }

    #[Override]
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $output->writeln(array(
            'Pruning experiments',
            '===================',
        ));
        $ids = array_map('intval', $input->getOption('id'));
        $userids = array_map('intval', $input->getOption('user'));
        $teams = array_map('intval', $input->getOption('team'));
        $since = $input->getOption('since') !== null ? (string) $input->getOption('since') : null;

        $types = array(EntityType::Experiments);
        $only = $input->getOption('only');
        if ($only !== null) {
            $types = array();
            $onlyArr = array_map('trim', explode(',', (string) $only));
            foreach ($onlyArr as $typeStr) {
                if ($typeStr === EntityType::Experiments->value) {
                    $types[] = EntityType::Experiments;
                } elseif ($typeStr === EntityType::Templates->value) {
                    $types[] = EntityType::Templates;
                } else {
                    throw new InvalidArgumentException(sprintf('Invalid entity type "%s" for this command. Allowed: experiments, experiments_templates.', $typeStr));
                }
            }
        }

        $cleanedNumber = 0;
        foreach ($types as $type) {
            $Cleaner = new EntityPruner($type, $ids, $userids, $teams, $since);
            $cleanedNumber += $Cleaner->cleanup();
        }
        $output->writeln(sprintf('Removed %d experiments', $cleanedNumber));
        return Command::SUCCESS;
    }
}
