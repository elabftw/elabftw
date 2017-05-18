<?php
$I = new AcceptanceTester($scenario);
$I->wantTo('Timestamp an experiment (timestamping allowed)');
testLogin($I);
$I->amOnPage('experiments.php?mode=edit&id=1');
$I->selectOption('Status', 'Success');
$I->waitForElement('.overlay', 5);
$I->see('Saved');
$I->click('Save and go back');
$I->waitForElement('#confirmTimestamp', 5);
$I->click('a.elab-tooltip:nth-child(8) > img:nth-child(2)');
$I->click('body > div.ui-dialog.ui-corner-all.ui-widget.ui-widget-content.ui-front.ui-dialog-buttons.ui-draggable > div.ui-dialog-buttonpane.ui-widget-content.ui-helper-clearfix > div > button:nth-child(1)');
$I->wait(2);
$I->see('Experiment was timestamped');
