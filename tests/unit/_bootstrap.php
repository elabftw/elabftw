<?php
session_start();
if (getenv('CIRCLECI')) {
    require_once 'tests/config.php';
} else {
    require_once 'config.php';
}
