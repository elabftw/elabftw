<?php

/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @copyright 2012 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */

declare(strict_types=1);

namespace Elabftw\Elabftw;

use Defuse\Crypto\Key;
use Elabftw\Exceptions\InvalidCsrfTokenException;
use Symfony\Component\HttpFoundation\Request;

/**
 * Prevent Cross Site Request Forgery (CSRF) attacks
 * See https://owasp.org/www-community/attacks/csrf
 *
 * A token is generated and stored in the session. It will be the same during all user session.
 * When a POST/PATCH request is made, the sent token (in 'csrf' field or as header for Ajax requests) is checked against
 * the stored one and an exception is thrown if they don't match, preventing the request to go through.
 */
final class Csrf
{
    private string $token = '';

    public function __construct(private Request $Request) {}

    /**
     * Get the token and generate one if needed
     */
    public function getToken(): string
    {
        if ($this->token === '') {
            $this->token = Key::createNewRandomKey()->saveToAsciiSafeString();
        }

        return $this->token;
    }

    public function setToken(string $token): void
    {
        $this->token = $token;
    }

    /**
     * Validate the csrf token against the one we have
     */
    public function validate(): void
    {
        // get requests are not checked, same for api requests or the reset password page
        if ($this->Request->getMethod() === 'GET' ||
            $this->Request->server->get('SCRIPT_NAME') === '/app/controllers/ResetPasswordController.php' ||
            $this->Request->server->get('SCRIPT_NAME') === '/app/controllers/ApiController.php') {
            return;
        }

        if ($this->getRequestToken() !== $this->getToken()) {
            // an invalid csrf token is most likely the result of an expired session
            throw new InvalidCsrfTokenException();
        }
    }

    private function getRequestToken(): string
    {
        // an Ajax request will have the token in the headers
        if ($this->Request->headers->get('X-Requested-With') === 'XMLHttpRequest') {
            return (string) $this->Request->headers->get('X-CSRF-Token');
        }
        return $this->Request->request->getString('csrf');
    }
}
