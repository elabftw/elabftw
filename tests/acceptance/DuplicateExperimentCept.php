<?php
$I = new AcceptanceTester($scenario);
$I->wantTo('Test duplicating an experiment');
testLogin($I);
$I->amOnPage('experiments.php?mode=view&id=1');
$I->click('html.fontawesome-i2svg-active.fontawesome-i2svg-complete body section#container.container-fluid div#real_container section.item span.view-action-buttons a.elab-tooltip svg.svg-inline--fa.fa-copy.fa-w-14.clickable.duplicateItem');
$I->see('Experiments');
