<?php

/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @copyright 2012 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */

declare(strict_types=1);

namespace Elabftw\Commands;

use Elabftw\Elabftw\Db;
use Elabftw\Enums\Action;
use Elabftw\Enums\EntityType;
use Elabftw\Exceptions\IllegalActionException;
use Elabftw\Exceptions\ImproperActionException;
use Elabftw\Models\ExperimentsLinks;
use Elabftw\Models\ItemsLinks;
use Elabftw\Models\Users;
use Elabftw\Services\TeamsHelper;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

use function preg_match_all;
use function printf;
use function str_replace;

/**
 * Find links to items and experiments in entity bodies and add to 'linked items'
 * Templates and ItemsTypes can only have links to items
 * See #1470 https://github.com/elabftw/elabftw/issues/1470#issuecomment-527098716
 */
#[AsCommand(name: 'links:sync')]
class AddMissingLinks extends Command
{
    protected function configure(): void
    {
        $this->setDescription('Make sure links in body are also properly added as "Linked items" and "Linked experiments".')
            ->setHelp('Find links to items and experiments in the body of entities and add them to the "Linked items" and "Linked experiments" of that entity. Templates and ItemsTypes can only have links to items.');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $Db = Db::getConnection();

        $queryItemsOrExperiment = "SELECT DISTINCT `id`, `body`, `userid`
            FROM `table`
            WHERE `body` LIKE '%database.php?mode=view&amp;id=%'
              OR `body` LIKE '%database.php?mode=edit&amp;id=%'
              OR `body` LIKE '%experiments.php?mode=view&amp;id=%'
              OR `body` LIKE '%experiments.php?mode=edit&amp;id=%';";

        $queryTemplates = "SELECT `id`, `body`, `userid`
            FROM `table`
            WHERE `body` LIKE '%database.php?mode=view&amp;id=%'
              OR `body` LIKE '%database.php?mode=edit&amp;id=%';";

        $queryItemsTypes = "SELECT `id`, `body`, `team`
            FROM `table`
            WHERE `body` LIKE '%database.php?mode=view&amp;id=%'
              OR `body` LIKE '%database.php?mode=edit&amp;id=%';";

        $tables = array(
            'experiments' => $queryItemsOrExperiment,
            'items' => $queryItemsOrExperiment,
            'experiments_templates' => $queryTemplates,
            'items_types' => $queryItemsTypes,
        );

        $patternItemsOrExperiment = '/(?<target>database|experiments)\.php\?mode=(?:view|edit)&amp;id=(?<id>[0-9]+)/';
        $patternTemplates = '/(?<target>database)\.php\?mode=(?:view|edit)&amp;id=(?<id>[0-9]+)/';
        $patterns = array(
            'experiments' => $patternItemsOrExperiment,
            'items' => $patternItemsOrExperiment,
            'experiments_templates' => $patternTemplates,
            'items_types' => $patternTemplates,
        );

        foreach ($tables as $table => $query) {
            printf('Searching in %s%s', $table, PHP_EOL);
            $sql = str_replace('table', $table, $query);
            $req = $Db->prepare($sql);
            $req->execute();
            $res = $req->fetchAll();

            if (!empty($res)) {
                $count = count($res);
                printf('Found %d entr%s with id%s:%s', $count, $count === 1 ? 'y' : 'ies', $count === 1 ? '' : 's', PHP_EOL);
                $count = 0;
                foreach ($res as $data) {
                    printf('  - %d', $data['id']);

                    // ItemsTypes entries have no user only a team but we need an admin to create a link
                    // -> get an admin from that team
                    if ($table === 'items_types') {
                        $adminOfTeam = (new TeamsHelper($data['team']))->getAllAdminsUserid()[0];
                        $User = new Users($adminOfTeam, $data['team']);
                    } else {
                        $User = new Users($data['userid']);
                    }

                    // don't set entity id yet, user has userData['team'] == 0 at this point
                    // this will result in permission issues during setId -> readOne -> canOrExplode('read') -> hasCommonTeamWithCurrent()
                    $entity = EntityType::from($table)->toInstance($User);

                    // make sure we can access all entries with write access
                    $entity->bypassWritePermission = true;
                    $entity->setId($data['id']);

                    $itemsLinks = new ItemsLinks($entity);
                    $experimentsLinks = new ExperimentsLinks($entity);

                    $countSmall = 0;
                    // look for links to items and experiments in the body and create links
                    // for Templates and ItemsTypes links to experiments will not be added
                    preg_match_all($patterns[$table], $data['body'], $matches, PREG_SET_ORDER);
                    foreach ($matches as $match) {
                        try {
                            $links = $itemsLinks;
                            if (($table === 'experiments' || $table === 'items') && $match['target'] === 'experiments') {
                                $links = $experimentsLinks;
                            }

                            $links->setId((int) $match['id']);
                            if ($links->postAction(Action::Create, array())) {
                                $countSmall++;
                                $count++;
                            }
                        } catch (IllegalActionException | ImproperActionException $e) {
                            // maybe the db item or experiment doesn't exist anymore or we no longer have access to it
                            // so just skip that one
                            continue;
                        }
                    }
                    printf('; (re-)added %d link%s%s', $countSmall, $countSmall === 1 ? '' : 's', PHP_EOL);
                }
                printf('total links: %d %2$s%2$s', $count, PHP_EOL);
            }
        }
        return 0;
    }
}
