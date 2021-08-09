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
     * Validate the form key against the one previously set in Session
     */
    public function validate(): void
    {
        // get requests are not checked
        if ($this->Request->server->get('REQUEST_METHOD') === 'GET') {
            return;
        }

        if ($this->getRequestToken() !== $this->getToken()) {
            // an invalid csrf token is most likely the result of an expired session
            throw new ImproperActionException(_('Your session expired.'));
        }
    }

    private function getRequestToken(): string
    {
        // an Ajax request will have the token in the headers
        if ($this->Request->headers->get('X-Requested-With') === 'XMLHttpRequest') {
            return (string) $this->Request->headers->get('X-CSRF-Token');
        }
        return (string) $this->Request->request->get('csrf');
    }
}
