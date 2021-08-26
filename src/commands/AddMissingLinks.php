<?php declare(strict_types=1);
/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @copyright 2012 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */

namespace Elabftw\Commands;

use Elabftw\Elabftw\ContentParams;
use Elabftw\Elabftw\Db;
use Elabftw\Exceptions\IllegalActionException;
use Elabftw\Models\Experiments;
use Elabftw\Models\Items;
use Elabftw\Models\Links;
use Elabftw\Models\Templates;
use Elabftw\Models\Users;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Find links to items in entity bodies and add to 'linked items'
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
            ->setDescription('Make sure links in body are also properly added as "Linked items"')

            // the full command description shown when running the command with
            // the "--help" option
            ->setHelp('Find links to items in the body of entities and add them to the "Linked items" of that entity.');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $Db = Db::getConnection();

        $tables = array('experiments', 'experiments_templates', 'items');
        $query = "SELECT `id`, `body`, `userid` FROM `table` WHERE `body` LIKE '%database.php?mode=view&amp;id=%';";

        foreach ($tables as $table) {
            echo 'Searching in ' . $table . "\n";
            $sql = str_replace('table', $table, $query);
            $req = $Db->prepare($sql);
            $req->execute();
            $res = $req->fetchAll();

            if (!empty($res)) {
                echo 'Found ' . count($res) . " entries with ids:\n";
                $count = 0;
                foreach ($res as $data) {
                    echo '  ' . $data['id'] . "\n";
                    switch ($table) {
                        case 'experiments':
                            $entity = new Experiments(new Users((int) $data['userid']), (int) $data['id']);
                            break;
                        case 'experiments_templates':
                            $entity = new Templates(new Users((int) $data['userid']), (int) $data['id']);
                            break;
                        case 'items':
                            $entity = new Items(new Users((int) $data['userid']), (int) $data['id']);
                            break;
                        default:
                            continue 2;
                    }

                    preg_match_all('/database\.php\?mode=view&amp;id=([0-9]+)/', $data['body'], $matches);
                    foreach ($matches[1] as $match) {
                        try {
                            $out = (new Links($entity))->create(new ContentParams($match));
                            if ((int) $out !== 0) {
                                $count++;
                            }
                        } catch (IllegalActionException $e) {
                            // maybe the db item doesn't exist anymore or we no longer have access to it
                            // so just skip that one
                            continue;
                        }
                    }
                }
                echo 'Added ' . $count . " links.\n\n";
            }
        }
        return 0;
    }
}
