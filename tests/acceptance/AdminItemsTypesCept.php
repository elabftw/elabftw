<?php declare(strict_types=1);
/**
 * @author Nicolas CARPi <nicolas.carpi@curie.fr>
 * @copyright 2012 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */
$I = new AcceptanceTester($scenario);
$I->wantTo('Create an item type');
testLogin($I);
$I->amOnPage('admin.php?tab=5');
$I->see('Add a new type of item');
$I->fillField('//*[@id="itemsTypesName"]', 'New item type');
$I->click('//*[@id="itemsTypesCreate"]');
$I->waitForJS('return jQuery.active == 0', 10);
$I->amOnPage('admin.php?tab=5');
$I->seeInDatabase('items_types', array('name' => 'New item type'));

$I->wantTo('Edit the newly created item type');
$I->amOnPage('admin.php?tab=5');
// fillField doesn't work if input is not in form
$I->clearField('//*[@id="itemsTypesName_2"]'); // but clearField works…
$I->click('//*[@id="itemsTypesName_2"]');
// this is necessary to show the template and make the Save button work
// even if it works without this in real life
$I->click('/html/body/section/div/div[7]/div[2]/ul/li[2]/ul/li[4]/button');
$I->pressKey('//*[@id="itemsTypesName_2"]', 'New item type edited');
$I->click('/html/body/section/div/div[7]/div[2]/ul/li[2]/ul/li[5]/button');
$I->waitForJS('return jQuery.active == 0', 10);
$I->seeInDatabase('items_types', array('name' => 'New item type edited'));
