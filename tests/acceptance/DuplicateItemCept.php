<?php
$I = new AcceptanceTester($scenario);
$I->wantTo('Test duplicating a database item');
testLogin($I);
$I->amOnPage('/app/controllers/EntityController.php?duplicate=1&id=1&type=items');
$I->see('Tags');
