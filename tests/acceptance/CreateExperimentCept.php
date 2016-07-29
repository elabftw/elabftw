<?php
$I = new AcceptanceTester($scenario);
$I->wantTo('Test creating an experiment');
$I->amOnPage('/login.php');
$I->submitForm('#login', ['email' => 'phpunit@yopmail.com', 'password' => 'phpunit']);
$I->amOnPage('/experiments.php');
$I->see('Howdy');
/*
$I->click("//*[@id='dropdownMenu1']");
$I->click("//*[@id='real_container']/div[2]/div[1]/div/ul/li[1]/a");
$I->see('Tags');
 */
