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

use Elabftw\Elabftw\Db;
use Elabftw\Enums\Action;
use Elabftw\Models\TeamTags;
use Elabftw\Models\UltraAdmin;
use PDO;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Override;

/**
 * Synchronize tags between teams
 */
#[AsCommand(name: 'tags:teamssync')]
final class TagsTeamsSync extends Command
{
    #[Override]
    protected function configure(): void
    {
        $this->setDescription('Synchronize tags between teams')
            ->addArgument('teams', InputArgument::IS_ARRAY | InputArgument::REQUIRED, 'List of teams (ids)')
            ->setHelp('Synchronize tags between teams.');
    }

    #[Override]
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $teams = $input->getArgument('teams');
        $allTags = $this->getTags($teams);
        $TeamTags = new TeamTags(new UltraAdmin());
        $inserted = 0;
        foreach ($teams as $team) {
            $teamId = (int) $team;
            $TeamTags->Users->userData['team'] = $teamId;
            $TeamTags->setId($teamId);
            foreach ($allTags as $tag) {
                if ($TeamTags->postAction(Action::Create, array('tag' => $tag)) > 0) {
                    $inserted += 1;
                }
            }
        }
        // only be verbose if we did something
        if ($inserted > 0) {
            $output->writeln(sprintf('Inserted %d tags.', $inserted));
        }
        return Command::SUCCESS;
    }

    private function getTags(array $teams): array
    {
        $Db = Db::getConnection();
        $sql = 'SELECT DISTINCT tag FROM tags WHERE team IN ( ' . implode(',', $teams) . ' )';
        $req = $Db->prepare($sql);
        $req->execute();
        return $req->fetchAll(PDO::FETCH_COLUMN);
    }
}
