<?php
$I = new AcceptanceTester($scenario);
$I->wantTo('Change the visibility to user');
testLogin($I);
$I->amOnPage('experiments.php?mode=edit&id=1');
$I->selectOption('#visibility_select', 'Only me');
$I->waitForJS('return jQuery.active == 0', 10);
$I->amOnPage('experiments.php?mode=view&id=1');
$I->see('User');
