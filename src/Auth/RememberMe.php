<?php

/**
 * @package   Elabftw\Elabftw
 * @author    Nicolas CARPi / Deltablot
 * @copyright 2026 Nicolas CARPi
 * @license   https://www.gnu.org/licenses/agpl-3.0.html AGPL-3.0
 * @see       https://www.elabftw.net Official website
 */

declare(strict_types=1);

namespace Elabftw\Auth;

use Symfony\Component\HttpFoundation\Request;

use function setcookie;
use function time;

final class RememberMe
{
    private const string COOKIE_NAME = 'icanhazcookies';

    private ?bool $captured = null;

    public function __construct(
        private readonly Request $request,
        private readonly bool $allowed,
    ) {}

    public function capture(): void
    {
        $this->captured = $this->allowed
            && $this->request->request->has('rememberme');

        $this->setCookie(
            $this->captured ? '1' : '0',
            time() + 300,
        );
    }

    public function isRequested(): bool
    {
        if (!$this->allowed) {
            return false;
        }

        // Needed when authentication completes in the same HTTP request:
        // setcookie() does not update Request::$cookies.
        if ($this->captured !== null) {
            return $this->captured;
        }

        return $this->request->cookies->getBoolean(
            self::COOKIE_NAME,
        );
    }

    public function clear(): void
    {
        $this->captured = null;
        $this->setCookie('', time() - 3600);
    }

    private function setCookie(string $value, int $expires): void
    {
        setcookie(
            self::COOKIE_NAME,
            $value,
            array(
                'expires' => $expires,
                'path' => '/',
                'domain' => '',
                'secure' => true,
                'httponly' => true,
                'samesite' => 'None',
            ),
        );
    }
}
