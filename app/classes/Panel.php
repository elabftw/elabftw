<?php
/**
 * \Elabftw\Elabftw\Panel
 *
 * @author Nicolas CARPi <nicolas.carpi@curie.fr>
 * @copyright 2012 Nicolas CARPi
 * @see http://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */
namespace Elabftw\Elabftw;

/**
 * Stuff for admin and sysadmin panel
 */
class Panel
{
    /**
     * Check for admin rights
     *
     * @return int 1 if is_admin
     */
    protected function isAdmin()
    {
        return $_SESSION['is_admin'];
    }
    /**
     * Check for sysadmin rights
     *
     * @return int 1 if is_sysadmin
     */
    protected function isSysAdmin()
    {
        return $_SESSION['is_sysadmin'];
    }
}
