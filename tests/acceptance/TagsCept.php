<?php declare(strict_types=1);
/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @copyright 2012 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */
$I = new AcceptanceTester($scenario);
$I->wantTo('Add a tag');
testLogin($I);
$I->amOnPage('experiments.php?mode=edit&id=1');
$I->see('Tags');
$I->fillField('//*[@id="createTagInput"]', 'New tag');
$I->pressKey('//*[@id="createTagInput"]', \Facebook\WebDriver\WebDriverKeys::ENTER);
$I->waitForJS('return jQuery.active == 0', 10);
$I->wait(2);
$I->seeInDatabase('tags', array('tag' => 'New tag'));

$I->wantTo('Delete a tag from the tag manager');
$I->amOnPage('admin.php?tab=8');
$I->see('Manage tags of the team');
$I->click('#tag_manager > p:nth-child(1) > svg:nth-child(1)');
$I->acceptPopup();
$I->waitForJS('return jQuery.active == 0', 10);
// with the new populated database there is a lot of different tags
//$I->dontSeeInDatabase('tags', array('tag' => 'New tag'));
