<?php
$I = new AcceptanceTester($scenario);
$I->wantTo('Test duplicating a database item');
testLogin($I);
$I->amOnPage('/database.php?mode=view&id=571');
$I->click('duplicate');
$I->see('successfully');
