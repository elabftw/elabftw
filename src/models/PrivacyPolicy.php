<?php declare(strict_types=1);
/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @copyright 2012 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */

namespace Elabftw\Models;

use Elabftw\Enums\Action;
use Elabftw\Exceptions\ResourceNotFoundException;
use Elabftw\Services\Filter;

/**
 * Privacy policy CRUD class
 */
class PrivacyPolicy
{
    public function __construct(private Config $Config)
    {
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

    public function update(string $body): bool
    {
        $this->Config->patch(Action::Update, array('privacy_policy' => Filter::body($body)));
        return true;
    }

    public function destroy(): bool
    {
        $this->Config->patch(Action::Update, array('privacy_policy' => null));
        return true;
    }
}
