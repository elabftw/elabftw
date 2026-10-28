<?php

/**
 * @authors   Nicolas CARPi <nico-git@deltablot.email>
 *            Elsa TOUMA <elsa@deltablot.email>
 * @copyright 2024 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */

declare(strict_types=1);

namespace Elabftw\Factories;

use Elabftw\Elabftw\Env;
use Elabftw\Interfaces\CompoundInterface;
use Elabftw\Models\Compounds;
use Elabftw\Models\Config;
use Elabftw\Models\Users\Users;
use Elabftw\Services\HttpGetter;
use Elabftw\Services\OpenBabelFingerprinter;
use GuzzleHttp\Client;

final class CompoundsFactory
{
    public static function create(Users $requester): CompoundInterface
    {
        $Config = Config::getConfig();
        $httpGetter = new HttpGetter(
            new Client(),
            $Config->configArr['proxy'],
            !Env::asBool('DEV_MODE'),
        );

        return new Compounds(
            $httpGetter,
            $requester,
            new OpenBabelFingerprinter(),
        );
    }
}
