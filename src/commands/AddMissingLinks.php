<?php declare(strict_types=1);
/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @copyright 2012 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */

namespace Elabftw\Commands;

use Elabftw\Elabftw\Db;
use Elabftw\Enums\Action;
use Elabftw\Exceptions\IllegalActionException;
use Elabftw\Exceptions\ImproperActionException;
use Elabftw\Factories\EntityFactory;
use Elabftw\Models\Links;
use Elabftw\Models\Users;
use Elabftw\Services\TeamsHelper;
use function preg_match_all;
use function printf;
use function str_replace;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Find links to items and experiments in entity bodies and add to 'linked items'
 * Templates and ItemsTypes can only have links to items
 * See #1470 https://github.com/elabftw/elabftw/issues/1470#issuecomment-527098716
 */
class AddMissingLinks extends Command
{
    // the name of the command (the part after "bin/console")
    protected static $defaultName = 'links:sync';

    /**
     * Set the help messages
     */
    protected function configure(): void
    {
        $this
            // the short description shown while running "php bin/console list"
            ->setDescription('Make sure links in body are also properly added as "Linked items" and "Linked experiments"')

            // the full command description shown when running the command with
            // the "--help" option
            ->setHelp('Find links to items and experiments in the body of entities and add them to the "Linked items" and "Linked experiments" of that entity. Templates and ItemsTypes can only have links to items');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $Db = Db::getConnection();

        $queryItemsOrExperiment = "SELECT DISTINCT `id`, `body`, `userid`
            FROM `table`
            WHERE `body` LIKE '%database.php?mode=view&amp;id=%'
            OR `body` LIKE '%experiments.php?mode=view&amp;id=%';";
        $queryTemplates = "SELECT `id`, `body`, `userid` FROM `table` WHERE `body` LIKE '%database.php?mode=view&amp;id=%';";
        $queryItemsTypes = "SELECT `id`, `body`, `team` FROM `table` WHERE `body` LIKE '%database.php?mode=view&amp;id=%';";

        $tables = array(
            'experiments' => $queryItemsOrExperiment,
            'items' => $queryItemsOrExperiment,
            'experiments_templates' => $queryTemplates,
            'items_types' => $queryItemsTypes,
        );

        $patternItemsOrExperiment = '/(?<target>database|experiments)\.php\?mode=view&amp;id=(?<id>[0-9]+)/';
        $patternTemplates = '/(?<target>database)\.php\?mode=view&amp;id=(?<id>[0-9]+)/';
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
                printf('Found %d entries with ids:%s', count($res), PHP_EOL);
                $count = 0;
                foreach ($res as $data) {
                    printf('- %d%s', $data['id'], PHP_EOL);

                    // ItemsTypes entries have no user only a team but we need a admin to create a link
                    // -> get an admin from that team
                    if ($table === 'items_types') {
                        $adminOfTeam = (new TeamsHelper($data['team']))->getAllAdminsUserid()[0];
                        $User = new Users($adminOfTeam);
                    } else {
                        $User = new Users($data['userid']);
                    }

                    // don't set entity id yet, user has userData['team'] == 0 at this point
                    // this will result in permission issues during setId -> readOne -> canOrExplode('read') -> hasCommonTeamWithCurrent()
                    $entity = (new EntityFactory($User, $table))->getEntity();

                    // make sure we can access all entries with write access
                    $entity->bypassWritePermission = true;
                    $entity->setId($data['id']);
                    $links = new Links($entity);

                    // look for links to items and experiments in the body and create links
                    // for Templates and ItemsTypes links to experiments will not be added
                    preg_match_all($patterns[$table], $data['body'], $matches, PREG_SET_ORDER);
                    foreach ($matches as $match) {
                        try {
                            $targetEntity = $match['target'] === 'experiments' ? 'experiments' : 'items';
                            if ($links->postAction(Action::Create, array('target_entity' => $targetEntity))) {
                                $count++;
                            }
                        } catch (IllegalActionException | ImproperActionException $e) {
                            // maybe the db item doesn't exist anymore or we no longer have access to it
                            // so just skip that one
                            continue;
                        }
                    }
                }
                printf('Added %d links.%2$s%2$s', $count, PHP_EOL);
            }
        }
        return 0;
    }
}
