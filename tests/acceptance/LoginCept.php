<?php
$I = new AcceptanceTester($scenario);
$I->wantTo('test login');
$I->amOnPage('/');
$I->submitForm('#login', ['username' => 'testguy', 'password' => 'testtest']);
$I->see('Howdy');
