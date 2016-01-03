<?php
$I = new AcceptanceTester($scenario);
$I->wantTo('Test duplicating an experiment');
testLogin($I);
$I->amOnPage('/experiments.php?mode=view&id=83');
$I->click('duplicate');
$I->see('successfully');
