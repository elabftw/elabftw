<?php
$I = new AcceptanceTester($scenario);
$I->wantTo('Test duplicating an experiment');
testLogin($I);
$I->amOnPage('/app/controllers/EntityController.php?duplicate=1&id=1type=experiments');
$I->see('Tags');
