<?php
$I = new AcceptanceTester($scenario);
$I->wantTo('Test creating an experiment');
testLogin($I);
$I->amOnPage('/experiments.php');
$I->click('#createExperiment');
$I->see('successfully.');
