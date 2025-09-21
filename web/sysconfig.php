<?php

/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @copyright 2012 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */

declare(strict_types=1);

namespace Elabftw\Elabftw;

use Elabftw\Enums\AuditCategory;
use Elabftw\Enums\BasePermissions;
use Elabftw\Enums\EnforceMfa;
use Elabftw\Enums\PasswordComplexity;
use Elabftw\Exceptions\AppException;
use Elabftw\Exceptions\IllegalActionException;
use Elabftw\Models\AuditLogs;
use Elabftw\Models\AuthFail;
use Elabftw\Models\Experiments;
use Elabftw\Models\Idps;
use Elabftw\Models\IdpsSources;
use Elabftw\Models\Info;
use Elabftw\Models\StorageUnits;
use Elabftw\Services\DummyRemoteDirectory;
use Elabftw\Services\EairefRemoteDirectory;
use Elabftw\Services\UploadsChecker;
use Exception;
use GuzzleHttp\Client;
use PDO;
use Symfony\Component\HttpFoundation\Response;
use ValueError;

use function array_walk;

/**
 * Instance level settings and tools
 */
require_once 'app/init.inc.php';

$Response = new Response();
try {
    $Response->prepare($App->Request);

    if (!$App->Users->userData['is_sysadmin']) {
        throw new IllegalActionException('Non sysadmin user tried to access sysconfig panel.');
    }

    $AuthFail = new AuthFail();
    $Idps = new Idps($App->Users);
    $idpsArr = $Idps->readAllLight();
    $IdpsSources = new IdpsSources($App->Users);
    $idpsSources = $IdpsSources->readAll();
    $teamsArr = $App->Teams->readAllComplete();
    $Experiments = new Experiments($App->Users);

    // Remote directory search
    $remoteDirectoryUsersArr = array();
    if ($App->Request->query->has('remote_dir_query')) {
        if ($App->Config->configArr['remote_dir_service'] === 'eairef') {
            $RemoteDirectory = new EairefRemoteDirectory(new Client(), $App->Config->configArr['remote_dir_config']);
        } else {
            $RemoteDirectory = new DummyRemoteDirectory(new Client(), $App->Config->configArr['remote_dir_config']);
        }
        $remoteDirectoryUsersArr = $RemoteDirectory->search($App->Request->query->getString('remote_dir_query'));
        if (empty($remoteDirectoryUsersArr)) {
            $App->Session->getFlashBag()->add('warning', _('No users found. Try another search.'));
        }
    }

    $samlSecuritySettings = array(
        array('slug' => 'saml_nameidencrypted', 'label' => 'Encrypt the nameID of the samlp:logoutRequest sent by this SP (nameIdEncrypted)'),
        array('slug' => 'saml_authnrequestssigned', 'label' => 'Sign the samlp:AuthnRequest messages sent (authnRequestsSigned)'),
        array('slug' => 'saml_logoutrequestsigned', 'label' => 'Sign the samlp:logoutRequest messages sent (logoutRequestSigned)'),
        array('slug' => 'saml_logoutresponsesigned', 'label' => 'Sign the samlp:logoutResponse messages sent (logoutresponsesigned)'),
        array('slug' => 'saml_signmetadata', 'label' => 'Sign the metadata (signMetadata)'),
        array('slug' => 'saml_wantmessagessigned', 'label' => 'Require the samlp:Response to be signed (wantMessagesSigned)'),
        array('slug' => 'saml_wantassertionsencrypted', 'label' => 'Require the saml:Assertion to be encrypted (wantAssertionsEncrypted)'),
        array('slug' => 'saml_wantassertionssigned', 'label' => 'Require the saml:Assertion to be signed (wantAssertionsSigned)'),
        array('slug' => 'saml_wantnameid', 'label' => 'Require the NameID element on the SAMLResponse received (wantNameId)'),
        array('slug' => 'saml_wantnameidencrypted', 'label' => 'Require the NameID element received to be encrypted (wantNameIdEncrypted)'),
        array('slug' => 'saml_wantxmlvalidation', 'label' => 'Validate all received xmls (strict mode must be activated) (wantXMLValidation)'),
        array('slug' => 'saml_relaxdestinationvalidation', 'label' => 'SAMLResponse with an empty value as its Destination will not be rejected for this fact. (relaxDestinationValidation)'),
        array('slug' => 'saml_lowercaseurlencoding', 'label' => 'ADFS compatibility on signature verification (lowercaseUrlEncoding)'),
        array('slug' => 'saml_allowrepeatattributename', 'label' => 'Allow attribute elements with name duplicated'),
    );

    $phpInfos = array(
        PHP_OS,
        PHP_VERSION,
        PHP_INT_MAX,
        PHP_SYSCONFDIR,
        ini_get('upload_max_filesize'),
        ini_get('date.timezone'),
        Db::getConnection()->getAttribute(PDO::ATTR_SERVER_VERSION),
    );

    $elabimgVersion = getenv('ELABIMG_VERSION') ?: 'Not in Docker';
    $auditLogsArr = AuditLogs::read($App->Request->query->getInt('limit', AuditLogs::DEFAULT_LIMIT), $App->Request->query->getInt('offset'));
    array_walk($auditLogsArr, function (array &$event) {
        try {
            $event['category'] = AuditCategory::from($event['category'])->name;
        } catch (ValueError) {
        }
    });
    $passwordComplexity = PasswordComplexity::from((int) $App->Config->configArr['password_complexity_requirement']);
    $StorageUnits = new StorageUnits($App->Users, false);
    $template = 'sysconfig.html';
    $renderArr = array(
        'Request' => $App->Request,
        'auditLogsArr' => $auditLogsArr,
        'containersCount' => $StorageUnits->readCount(),
        'nologinUsersCount' => $AuthFail->getLockedUsersCount(),
        'lockoutDevicesCount' => $AuthFail->getLockoutDevicesCount(),
        'elabimgVersion' => $elabimgVersion,
        'idpsArr' => $idpsArr,
        'idpsSources' => $idpsSources,
        'pageTitle' => _('Instance settings'),
        'passwordInputHelp' => $passwordComplexity->toHuman(),
        'passwordInputPattern' => $passwordComplexity->toPattern(),
        'phpInfos' => $phpInfos,
        'remoteDirectoryUsersArr' => $remoteDirectoryUsersArr,
        'samlSecuritySettings' => $samlSecuritySettings,
        // disabled as we don't use getStats here now
        //'Teams' => $Teams,
        'teamsArr' => $teamsArr,
        'info' => new Info()->readAll(),
        'timestampLastMonth' => $Experiments->getTimestampLastMonth(),
        'uploadsStats' => UploadsChecker::getStats(),
        'enforceMfaArr' => EnforceMfa::getAssociativeArray(),
        'passwordComplexityArr' => PasswordComplexity::getAssociativeArray(),
        'permissions' => BasePermissions::cases(),
    );
    $Response->setContent($App->render($template, $renderArr));
} catch (AppException $e) {
    $Response = $e->getResponseFromException($App);
} catch (Exception $e) {
    $Response = $App->getResponseFromException($e);
} finally {
    $Response->send();
}
