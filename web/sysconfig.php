<?php declare(strict_types=1);
/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @copyright 2012 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */

namespace Elabftw\Elabftw;

use Elabftw\Enums\AuditCategory;
use Elabftw\Enums\EnforceMfa;
use Elabftw\Enums\PasswordComplexity;
use Elabftw\Exceptions\IllegalActionException;
use Elabftw\Models\AuditLogs;
use Elabftw\Models\AuthFail;
use Elabftw\Models\Experiments;
use Elabftw\Models\Idps;
use Elabftw\Models\Info;
use Elabftw\Models\Teams;
use Elabftw\Services\DummyRemoteDirectory;
use Elabftw\Services\EairefRemoteDirectory;
use Elabftw\Services\UsersHelper;
use Exception;
use GuzzleHttp\Client;
use PDO;
use Symfony\Component\HttpFoundation\Response;

/**
 * Administrate elabftw install
 *
 */
require_once 'app/init.inc.php';
$App->pageTitle = _('eLabFTW Configuration');
/** @psalm-suppress UncaughtThrowInGlobalScope */
$Response = new Response();
$Response->prepare($App->Request);

$template = 'error.html';
$renderArr = array();

try {
    if (!$App->Users->userData['is_sysadmin']) {
        throw new IllegalActionException('Non sysadmin user tried to access sysconfig panel.');
    }

    $AuthFail = new AuthFail();
    $Idps = new Idps();
    $idpsArr = $Idps->readAll();
    $Teams = new Teams($App->Users);
    $teamsArr = $Teams->readAll();
    $Experiments = new Experiments($App->Users);

    // Users search
    $isSearching = false;
    $usersArr = array();
    if ($App->Request->query->has('q')) {
        $isSearching = true;
        $usersArr = $App->Users->readFromQuery(
            $App->Request->query->getString('q'),
            $App->Request->query->getInt('teamFilter'),
            $App->Request->query->getBoolean('includeArchived'),
            $App->Request->query->getBoolean('onlyAdmins'),
        );
        foreach ($usersArr as &$user) {
            $UsersHelper = new UsersHelper((int) $user['userid']);
            $user['teams'] = $UsersHelper->getTeamsFromUserid();
        }
        // further filter if userid is present
        if ($App->Request->query->has('userid')) {
            $usersArr = array_filter($usersArr, function ($u) use ($App) {
                return $u['userid'] === $App->Request->query->getInt('userid');
            });
        }
    }

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
            $App->warning[] = _('No users found. Try another search.');
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
    array_walk($auditLogsArr, function (&$event) {
        $event['category'] = AuditCategory::from($event['category'])->name;
    });
    $passwordComplexity = PasswordComplexity::from((int) $App->Config->configArr['password_complexity_requirement']);
    $template = 'sysconfig.html';
    $renderArr = array(
        'Request' => $App->Request,
        'auditLogsArr' => $auditLogsArr,
        'nologinUsersCount' => $AuthFail->getLockedUsersCount(),
        'lockoutDevicesCount' => $AuthFail->getLockoutDevicesCount(),
        'elabimgVersion' => $elabimgVersion,
        'idpsArr' => $idpsArr,
        'isSearching' => $isSearching,
        'passwordInputHelp' => $passwordComplexity->toHuman(),
        'passwordInputPattern' => $passwordComplexity->toPattern(),
        'phpInfos' => $phpInfos,
        'remoteDirectoryUsersArr' => $remoteDirectoryUsersArr,
        'samlSecuritySettings' => $samlSecuritySettings,
        'Teams' => $Teams,
        'teamsArr' => $teamsArr,
        'info' => (new Info())->readAll(),
        'timestampLastMonth' => $Experiments->getTimestampLastMonth(),
        'usersArr' => $usersArr,
        'enforceMfaArr' => EnforceMfa::getAssociativeArray(),
        'passwordComplexityArr' => PasswordComplexity::getAssociativeArray(),
    );
} catch (IllegalActionException $e) {
    $renderArr['error'] = Tools::error(true);
} catch (Exception $e) {
    $App->Log->error('', array(array('userid' => $App->Session->get('userid')), array('Exception' => $e)));
    $renderArr['error'] = $e->getMessage();
} finally {
    $Response->setContent($App->render($template, $renderArr));
    $Response->send();
}
