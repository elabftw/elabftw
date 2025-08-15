<?php

/**
 * @package   Elabftw\Elabftw
 * @author    Nicolas CARPi <nico-git@deltablot.email>
 * @copyright 2023 Nicolas CARPi
 * @license   https://www.gnu.org/licenses/agpl-3.0.html AGPL-3.0
 * @see       https://www.elabftw.net Official website
 */

declare(strict_types=1);

namespace Elabftw\Services;

use Elabftw\Interfaces\RemoteDirectoryInterface;
use GuzzleHttp\ClientInterface;

/**
 * Search a remote directory for users that can be added to the local database.
 */
abstract class AbstractRemoteDirectory implements RemoteDirectoryInterface
{
    protected const METHOD = 'GET';

    protected array $config;

    public function __construct(protected ClientInterface $client, string $jsonConfig)
    {
        $this->config = json_decode($jsonConfig, true, 10, JSON_THROW_ON_ERROR);
    }
}
