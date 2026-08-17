<?php

/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @copyright 2026 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */

declare(strict_types=1);

namespace Elabftw\Commands;

use Elabftw\Enums\EntityType;
use Elabftw\Services\Check;
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
 * To remove deleted entries completely
 */
#[AsCommand(name: 'prune:entries')]
final class PruneEntries extends Command
{
    #[Override]
    protected function configure(): void
    {
        $this->setDescription('Remove deleted entries definitively')
            ->setHelp('Remove deleted entries from the database')
            ->addOption('id', 'i', InputOption::VALUE_REQUIRED | InputOption::VALUE_IS_ARRAY, 'Only prune entries with these IDs')
            ->addOption('user', 'u', InputOption::VALUE_REQUIRED | InputOption::VALUE_IS_ARRAY, 'Only prune entries belonging to these users')
            ->addOption('team', 't', InputOption::VALUE_REQUIRED | InputOption::VALUE_IS_ARRAY, 'Only prune entries belonging to these teams')
            ->addOption('since', 's', InputOption::VALUE_REQUIRED, 'Only prune entries created since this date')
            ->addOption('only', 'o', InputOption::VALUE_REQUIRED, 'Only prune specific entity types');
    }

    #[Override]
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $output->writeln(array(
            'Pruning entries',
            '===============',
        ));
        $ids = array_map(static fn($id) => Check::id((int) $id), $input->getOption('id'));
        $userids = array_map(static fn($userid) => Check::id((int) $userid), $input->getOption('user'));
        $teams = array_map(static fn($team) => Check::id((int) $team), $input->getOption('team'));
        $since = $input->getOption('since') !== null ? (string) $input->getOption('since') : null;

        $types = EntityType::cases();
        $only = $input->getOption('only');
        if ($only !== null) {
            $types = array();
            $onlyArr = array_map('trim', explode(',', (string) $only));
            foreach ($onlyArr as $typeStr) {
                foreach (EntityType::cases() as $type) {
                    if ($type->value === $typeStr) {
                        $types[] = $type;
                        continue 2;
                    }
                }
                throw new InvalidArgumentException(sprintf('Invalid entity type "%s".', $typeStr));
            }
        }

        $cleanedNumber = 0;
        foreach ($types as $type) {
            $Cleaner = new EntityPruner($type, $ids, $userids, $teams, $since);
            $cleanedNumber += $Cleaner->cleanup();
        }
        $output->writeln(sprintf('Removed %d entries', $cleanedNumber));
        return Command::SUCCESS;
    }
}
