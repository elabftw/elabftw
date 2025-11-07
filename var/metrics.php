<?php

/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @copyright 2025 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */

declare(strict_types=1);

namespace Elabftw\Elabftw;

// This file is accessed by /metrics endpoint from nginx
require_once dirname(__DIR__) . '/vendor/autoload.php';

new OpenMetrics()
    ->getResponse()
    ->send();
