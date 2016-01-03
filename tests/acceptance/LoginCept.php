<?php
$I = new AcceptanceTester($scenario);
$I->wantTo('Test login form');
testLogin($I);
$I->amOnPage('/');
$I->see('Howdy');
