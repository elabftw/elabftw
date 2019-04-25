<?php
use \Codeception\Util\HttpCode;
$I = new AcceptanceTester($scenario);
$I->wantTo('Test creating a CSV file');
testLogin($I);
$I->amOnPage('/make.php?what=csv&id=1&type=experiments');
//$I->seeHttpHeader('Content-Type', 'text-csv; charset=UTF-8');
//$I->seeResponseCodeIs(HttpCode::OK);
