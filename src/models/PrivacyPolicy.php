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

    public function readAll(): array
    {
        $privacyPolicy = $this->Config->configArr['privacy_policy'] ?? throw new ResourceNotFoundException('No policy set');
        return array($privacyPolicy);
    }

    public function readOne(): array
    {
        return $this->readAll();
    }

    public function update(ContentParamsInterface $params): bool
    {
        $this->Config->patch(array('privacy_policy' => $params->getBody()));
        return true;
    }

    public function destroy(): bool
    {
        $this->Config->patch(array('privacy_policy' => null));
        return true;
    }
}
