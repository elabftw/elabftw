<?php declare(strict_types=1);
/**
 * @author Nicolas CARPi <nicolas.carpi@curie.fr>
 * @copyright 2012 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */
session_start();
if (\getenv('CIRCLE_BUILD_URL')) {
    require_once 'tests/config-circleci.php';
} elseif (\getenv('USER') === 'scrutinizer') {
    require_once 'tests/config-scrutinizer.php';
} else {
    require_once 'tests/config-home.php';
}
