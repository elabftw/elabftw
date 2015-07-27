<?php
require_once __DIR__ . '/vendor/autoload.php';

$app = new \Cilex\Application('Cilex');
//$app->command(new \Cilex\Command\GreetCommand());
$app->run();