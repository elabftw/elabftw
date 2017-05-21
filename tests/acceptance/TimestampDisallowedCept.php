<?php
$I = new AcceptanceTester($scenario);
$I->wantTo('Timestamp an experiment (timestamping disallowed)');
testLogin($I);
$I->amOnPage('experiments.php?mode=view&id=1');
$I->dontSeeElement('#confirmTimestamp');
