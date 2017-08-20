<?php
$I = new AcceptanceTester($scenario);
$I->wantTo('Test duplicating a database item');
testLogin($I);
//$I->amOnPage('/database.php?mode=view&id=1');
//$I->click('Duplicate');
$I->amOnPage('/app/controllers/EntityController.php?duplicateId=1&type=items');
$I->see('Tags');
