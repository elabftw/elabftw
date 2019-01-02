<?php
$I = new AcceptanceTester($scenario);
$I->wantTo('Test creating an experiment');
testLogin($I);
$I->amOnPage('experiments.php');
$I->see('Experiments');
$I->amOnPage('experiments.php?create=true');
$I->see('Tags');
$I->see('Date');
$I->see('Title');
