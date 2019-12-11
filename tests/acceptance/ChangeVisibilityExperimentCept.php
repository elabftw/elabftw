<?php declare(strict_types=1);
/**
 * @author Nicolas CARPi <nicolas.carpi@curie.fr>
 * @copyright 2012 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */
$I = new AcceptanceTester($scenario);
$I->wantTo('Change the visibility to user');
testLogin($I);
$I->amOnPage('experiments.php?mode=edit&id=1');
// click the ellipsis menu
$I->click('html.fontawesome-i2svg-active.fontawesome-i2svg-complete body div#container.container-fluid div.real-container section#main_section.box div.dropdown svg.svg-inline--fa.fa-ellipsis-h.fa-w-16.dropdown-toggle.fa-2x.fa-pull-right.clickable');
// click the Manage Permissions menu item
$I->click('/html/body/div[1]/div/section[1]/div[1]/div/a[1]');
$I->wait(1);
$I->selectOption('#canread_select', 'Public');
$I->selectOption('#canwrite_select', 'Only the team');
$I->waitForJS('return jQuery.active == 0', 10);
$I->seeInDatabase('experiments', array('canread' => 'public'));
$I->seeInDatabase('experiments', array('canwrite' => 'team'));
