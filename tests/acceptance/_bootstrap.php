<?php
// Here you can initialize variables that will be available to your tests
function testLogin($I)
{
    // if snapshot exists - skipping login
    if ($I->loadSessionSnapshot('login')) {
        return;
    }
    // logging in
    $I->amOnPage('/login.php');
    $I->submitForm('#login', ['username' => 'testguy', 'password' => 'testtest']);
    // saving snapshot
    $I->saveSessionSnapshot('login');
}
