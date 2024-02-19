<?php declare(strict_types=1);
/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @copyright 2024 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */

namespace Elabftw\Services;

use Elabftw\Enums\PasswordComplexity;
use Elabftw\Exceptions\ImproperActionException;

use function mb_strlen;
use function preg_match;
use function sprintf;

/**
 * Validate a password against instance configuration for length and complexity
 */
class PasswordValidator
{
    public function __construct(private readonly int $minLength, private readonly PasswordComplexity $passwordComplexity)
    {
    }

    public function validate(string $password): bool
    {
        if (mb_strlen($password) < $this->minLength) {
            throw new ImproperActionException(sprintf(_('Password must contain at least %d characters.'), $this->minLength));
        }
        $pattern = $this->passwordComplexity->toPhPattern();
        if (((bool) preg_match($pattern, $password)) === false) {
            throw new ImproperActionException(sprintf(_('Password does not match requirement: %s'), $this->passwordComplexity->toHuman()));
        }
        return true;
    }
}
