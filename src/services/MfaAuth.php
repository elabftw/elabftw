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

use Elabftw\Elabftw\AuthResponse;
use Elabftw\Exceptions\InvalidCredentialsException;
use Elabftw\Interfaces\AuthInterface;
use Elabftw\Models\Users;

/**
 * Multi Factor Auth service
 */
class MfaAuth implements AuthInterface
{
    /** @var MfaHelper $MfaHelper */
    private $MfaHelper;

    /** @var string $code */
    private $code;

    public function __construct(MfaHelper $mfa, string $code)
    {
        $this->MfaHelper = $mfa;
        $this->code = $code;
    }

    public function tryAuth(): AuthResponse
    {
        $UsersHelper = new UsersHelper();
        $Users = new Users($this->MfaHelper->userid);

        if (!$this->MfaHelper->verifyCode($this->code)) {
            throw new InvalidCredentialsException('The code you entered is not valid!');
        }

        $AuthResponse = new AuthResponse();
        $AuthResponse->isAuthenticated = true;
        $AuthResponse->hasVerifiedMfa = true;
        $AuthResponse->mfaSecret = $Users->userData['mfa_secret'];

        $AuthResponse->selectableTeams = $UsersHelper->getTeamsFromUserid($this->MfaHelper->userid);
        $AuthResponse->userid = $this->MfaHelper->userid;

        // if the user only has access to one team, use this one directly
        if (count($AuthResponse->selectableTeams) === 1) {
            $AuthResponse->selectedTeam = (int) $AuthResponse->selectableTeams[0]['id'];
        }
        return $AuthResponse;
    }
}
