<?php
/**
 * \Elabftw\Elabftw\Csrf.php
 *
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
use Symfony\Component\HttpFoundation\Session\SessionInterface;

/**
 * Prevent CSRF attacks
 */
class Csrf
{
    /** @var Request $Request the request object */
    private $Request;

    /** @var SessionInterface $Session the session object */
    private $Session;

    public function __construct(Request $request, SessionInterface $session)
    {
        $this->Request = $request;
        $this->Session = $session;
        if (!$this->Session->has('csrf')) {
            $this->Session->set('csrf', $this->generate());
        }
    }

    /**
     * Return the form key for inclusion in HTML
     *
     * @return string
     */
    public function getHiddenInput(): string
    {
        return "<input type='hidden' name='csrf' value='" . $this->getToken() . "' />";
    }

    /**
     * Read token from session
     *
     * @return string
     */
    public function getToken(): string
    {
        return $this->Session->get('csrf');
    }

    /**
     * Validate the form key against the one previously set in Session
     *
     * @return void
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
        if ($res === false) {
            throw new InvalidCsrfTokenException();
        }
    }

    /**
     * Generate a CSRF token
     *
     * @return string
     */
    private function generate(): string
    {
        return Key::createNewRandomKey()->saveToAsciiSafeString();
    }

    /**
     * AJAX requests find the token in header
     *
     * @return bool
     */
    private function validateAjax(): bool
    {
        return $this->Request->headers->get('X-CSRF-Token') === $this->getToken();
    }

    /**
     * Normal forms send the token with hidden field
     *
     * @return bool
     */
    private function validateForm(): bool
    {
        return $this->Request->request->get('csrf') === $this->getToken();
    }
}
