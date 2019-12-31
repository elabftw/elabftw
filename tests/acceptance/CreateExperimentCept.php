<?php declare(strict_types=1);
/**
 * @author Nicolas CARPi <nicolas.carpi@curie.fr>
 * @copyright 2012 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */
$I = new AcceptanceTester($scenario);
$I->wantTo('Test creating an experiment');
testLogin($I);
$I->amOnPage('experiments.php');
$I->see('Experiments');
$I->amOnPage('experiments.php?create=true');
$I->see('Tags');
$I->see('Date');
$I->see('Title');
/* for some reason the editor cannot be accessed...
$I->wantTo('Change the body of the experiment');
$I->executeJS("tinymce.get('body_area').setContent('supercalifragilisticexpialidocious');");
$I->click('.submitButtonDiv > button:nth-child(1)');
$I->seeInDatabase('experiments', array('body' => '<p>supercalifragilisticexpialidocious</p>'));
*/
