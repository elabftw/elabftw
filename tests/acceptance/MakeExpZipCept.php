<?php declare(strict_types=1);
/**
 * @author Nicolas CARPi <nicolas.carpi@curie.fr>
 * @copyright 2012 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */
$I = new AcceptanceTester($scenario);
$I->wantTo('Test creating a zip archive from an experiment');
testLogin($I);
$I->amOnPage('/make.php?what=zip&id=1&type=experiments');
$I->wait(2);
$I->wantTo('Test creating a zip archive from a database item');
$I->amOnPage('/make.php?what=zip&id=1&type=items');
$I->wait(2);

$folder = $_SERVER['HOME'].'/Downloads/';
$files = scandir($folder);
foreach ($files as $file) {
    if (preg_match('/^elabftw-export/', $file)) {
        $I->seeFileIsZip($folder . $file);
    }
}
