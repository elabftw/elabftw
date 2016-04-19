<?php
// Here you can initialize variables that will be available to your tests
function testLogin($I)
{
    // if snapshot exists -> skip login
    if ($I->loadSessionSnapshot('login')) {
        return;
    }
    // logging in
    $I->amOnPage('/login.php');
    $I->submitForm('#login', ['username' => 'sysadmin', 'password' => 'sysadmin']);
    // saving snapshot
    $I->saveSessionSnapshot('login');
}
