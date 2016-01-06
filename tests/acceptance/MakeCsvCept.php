<?php
$I = new AcceptanceTester($scenario);
$I->wantTo('Test creating a CSV file');
testLogin($I);
$I->amOnPage('/make.php?what=csv&id=1&type=experiments');
$I->see('ready');
