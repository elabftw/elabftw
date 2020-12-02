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

use Defuse\Crypto\Key;
use function dirname;
use Elabftw\Services\DatabaseInstaller;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\Question;
use Symfony\Component\Filesystem\Exception\IOExceptionInterface;
use Symfony\Component\Filesystem\Filesystem;

/**
 * Install the database when not in docker
 */
class Install extends Command
{
    // the name of the command (the part after "bin/console")
    protected static $defaultName = 'start';

    protected function configure(): void
    {
        $this
            // the short description shown while running "php bin/console list"
            ->setDescription('Install eLabFTW in a MySQL database')

            // the full command description shown when running the command with
            // the "--help" option
            ->setHelp('Ask information to connect to the MySQL database, create the config file and load the database structure.');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $fs = new Filesystem();
        $elabRoot = dirname(__DIR__, 2);
        $configFilePath = $elabRoot . '/config.php';
        $cacheDir = $elabRoot . '/cache';
        $uploadsDir = $elabRoot . '/uploads';

        $output->writeln(array(
            '',
            '      _          _     _____ _______        __',
            "  ___| |    __ _| |__ |  ___|_   _\ \      / /",
            " / _ \ |   / _| | '_ \| |_    | |  \ \ /\ / / ",
            "|  __/ |__| (_| | |_) |  _|   | |   \ V  V /  ",
            " \___|_____\__,_|_.__/|_|     |_|    \_/\_/   ",
            '                                              ',
            '',
        ));


        $output->writeln(array(
            'Welcome to the install of eLabFTW!',
            'This program will check if everything is good, create a config.php file and install the MySQL structure.',
            'Before proceeding, make sure you have an empty MySQL database for eLabFTW with a user+password to access it.',
            '',
        ));
        $output->writeln('=> Preliminary checks starting');

        // check for config.php
        if ($fs->exists($configFilePath)) {
            $output->writeln('<info>✓ A config file is already present. It will be used to initialize the database.</info>');
        } else {
            $output->writeln('<info>✓ No config file present. One will be created.</info>');
            // check if the folder is writable for saving the config file
            if (!\is_writable($elabRoot)) {
                $msg = sprintf('The eLabFTW folder (%s) is not writable by the current user. Adjust permissions and try again.', $elabRoot);
                $output->writeln('<error>ERROR: ' . $msg . '</error>');
                return 1;
            }
            $output->writeln('<info>✓ Folder is writable by current user.</info>');
        }

        // Check for hash function
        if (!function_exists('hash')) {
            $message = "You don't have the hash function. On Freebsd it's in /usr/ports/security/php73-hash.";
            $output->writeln('<error>ERROR: ' . $message . '</error>');
            return 1;
        }

        $output->writeln('<info>✓ Hash function is present.</info>');

        // same doc url for cache and uploads folder
        $doc = 'https://doc.elabftw.net/faq.html#failed-creating-uploads-directory';
        try {
            $dirs = array($cacheDir, $uploadsDir);
            $fs->mkdir($dirs);
            $fs->chmod($dirs, 0777);
        } catch (IOExceptionInterface $e) {
            $output->writeln('<error>ERROR: ' . $e->getMessage() . '</error>');
            $message = 'Documentation: ' . $doc;
            $output->writeln($message);
            return 1;
        }

        if (!$fs->exists($configFilePath)) {
            $output->writeln('✓ All preliminary checks suceeded. Now asking information to produce the config.php file.');
            $output->writeln('<comment>The value between brackets is the default value entered if you just press enter.</comment>');
            // ask for authentication credentials
            $helper = $this->getHelper('question');
            $config = array();
            $question = new Question('<question>MySQL hostname [localhost]:</question> ', 'localhost');
            $config['dbHost'] = $helper->ask($input, $output, $question);
            $question = new Question('<question>Database name [elabftw]:</question> ', 'elabftw');
            $config['dbName'] = $helper->ask($input, $output, $question);
            $question = new Question('<question>Database port [3306]:</question> ', '3306');
            $config['dbPort'] = $helper->ask($input, $output, $question);
            $question = new Question('<question>MySQL username [elabftw]:</question> ', 'elabftw');
            $config['dbUser'] = $helper->ask($input, $output, $question);
            $question = new Question('<question>MySQL password:</question> ', '');
            $config['dbPass'] = $helper->ask($input, $output, $question);

            // BUILD CONFIG FILE
            $configFilePath = $elabRoot . '/config.php';

            // make a new secret key
            $key = Key::createNewRandomKey();

            // what we will write in the file
            $configContent = "<?php
            define('DB_HOST', '" . $config['dbHost'] . "');
            define('DB_PORT', '" . $config['dbPort'] . "');
            define('DB_NAME', '" . $config['dbName'] . "');
            define('DB_USER', '" . $config['dbUser'] . "');
            define('DB_PASSWORD', '" . $config['dbPass'] . "');
            define('SECRET_KEY', '" . $key->saveToAsciiSafeString() . "');";
            $fs->dumpFile($configFilePath, $configContent);
            $output->writeln('✓ Config file is now in place.');
        }

        $output->writeln('<info>=> Initializing MySQL database...</info>');
        $Installer = new DatabaseInstaller();
        $Installer->install();
        $output->writeln('<info>✓ Installation successful! You can now start using your eLabFTW instance.</info>');
        return 0;
    }
}
