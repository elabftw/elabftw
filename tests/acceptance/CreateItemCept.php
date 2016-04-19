<?php
$I = new AcceptanceTester($scenario);
$I->wantTo('Test creating an item');
testLogin($I);
$I->amOnPage('/database.php');
$I->click('#dropdownMenu1');
$I->click("id('real_container')/x:div[2]/x:div[1]/x:div/x:ul/x:li/x:a");
$I->see('Tags');
