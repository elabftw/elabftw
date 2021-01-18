<?php declare(strict_types=1);
/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @copyright 2012 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */
$I = new AcceptanceTester($scenario);
testLogin($I);

$I->amOnPage('admin.php?tab=1');
$I->see('Configure your Team');

// DELETABLE XP YES
$I->wantTo('Set deletable xp to yes');
$I->selectOption('//*[@id="deletable_xp"]', 'Yes');
// click save button
$I->click('div.mt-4:nth-child(26) > button:nth-child(1)');
$I->seeInDatabase('teams', array('deletable_xp' => '1'));

$I->amOnPage('admin.php?tab=1');
// DELETABLE XP NO
$I->wantTo('Set deletable xp to no');
$I->selectOption('//*[@id="deletable_xp"]', 'No');
// click save button
$I->click('div.mt-4:nth-child(26) > button:nth-child(1)');
$I->seeInDatabase('teams', array('deletable_xp' => '0'));

$I->amOnPage('admin.php?tab=1');
// PUBLIC DB YES
$I->wantTo('Set public db to yes');
$I->selectOption('//*[@id="public_db"]', 'Yes');
// click save button
$I->click('div.mt-4:nth-child(26) > button:nth-child(1)');
$I->seeInDatabase('teams', array('public_db' => '1'));
$I->amOnPage('admin.php?tab=1');
// PUBLIC DB NO
$I->wantTo('Set public db to no');
$I->selectOption('//*[@id="public_db"]', 'No');
// click save button
$I->click('div.mt-4:nth-child(26) > button:nth-child(1)');
$I->seeInDatabase('teams', array('public_db' => '0'));

$I->amOnPage('admin.php?tab=1');
// LINK_NAME
$I->wantTo('Change the link name');
$I->fillField('link_name', 'DocDoc');
$I->click('div.mt-4:nth-child(26) > button:nth-child(1)');
$I->seeInDatabase('teams', array('link_name' => 'DocDoc'));

$I->amOnPage('admin.php?tab=1');
// LINK_HREF
$I->wantTo('Change the link target');
$I->fillField('link_href', 'https://new.elabftw.net');
$I->click('div.mt-4:nth-child(26) > button:nth-child(1)');
$I->seeInDatabase('teams', array('link_href' => 'https://new.elabftw.net'));
