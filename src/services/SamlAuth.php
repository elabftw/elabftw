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
use Elabftw\Elabftw\Saml;
use Elabftw\Elabftw\Tools;
use Elabftw\Exceptions\ImproperActionException;
use Elabftw\Exceptions\ResourceNotFoundException;
use Elabftw\Exceptions\UnauthorizedException;
use Elabftw\Interfaces\AuthInterface;
use Elabftw\Models\Config;
use Elabftw\Models\Idps;
use Elabftw\Models\Teams;
use Elabftw\Models\Users;
use function is_array;
use OneLogin\Saml2\Auth as SamlAuthLib;

/**
 * SAML auth service
 */
class SamlAuth implements AuthInterface
{
    /** @var Saml $Saml*/
    private $Saml;

    /** @var array $settings saml settings for a particular idp */
    private $settings;

    /** @var SamlAuthLib $SamlAuthLib */
    private $SamlAuthLib;

    public function __construct(Config $config, Idps $idps, ?int $idpId = null)
    {
        $this->Saml = new Saml($config, $idps);
        $this->settings = $this->Saml->getSettings($idpId);
        $this->SamlAuthLib = new SamlAuthLib($this->settings);
    }

    public function tryAuth(): AuthResponse
    {
        $returnUrl = $this->settings['baseurl'] . '/index.php?acs';
        $this->SamlAuthLib->login($returnUrl);
        return new AuthResponse();
    }

    public function assertIdpResponse(): AuthResponse
    {
        $this->SamlAuthLib->processResponse();
        $errors = $this->SamlAuthLib->getErrors();

        // Display the errors if we are in debug mode
        if (!empty($errors) && $this->Saml->Config->configArr['debug']) {
            echo 'Something went wrong with SAML auth:<br>';
            echo Tools::printArr($errors);
            throw new UnauthorizedException('Authentication with IDP failed!');
        }

        if (!$this->SamlAuthLib->isAuthenticated()) {
            throw new UnauthorizedException('Authentication with IDP failed!');
        }

        // get the user information sent by IDP
        $samlUserdata = $this->SamlAuthLib->getAttributes();

        // GET EMAIL
        $email = $this->getEmail($samlUserdata);
        // GET TEAMS
        $teams = $this->getTeams($samlUserdata);
        // GET USERID FROM EMAIL
        $Users = $this->getUsers($email, $samlUserdata);

        // synchronize the teams from the IDP
        // because teams can change since the time the user was created
        if ($this->Saml->Config->configArr['saml_sync_teams']) {
            $Teams = new Teams($Users);
            $Teams->syncFromIdp($userid, $teams);
        }

        $AuthResponse = new AuthResponse();
        $AuthResponse->userid = (int) $Users->userData['userid'];
        $AuthResponse->mfaSecret = $Users->userData['mfa_secret'];
        $AuthResponse->setTeams();

        return $AuthResponse;
    }

    private function getEmail(array $samlUserdata): string
    {
        $email = $samlUserdata[$this->Saml->Config->configArr['saml_email']];

        if (is_array($email)) {
            $email = $email[0];
        }

        if ($email === null) {
            throw new ImproperActionException('Could not find email in response from IDP! Aborting.');
        }
        return $email;
    }

    private function getTeams(array $samlUserdata): array
    {
        $teams = $samlUserdata[$this->Saml->Config->configArr['saml_team']];

        // if no team attribute is sent by the IDP, use the default team
        if (empty($teams)) {
            // we directly get the id from the stored config
            $teamId = (int) $this->Saml->Config->configArr['saml_team_default'];
            if ($teamId === 0) {
                throw new ImproperActionException('Could not find team ID to assign user!');
            }
            $teams = array((string) $teamId);
        }

        // several teams can be returned by the IDP
        // or one but with ',' inside and we'll split on that
        if (count($teams) === 1) {
            $teams = explode(',', $teams[0]);
        }
    }

    private function getUsers(string $email, array $samlUserdata): Users
    {
        $Users = new Users();
        // user might not exist yet and populateFromEmail() will throw a ResourceNotFoundException
        try {
            $Users->populateFromEmail($email);
        } catch (ResourceNotFoundException $e) {
            // the user doesn't exist yet in the db

            // GET FIRSTNAME AND LASTNAME
            $firstname = $samlUserdata[$this->Saml->Config->configArr['saml_firstname']];
            if (is_array($firstname)) {
                $firstname = $firstname[0];
            }
            $lastname = $samlUserdata[$this->Saml->Config->configArr['saml_lastname']];
            if (is_array($lastname)) {
                $lastname = $lastname[0];
            }

            // CREATE USER (and force validation of user)
            $Users = new Users($Users->create($email, $teams, $firstname, $lastname, '', null, true));
        }
        return $Users;
    }
}
