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

use Elabftw\Storage\Cache\NginxCache;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Override;
use Symfony\Component\Console\Input\InputArgument;

/**
 * Handle the nginx cache folder
 */
#[AsCommand(name: 'cache:nginx')]
final class CacheNginx extends Cache
{
    #[Override]
    protected function configure(): void
    {
        $this->setDescription('Manage Nginx cache directory')
            ->setHelp('Nginx cache dir cli tool')
            ->addArgument('action', InputArgument::REQUIRED, 'Action to perform: clear or warm', null, array('clear', 'warm'));
    }

    #[Override]
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        if ($input->getArgument('action') === 'warm') {
            new NginxCache()->warm();
        }
        if ($input->getArgument('action') === 'clear') {
            new NginxCache()->clear();
        }
        return 0;
    }
}
