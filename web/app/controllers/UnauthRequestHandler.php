<?php

declare(strict_types=1);
/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @copyright 2012 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */

namespace Elabftw\Elabftw;

use Elabftw\Enums\Language;
use Symfony\Component\HttpFoundation\JsonResponse;

use function dirname;

require_once dirname(__DIR__) . '/init.inc.php';

// this is here so privacy policy can be called from ajax without being auth
$Response = new JsonResponse();
// also deal with setting lang in session with a GET ?lang=fr_FR
if ($App->Request->query->has('lang')) {
    $lang = Language::tryFrom($App->Request->query->getString('lang')) ?? Language::EnglishGB;
    $App->Session->set('lang', $lang->value);
}
$Response->setData(array(
    'privacy' => $App->Config->configArr['privacy_policy'],
    'privacy_name' => $App->Config->configArr['privacy_policy_name'],
    'tos' => $App->Config->configArr['terms_of_service'],
    'tos_name' => $App->Config->configArr['terms_of_service_name'],
    'a11y' => $App->Config->configArr['a11y_statement'],
    'a11y_name' => $App->Config->configArr['a11y_statement_name'],
    'legal' => $App->Config->configArr['legal_notice'],
    'legal_name' => $App->Config->configArr['legal_notice_name'],
));
$Response->send();
