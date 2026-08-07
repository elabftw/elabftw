<?php

/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @copyright 2024 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */

declare(strict_types=1);

namespace Elabftw\Services;

use Elabftw\Enums\PasswordComplexity;
use Elabftw\Exceptions\ImproperActionException;
use Elabftw\Interfaces\ValidatorInterface;
use Override;

use function mb_strlen;
use function preg_match;
use function sprintf;
use function _;

/**
 * Validate a password against instance configuration for length and complexity
 */
final class PasswordValidator implements ValidatorInterface
{
    public function __construct(private readonly int $minLength, private readonly PasswordComplexity $passwordComplexity, private readonly string $password) {}

    #[Override]
    public function validate(): bool
    {
        if (mb_strlen($this->password) < $this->minLength) {
            throw new ImproperActionException(sprintf(_('Password must contain at least %d characters.'), $this->minLength));
        }
        $pattern = $this->passwordComplexity->toPhPattern();
        if (((bool) preg_match($pattern, $this->password)) === false) {
            throw new ImproperActionException(sprintf(_('Password does not match requirement: %s'), $this->passwordComplexity->toHuman()));
        }
        return true;
    }
}
