<?php
session_start();
if (\getenv('CIRCLE_BUILD_URL')) {
    require_once 'tests/config-circleci.php';
} elseif (\getenv('USER') === 'scrutinizer') {
    require_once 'tests/config-scrutinizer.php';
} else {
    require_once 'tests/config-home.php';
}
