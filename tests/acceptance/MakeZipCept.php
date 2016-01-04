<?php
$I = new AcceptanceTester($scenario);
$I->wantTo('Test creating a zip archive');
testLogin($I);
$I->amOnPage('/make.php?what=zip&id=90&type=experiments');
$I->see('ready');
