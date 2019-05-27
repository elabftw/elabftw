<?php declare(strict_types=1);
/**
 * @author Nicolas CARPi <nicolas.carpi@curie.fr>
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
$I->pressKey('//*[@id="createTagInput"]', WebDriverKeys::ENTER);
$I->waitForJS('return jQuery.active == 0', 10);
$I->seeInDatabase('tags', array('tag' => 'New tag'));

$I->wantTo('Delete a tag from the tag manager');
$I->amOnPage('admin.php?tab=8');
$I->see('Manage tags of the team');
$I->click('#tag_manager > p:nth-child(1) > svg:nth-child(2) > path:nth-child(2)');
$I->acceptPopup();
$I->waitForJS('return jQuery.active == 0', 10);
$I->dontSeeInDatabase('tags', array('tag' => 'New tag'));
