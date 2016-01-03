<?php
$I = new AcceptanceTester($scenario);
$I->wantTo('Ensure that frontpage works');
$I->amOnPage('/');
$I->see('Login');
