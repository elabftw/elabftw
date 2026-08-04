<?php

/**
 * @author Nicolas CARPi / Deltablot
 * @copyright 2026 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */

declare(strict_types=1);

namespace Elabftw\Elabftw;

use Elabftw\Interfaces\PasswordInterface;
use Override;

class NullLocalPassword implements PasswordInterface
{
    #[Override]
    public function toHash(): string
    {
        return '';
    }
}
