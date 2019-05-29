<?php declare(strict_types=1);
/**
 * @author Nicolas CARPi <nicolas.carpi@curie.fr>
 * @copyright 2012 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */
$I = new AcceptanceTester($scenario);
$I->wantTo('Test duplicating a database item');
testLogin($I);
$I->amOnPage('/app/controllers/EntityController.php?duplicate=1&id=1&type=items');
$I->see('Tags');
