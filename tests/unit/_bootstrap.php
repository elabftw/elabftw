<?php declare(strict_types=1);
/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @copyright 2012 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */
use function getenv;

if (getenv('CIRCLE_BUILD_URL') || getenv('USER') === 'scrutinizer') {
    require_once 'tests/config-ci.php';
} else {
    require_once 'tests/config-home.php';
}
