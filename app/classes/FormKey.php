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
namespace Elabftw\Elabftw;

use Defuse\Crypto\Key as Key;
use Symfony\Component\HttpFoundation\Session\Session;

/**
 * Generate and validate keys for input forms.
 * **Note** : for a page with several *form* elements this will work only for 1 *form*!
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
     * Return the form key for inclusion in HTML
     *
     * @return string $hinput Hidden input html
     */
    public function getFormkey()
    {
        // generate the key
        $formkey = Key::createNewRandomKey()->saveToAsciiSafeString();
        // store the form key in the session
        $this->Session->set('formkey', $formkey);
        // output the form key
        return "<input type='hidden' name='formkey' value='" . $formkey . "' />";
    }

    /**
     * Validate the form key against the one previously set in Session
     *
     * @param string $formkey
     * @return bool True if there is no CSRF going on (hopefully)
     */
    public function validate($formkey)
    {
        return $formkey === $this->Session->get('formkey');
    }
}
