<?php
$I = new AcceptanceTester($scenario);
$I->wantTo('Test duplicating an experiment');
testLogin($I);
$I->amOnPage('/app/controllers/EntityController.php?duplicate=1&id=1&type=experiments');
$I->see('Tags');
