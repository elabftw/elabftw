<?php
$I = new AcceptanceTester($scenario);
$I->wantTo('Test adding a comment on an experiment');
testLogin($I);
$I->amOnPage('experiments.php?mode=view&id=1');
$I->fillField('#commentsCreateArea', 'A nice comment');
$I->click('#commentsCreateButton');
$I->waitForJS('return jQuery.active == 0', 10);
$I->see('commented');
