<?php
$I = new AcceptanceTester($scenario);
$I->wantTo('Test creating an item');
testLogin($I);
$I->amOnPage('/database.php');
$I->click('/html/body/section/div/menu/div/div[1]/form/select/option[2]');
$I->see('successfully.');
