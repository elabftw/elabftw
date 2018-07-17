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
    $I->submitForm('#login', ['email' => 'phpunit@yopmail.com', 'password' => 'phpunitftw']);
    // saving snapshot
    $I->saveSessionSnapshot('login');
}
