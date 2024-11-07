<?php

/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @author Alexander Minges <alexander.minges@uni-due.de>
 * @copyright 2024 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */

declare(strict_types=1);

namespace Elabftw\Storage;

/**
 * For local export folder, used to store user exports of experiments and resources
 */
class Exports extends Local
{
    protected const string FOLDER = 'exports';
}
