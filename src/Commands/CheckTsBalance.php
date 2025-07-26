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

use Elabftw\Services\Email;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Override;

/**
 * Look at the timestamp balance and notify sysadmin if it's too low
 */
#[AsCommand(name: 'notifications:tsbalance')]
final class CheckTsBalance extends Command
{
    private const int THRESHOLD = 20;

    public function __construct(private int $currentBalance, private Email $Email)
    {
        parent::__construct();
    }

    #[Override]
    protected function configure(): void
    {
        $this->setDescription("Check the balance on timestamps left and create a notification if it's too low")
            ->setHelp("Look at the column ts_balance from Config table and create a notification to sysadmins if it's too low.");
    }

    #[Override]
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        if ($this->currentBalance === 0) {
            return Command::SUCCESS;
        }
        if ($this->currentBalance < self::THRESHOLD) {
            $this->Email->notifySysadminsTsBalance($this->currentBalance);
        }
        return Command::SUCCESS;
    }
}
