<?php declare(strict_types=1);
/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @copyright 2012 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */

namespace Elabftw\Elabftw;

use Defuse\Crypto\Key;
use Elabftw\Exceptions\ImproperActionException;
use Symfony\Component\HttpFoundation\Request;

/**
 * Prevent CSRF attacks
 */
class Csrf
{
    private string $token = '';

    public function __construct(private Request $Request)
    {
    }

    /**
     * Generate a token if needed
     */
    public function getToken(): string
    {
        if ($this->token === '') {
            $this->token = $this->generate();
        }

        return $this->token;
    }

    public function setToken(string $token): void
    {
        $this->token = $token;
    }

    /**
     * Validate the form key against the one previously set in Session
     */
    public function validate(): void
    {
        // get requests are not checked
        if ($this->Request->server->get('REQUEST_METHOD') === 'GET') {
            return;
        }
        // detect ajax request
        if ($this->Request->headers->get('X-Requested-With') === 'XMLHttpRequest') {
            $res = $this->validateAjax();
        } else {
            $res = $this->validateForm();
        }
        if (!$res) {
            // an invalid csrf token is most likely the result of an expired session
            throw new ImproperActionException(_('Your session expired.'));
        }
    }

    /**
     * Generate a CSRF token
     */
    private function generate(): string
    {
        return Key::createNewRandomKey()->saveToAsciiSafeString();
    }

    /**
     * AJAX requests find the token in header
     */
    private function validateAjax(): bool
    {
        return $this->Request->headers->get('X-CSRF-Token') === $this->getToken();
    }

    /**
     * Normal forms send the token with hidden field
     */
    private function validateForm(): bool
    {
        return $this->Request->request->get('csrf') === $this->getToken();
    }
}
