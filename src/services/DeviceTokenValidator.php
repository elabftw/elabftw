<?php

/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @copyright 2012 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */

declare(strict_types=1);

namespace Elabftw\Services;

use Elabftw\Elabftw\Db;
use Lcobucci\JWT\Configuration;
use Lcobucci\JWT\Encoding\CannotDecodeContent;
use Lcobucci\JWT\Token\InvalidTokenStructure;
use Lcobucci\JWT\Validation\RequiredConstraintsViolated;

/**
 * Validate a device token
 */
class DeviceTokenValidator
{
    public function __construct(private Configuration $config, private string $deviceToken) {}

    public function validate(): bool
    {
        if (empty($this->deviceToken)) {
            return false;
        }
        $Db = Db::getConnection();
        try {
            $parsedToken = $this->config->parser()->parse($this->deviceToken);
            $this->config->validator()->assert($parsedToken, ...$this->config->validationConstraints());
            // also check if the device token is not in the locklist
            $sql = 'SELECT COUNT(id) FROM lockout_devices WHERE device_token = :device_token AND locked_at > (NOW() - INTERVAL 1 HOUR)';
            $req = $Db->prepare($sql);
            $req->bindParam(':device_token', $this->deviceToken);
            $req->execute();
            if ($req->fetchColumn() > 0) {
                return false;
            }
            // group all the possible exceptions into one because we don't really care the reason why the token might be invalid
        } catch (CannotDecodeContent | InvalidTokenStructure | RequiredConstraintsViolated) {
            return false;
        }
        return true;
    }
}
