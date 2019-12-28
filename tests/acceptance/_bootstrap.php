<?php declare(strict_types=1);
/**
 * @author Nicolas CARPi <nicolas.carpi@curie.fr>
 * @copyright 2012 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */

/**
 * Login to the test instance
 */
function testLogin($I)
{
    // if snapshot exists -> skip login
    if ($I->loadSessionSnapshot('login')) {
        return;
    }
    // logging in
    $I->amOnPage('/login.php');
    $I->submitForm('#login', array('email' => 'toto@yopmail.com', 'password' => 'phpunitftw'));
    // saving snapshot
    $I->saveSessionSnapshot('login');
}
