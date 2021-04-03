<?php
/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @copyright 2012 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */
declare(strict_types=1);

namespace Elabftw\Models;

use Elabftw\Exceptions\ResourceNotFoundException;

/**
 * Privacy policy CRUD class
 */
class PrivacyPolicy
{
    private Config $Config;

    public function __construct(Config $config)
    {
        $this->Config = $config;
    }

    public function read(): string
    {
        return $this->Config->configArr['privacy_policy'] ?? throw new ResourceNotFoundException('No policy set');
    }

    public function update(string $policy): void
    {
        $this->Config->update(array('privacy_policy' => $policy));
    }

    public function clear(): void
    {
        $this->Config->update(array('privacy_policy' => null));
    }
}
