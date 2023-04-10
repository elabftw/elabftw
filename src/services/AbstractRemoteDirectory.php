<?php declare(strict_types=1);
/**
 * @package   Elabftw\Elabftw
 * @author    Nicolas CARPi <nico-git@deltablot.email>
 * @copyright 2023 Nicolas CARPi
 * @license   https://www.gnu.org/licenses/agpl-3.0.html AGPL-3.0
 * @see       https://www.elabftw.net Official website
 */

namespace Elabftw\Services;

use Defuse\Crypto\Crypto;
use Defuse\Crypto\Key;
use Elabftw\Interfaces\RemoteDirectoryInterface;
use Elabftw\Models\Config;
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
        $decryptedConfig = Crypto::decrypt($jsonConfig, Key::loadFromAsciiSafeString(Config::fromEnv('SECRET_KEY')));
        $this->config = json_decode($decryptedConfig, true, 10, JSON_THROW_ON_ERROR);
    }
}
