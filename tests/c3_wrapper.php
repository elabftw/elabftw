<?php

declare(strict_types=1);
/**
 * @author Marcel Bolten <github@marcelbolten.de>
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @copyright 2023 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 *
 * This file is only used in test runs and will be included via auto_prepend_file.
 * See elabtmp.Dockerfile
 * Loading c3 this way ensures that the line coverage of all files is correctly
 * annotated and no lines are missed which would happen in the files upstream of
 * the file where c3.php is included. E.g. web/*.php web/app/init.inc.php
 */

if (PHP_SAPI === 'fpm-fcgi') {
    require_once dirname(__DIR__) . '/vendor/autoload.php';

    if (file_exists(dirname(__DIR__) . '/c3.php')) {
        require_once dirname(__DIR__) . '/c3.php';
    }
}
