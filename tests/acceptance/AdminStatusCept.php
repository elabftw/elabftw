<?php declare(strict_types=1);
/**
 * @author Nicolas CARPi <nicolas.carpi@curie.fr>
 * @copyright 2012 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */
$I = new AcceptanceTester($scenario);
$I->wantTo('Create a new status');
testLogin($I);
$I->amOnPage('admin.php?tab=4');
$I->see('Add a New Status');
$I->fillField('//*[@id="statusName"]', 'New status');
$I->click('//*[@id="statusCreate"]');
$I->waitForJS('return jQuery.active == 0', 10);
$I->amOnPage('admin.php?tab=4');
$I->seeInDatabase('status', array('name' => 'New status'));

$I->wantTo('Edit the newly created status');
$I->amOnPage('admin.php?tab=4');
// fillField doesn't work if input is not in form
$I->clearField('//*[@id="statusName_2"]'); // but clearField works…
$I->click('//*[@id="statusName_2"]');
// this is necessary to show the template and make the Save button work
// even if it works without this in real life
$I->pressKey('//*[@id="statusName_2"]', 'New status edited');
$I->click('/html/body/section/div/div[6]/div[2]/ul/li[2]/ul/li[5]/button');
$I->waitForJS('return jQuery.active == 0', 10);
$I->seeInDatabase('status', array('name' => 'New status edited'));
