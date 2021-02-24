<?php declare(strict_types=1);
/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @copyright 2012 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */
$I = new AcceptanceTester($scenario);
$I->wantTo('Test pinning an entity');
testLogin($I);
$I->amOnPage('/experiments.php?mode=view&id=2');
$I->click('#pinIcon');
$I->waitForJS('return jQuery.active == 0', 10);
$I->seeInDatabase('pin2users', array('users_id' => '1', 'entity_id' => '2', 'type' => 'experiments'));
$I->click('#pinIcon');
$I->waitForJS('return jQuery.active == 0', 10);
$I->dontSeeInDatabase('pin2users', array('users_id' => '1', 'entity_id' => '2', 'type' => 'experiments'));

$I->amOnPage('/database.php?mode=view&id=2');
$I->click('#pinIcon');
$I->waitForJS('return jQuery.active == 0', 10);
$I->waitForJS('return jQuery.active == 0', 10);
$I->seeInDatabase('pin2users', array('users_id' => '1', 'entity_id' => '2', 'type' => 'items'));
$I->click('#pinIcon');
$I->waitForJS('return jQuery.active == 0', 10);
$I->dontSeeInDatabase('pin2users', array('users_id' => '1', 'entity_id' => '2', 'type' => 'items'));
