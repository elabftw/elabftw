<?php
$I = new AcceptanceTester($scenario);
$I->wantTo('Test creating an item');
testLogin($I);
$I->amOnPage('database.php?create=true&tpl=1');
$I->see('Tags');
$I->see('Date');
$I->see('Title');
