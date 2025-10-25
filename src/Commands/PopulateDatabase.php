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

use Elabftw\Exceptions\ImproperActionException;
use Elabftw\Services\Populate;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ConfirmationQuestion;
use Symfony\Component\Yaml\Exception\ParseException;
use Symfony\Component\Yaml\Yaml;
use Override;

use function is_string;
use function floor;
use function microtime;
use function sprintf;

/**
 * Populate the database with example data. Useful to get a fresh dev env.
 * For dev purposes, should not be used by normal users.
 */
#[AsCommand(name: 'db:populate')]
final class PopulateDatabase extends Command
{
    #[Override]
    protected function configure(): void
    {
        $this->setDescription('Populate the database with fake data')
            ->addOption('yes', 'y', InputOption::VALUE_NONE, 'Skip confirmation question')
            ->addOption('fast', 'f', InputOption::VALUE_NONE, 'Only populate minimal things (use in CI)')
            ->addOption('iterations', 'i', InputOption::VALUE_REQUIRED, 'Number of entities to create for each user')
            ->addArgument('file', InputArgument::REQUIRED, 'Yaml configuration file')
            ->setHelp('This command allows you to populate the database with fake users/experiments/items. The database will be dropped before populating it. The configuration is read from the yaml file passed as first argument.');
    }

    #[Override]
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $start = microtime(true);
        try {
            $file = $input->getArgument('file');
            if (!is_string($file)) {
                throw new ImproperActionException('Could not read file from provided file path!');
            }
            $yaml = Yaml::parseFile($file);
        } catch (ParseException | ImproperActionException $e) {
            $output->writeln(sprintf('Error parsing the file: %s', $e->getMessage()));
            return 1;
        }

        // ask confirmation before deleting all the database
        $helper = $this->getHelper('question');
        // the -y flag overrides the config value
        if (($yaml['skip_confirm'] ?? false) === false && !$input->getOption('yes')) {
            $question = new ConfirmationQuestion("WARNING: this command will completely ERASE your current database!\nAre you sure you want to continue? (y/n)\n", false);

            /** @phpstan-ignore-next-line ask method is part of QuestionHelper which extends HelperInterface */
            if (!$helper->ask($input, $output, $question)) {
                $output->writeln('Aborting!');
                return 1;
            }
        }

        $fast = (bool) $input->getOption('fast');
        $iter = null;
        // string 0 is falsy in php!
        if ($input->getOption('iterations') || $input->getOption('iterations') === '0') {
            $iter = (int) $input->getOption('iterations');
        }
        new Populate($output, $yaml, $fast, $iter)->run();

        $elapsed = (int) (microtime(true) - $start);
        $minutes = floor($elapsed / 60);
        $seconds = floor($elapsed % 60);
        $output->writeln(sprintf('├ ✓ All done (%dm%ds)', $minutes, $seconds));
        $output->writeln('└' . str_repeat('─', 62));
        return Command::SUCCESS;
    }
}
