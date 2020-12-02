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

$I->amOnPage('sysconfig.php?tab=1');
$I->see('Installed version');

// Announcement
$I->wantTo('Test the announcement system');
$I->fillField('input.form-control:nth-child(1)', 'Blah blah blih');
$I->click('div.submitButtonDiv:nth-child(2) > button:nth-child(1)');
$I->amOnPage('sysconfig.php?tab=1');
$I->see('Blah blah blih');
$I->click('div.submitButtonDiv:nth-child(2) > button:nth-child(2)');
$I->amOnPage('sysconfig.php?tab=1');
$I->dontsee('Blah blah blih');
