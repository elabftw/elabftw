<?php

/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @copyright 2023 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */

declare(strict_types=1);

namespace Elabftw\Commands;

use Elabftw\Services\UploadsChecker;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ConfirmationQuestion;
use Override;

/**
 * Check uploaded files
 */
#[AsCommand(name: 'uploads:check')]
final class CheckUploads extends Command
{
    #[Override]
    protected function configure(): void
    {
        $this->setDescription('Check uploaded files')
            ->setHelp('Check attachments to see if they have a hash and filesize that are correct')
            ->addOption('dry-run', 'd', InputOption::VALUE_NONE, 'Do not change anything in the database, just report found issues')
            ->addOption('recompute-hash', 'r', InputOption::VALUE_NONE, 'Recompute ALL uploads hash and replace value in database if a mismatch is found. Run with dry run flag (-d) to prevent database change');
    }

    #[Override]
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $output->writeln(array(
            'Checking uploads',
            '================',
        ));
        $checker = new UploadsChecker($output);

        if ($input->getOption('recompute-hash')) {
            $output->writeln('Recomputing hash value for all uploads... This can take a very long time!');
            $checker->recomputeHash($input->getOption('dry-run'));
            return Command::SUCCESS;
        }

        $output->writeln('Checking for attachments with no stored filesize...');
        $nullFilesize = $checker->getNullColumn('filesize');
        $nullFilesizeCount = count($nullFilesize);
        $output->writeln(sprintf('→ Found %d uploads missing a value for filesize in the database.', $nullFilesizeCount));
        if ($nullFilesizeCount > 0) {
            $helper = $this->getHelper('question');
            $question = new ConfirmationQuestion("Would you like to fix it now?\nThis will look for the filesize on disk and update the stored value in MySQL. (y/n)", false);

            /** @phpstan-ignore-next-line ask method is part of QuestionHelper which extends HelperInterface */
            if ($helper->ask($input, $output, $question)) {
                $output->writeln(sprintf('Fixing stored filesize for %d files...', $nullFilesizeCount));
                $fixedCount = $checker->fixNullFilesize();
                $output->writeln(sprintf('✓ Fixed %d rows', $fixedCount));
            }
        }

        $output->writeln('Checking for attachments with no stored hash...');
        $nullHash = $checker->getNullColumn('hash');
        $nullHashCount = count($nullHash);
        $output->writeln(sprintf('→ Found %d uploads missing a hash value.', $nullHashCount));
        if ($nullHashCount > 0) {
            $helper = $this->getHelper('question');
            $question = new ConfirmationQuestion("Would you like to fix it now?\nThis will compute the hash (even on big files) and update the stored value in MySQL. (y/n)", false);

            /** @phpstan-ignore-next-line ask method is part of QuestionHelper which extends HelperInterface */
            if ($helper->ask($input, $output, $question)) {
                $output->writeln(sprintf('Fixing stored hash for %d files...', $nullHashCount));
                $fixedCount = $checker->fixNullHash();
                $output->writeln(sprintf('✓ Fixed %d rows', $fixedCount));
            }
        }
        return Command::SUCCESS;
    }
}
