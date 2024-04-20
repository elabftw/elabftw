<?php

/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @copyright 2021 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */

declare(strict_types=1);

namespace Elabftw\Elabftw;

use Elabftw\Exceptions\ImproperActionException;
use Elabftw\Traits\ProcessTrait;

/**
 * Trusted Timestamping (RFC3161) response object
 */
class TimestampResponse
{
    use ProcessTrait;

    public readonly string $dataPath;

    public readonly string $tokenPath;

    public function __construct()
    {
        $this->dataPath = FsTools::getCacheFile();
        $this->tokenPath = FsTools::getCacheFile();
    }

    public function getTimestampFromResponseFile(): string
    {
        if (!is_readable($this->tokenPath)) {
            throw new ImproperActionException('The token is not readable.');
        }

        $output = $this->runProcess(array(
            'openssl',
            'ts',
            '-reply',
            '-in',
            $this->tokenPath,
            '-text',
        ));

        /*
         * Format of output:
         *
         * Status info:
         *   Status: Granted.
         *   Status description: unspecified
         *   Failure info: unspecified
         *
         *   TST info:
         *   Version: 1
         *   Policy OID: 1.3.6.1.4.1.15819.5.2.2
         *   Hash Algorithm: sha256
         *   Message data:
         *       0000 - 3a 9a 6c 32 12 7f b0 c7-cd e0 c9 9e e2 66 be a9   :.l2.........f..
         *       0010 - 20 b9 b1 83 3d b1 7c 16-e4 ac b0 5f 43 bc 40 eb    ...=.|...._C.@.
         *   Serial number: 0xA7452417D851301981FA9A7CC2CF776B5934D3E5
         *   Time stamp: Apr 27 13:37:34.363 2015 GMT
         *   Accuracy: unspecified seconds, 0x01 millis, unspecified micros
         *   Ordering: yes
         *   Nonce: unspecified
         *   TSA: DirName:/CN=Universign Timestamping Unit 012/OU=0002 43912916400026/O=Cryptolog International/C=FR
         *   Extensions:
         */

        $matches = array();
        $lines = explode("\n", $output);
        foreach ($lines as $line) {
            if (preg_match("~^Time\sstamp\:\s(.*)~", $line, $matches)) {
                return $matches[1];
            }
        }
        throw new ImproperActionException('Could not get response time!');
    }
}
