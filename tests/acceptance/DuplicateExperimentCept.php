<?php
$I = new AcceptanceTester($scenario);
$I->wantTo('Test duplicating an experiment');
testLogin($I);
//$I->amOnPage('/experiments.php?mode=view&id=1');
$I->amOnPage('/app/controllers/EntityController.php?duplicateId=1&type=experiments');
#$I->click('Duplicate');
$I->see('Tags');
