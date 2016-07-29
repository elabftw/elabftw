<?php
session_start();
if (getenv('CIRCLECI')) {
    require_once 'tests/config-circleci.php';
} elseif (getenv('SHELL') == '/usr/bin/zsh') {
    require_once 'tests/config-home.php';
} else {
    require_once 'config.php';
}
