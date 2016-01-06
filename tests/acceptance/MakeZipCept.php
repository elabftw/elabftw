<?php
$I = new AcceptanceTester($scenario);
$I->wantTo('Test creating a zip archive');
testLogin($I);
$I->amOnPage('/make.php?what=zip&id=1&type=experiments');
$I->see('ready');
