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
use Elabftw\Interfaces\ContentParamsInterface;
use Elabftw\Interfaces\CrudInterface;

/**
 * Privacy policy CRUD class
 */
class PrivacyPolicy implements CrudInterface
{
    public function __construct(private Config $Config)
    {
    }

    public function create(ContentParamsInterface $params): int
    {
        return 0;
    }

    public function read(ContentParamsInterface $params): string
    {
        return $this->Config->configArr['privacy_policy'] ?? throw new ResourceNotFoundException('No policy set');
    }

    public function update(ContentParamsInterface $params): bool
    {
        $this->Config->updateAll(array('privacy_policy' => $params->getBody()));
        return true;
    }

    public function destroy(): bool
    {
        $this->Config->updateAll(array('privacy_policy' => null));
        return true;
    }
}
