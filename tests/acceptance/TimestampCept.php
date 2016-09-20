<?php
$I = new AcceptanceTester($scenario);
$I->wantTo('Timestamp an experiment');
testLogin($I);
$I->amOnPage('experiments.php?mode=view&id=1');
$I->click('a.elab-tooltip:nth-child(8) > img:nth-child(2)');
$I->click('.ui-dialog-buttonset > button:nth-child(1)');
$I->wait(2);
$I->see('Experiment was timestamped');
