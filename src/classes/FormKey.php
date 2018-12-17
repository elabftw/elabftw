<?php
/**
 * \Elabftw\Elabftw\FormKey
 *
 * @author Nicolas CARPi <nicolas.carpi@curie.fr>
 * @copyright 2012 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */
declare(strict_types=1);

namespace Elabftw\Elabftw;

use Defuse\Crypto\Key;
use Symfony\Component\HttpFoundation\Session\Session;

/**
 * Generate and validate keys for input forms
 */
class FormKey
{
    /** @var Session $Session the session object */
    private $Session;

    /**
     * We need the Session object
     *
     * @param Session $session
     */
    public function __construct(Session $session)
    {
        $this->Session = $session;
    }

    /**
     * Generate the key and store it in session
     *
     * @throws Exception
     * @return array
     */
    private function create(): array
    {
        $fkname = \bin2hex(\random_bytes(42));
        $fkvalue = Key::createNewRandomKey()->saveToAsciiSafeString();
        $this->Session->set($fkname, $fkvalue);

        return array('value' => $fkvalue, 'name' => $fkname);
    }

    /**
     * Return the form key for inclusion in HTML
     *
     * @return string
     */
    public function getFormkey(): string
    {
        $formkeyArr = $this->create();
        return "<input type='hidden' name='fkname' value='" . $formkeyArr['name'] . "' />
                <input type='hidden' name='fkvalue' value='" . $formkeyArr['value'] . "' />";
    }

    /**
     * Generate data attributes for javascript actions
     *
     * @return string
     */
    public function getDataFormkey(): string
    {
        $formkeyArr = $this->create();
        return "data-fkname='" . $formkeyArr['name'] . "' data-fkvalue='" . $formkeyArr['value'] . "'";
    }

    /**
     * Validate the form key against the one previously set in Session
     *
     * @param string $value
     * @param string $name
     * @return bool True if there is no CSRF going on (hopefully)
     */
    public function validate(string $value, string $name): bool
    {
        return $value === $this->Session->get($name);
    }
}
