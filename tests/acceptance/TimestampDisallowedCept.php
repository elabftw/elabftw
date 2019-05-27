<?php declare(strict_types=1);
/**
 * @author Nicolas CARPi <nicolas.carpi@curie.fr>
 * @copyright 2012 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */
$I = new AcceptanceTester($scenario);
$I->wantTo('Timestamp an experiment (timestamping disallowed)');
testLogin($I);
$I->amOnPage('experiments.php?mode=view&id=1');
// not working FIXME
//$I->dontSeeElement('#confirmTimestamp');
