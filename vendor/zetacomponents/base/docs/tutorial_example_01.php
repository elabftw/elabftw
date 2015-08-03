<?php
require_once 'tutorial_autoload.php';

ezcBase::addClassRepository( './repos', './repos/autoloads' );
$myVar1 = new erMyClass2();
$myVar1->toString();
$yourVar1 = new erYourClass1();
$yourVar1->toString();
?>
