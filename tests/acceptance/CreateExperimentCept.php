<?php
$I = new AcceptanceTester($scenario);
$I->wantTo('Test creating an experiment');
testLogin($I);
$I->amOnPage('experiments.php');
$I->see('Experiments');
$I->amOnPage('app/controllers/ExperimentsController.php?create=true');
//$I->click("//*[@id='dropdownMenu1']");
//$I->click("//*[@id='real_container']/div[2]/div[1]/div/ul/li[1]/a");
$I->see('Tags');
