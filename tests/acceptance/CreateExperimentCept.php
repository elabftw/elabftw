<?php
$I = new AcceptanceTester($scenario);
$I->wantTo('Test creating an experiment');
testLogin($I);
$I->amOnPage('/experiments.php');
$I->click("//*[@id='dropdownMenu1']");
$I->click("//*[@id='real_container']/div[2]/div[1]/div/ul/li[1]/a");
$I->see('Tags');
