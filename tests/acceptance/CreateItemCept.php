<?php
$I = new AcceptanceTester($scenario);
$I->wantTo('Test creating an item');
testLogin($I);
$I->amOnPage('/database.php');
$I->click('#dropdownMenu1');
$I->click("/html/body/section/div/div[2]/div[1]/div/ul/li/a");
$I->see('Tags');
