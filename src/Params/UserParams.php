<?php

/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @copyright 2022 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */

declare(strict_types=1);

namespace Elabftw\Params;

use Elabftw\Enums\DisplayMode;
use Elabftw\Enums\Entrypoint;
use Elabftw\Enums\Language;
use Elabftw\Enums\Orderby;
use Elabftw\Enums\PdfFormat;
use Elabftw\Enums\Scope;
use Elabftw\Enums\Sort;
use Elabftw\Enums\ThemeVariant;
use Elabftw\Enums\UsersColumn;
use Elabftw\Exceptions\ImproperActionException;
use Elabftw\Services\Check;
use Elabftw\Services\Filter;
use Override;

use function mb_substr;
use function preg_match;
use function str_replace;
use function strlen;

final class UserParams extends ContentParams
{
    #[Override]
    public function getContent(): string | int | null
    {
        return match ($this->target) {
            // checked in update
            UsersColumn::Email->value => Filter::sanitizeEmail($this->asString()),
            UsersColumn::Firstname->value,
            UsersColumn::Lastname->value => Filter::toPureString($this->asString()),
            UsersColumn::Orgid->value => $this->content,
            UsersColumn::ValidUntil->value => (
                function () {
                    // clicking the little cross on the input will send an empty string, so set a date far in the future instead
                    if (empty($this->content)) {
                        return '3000-01-01';
                    }
                    return $this->content;
                }
            )(),
            UsersColumn::Orcid->value => $this->filterOrcid($this->asString()),
            UsersColumn::Limit->value => (string) Check::limit($this->asInt()),
            UsersColumn::DisplayMode->value => (DisplayMode::tryFrom($this->content) ?? DisplayMode::Normal)->value,
            UsersColumn::Sort->value => (Sort::tryFrom($this->content) ?? Sort::Desc)->value,
            UsersColumn::Orderby->value => (Orderby::tryFrom($this->content) ?? Orderby::Date)->value,
            UsersColumn::ScopeExperimentsTemplates->value,
            UsersColumn::ScopeExperiments->value,
            UsersColumn::ScopeEvents->value,
            UsersColumn::ScopeItems->value,
            UsersColumn::ScopeItemsTypes->value,
            UsersColumn::ScopeTeamgroups->value => (string) (Scope::tryFrom($this->asInt()) ?? Scope::Team)->value,
            UsersColumn::ScCreate->value,
            UsersColumn::ScFavorite->value,
            UsersColumn::ScTodo->value,
            UsersColumn::ScEdit->value,
            UsersColumn::ScSearch->value => Filter::firstLetter($this->asString()),
            UsersColumn::AlwaysShowOwned->value,
            UsersColumn::AppendPdfs->value,
            UsersColumn::CjkFonts->value,
            UsersColumn::DisableShortcuts->value,
            UsersColumn::IncFilesPdf->value,
            UsersColumn::IsSysadmin->value,
            UsersColumn::CanManageUsers2teams->value,
            UsersColumn::CanManageCompounds->value,
            UsersColumn::CanManageInventoryLocations->value,
            UsersColumn::NotifCommentCreatedEmail->value,
            UsersColumn::NotifCommentCreated->value,
            UsersColumn::NotifEventDeletedEmail->value,
            UsersColumn::NotifEventDeleted->value,
            UsersColumn::NotifStepDeadlineEmail->value,
            UsersColumn::NotifStepDeadline->value,
            UsersColumn::NotifUserCreatedEmail->value,
            UsersColumn::NotifUserCreated->value,
            UsersColumn::NotifUserNeedValidationEmail->value,
            UsersColumn::NotifUserNeedValidation->value,
            UsersColumn::PdfSignature->value,
            UsersColumn::SchedulerLayout->value,
            UsersColumn::ShowWeekends->value,
            UsersColumn::UploadsLayout->value,
            UsersColumn::UseIsodate->value,
            UsersColumn::UseMarkdown->value,
            UsersColumn::Validated->value => (string) Filter::toBinary($this->content),
            UsersColumn::PrimaryBg->value,
            UsersColumn::PrimaryFg->value => Filter::nullableHexColor($this->getNullableString()),
            UsersColumn::ThemeVariant->value => (ThemeVariant::tryFrom($this->asInt()) ?? ThemeVariant::Auto)->value,
            UsersColumn::MfaSecret->value => $this->getNullableString(),
            UsersColumn::Lang->value => (Language::tryFrom($this->content) ?? Language::EnglishGB)->value,
            UsersColumn::Entrypoint->value => (Entrypoint::tryFrom($this->asInt()) ?? Entrypoint::Dashboard)->value,
            UsersColumn::DefaultRead->value,
            UsersColumn::DefaultWrite->value => $this->getCanJson(),
            UsersColumn::DefaultReadBase->value,
            UsersColumn::DefaultWriteBase->value => $this->getCanBase(),
            UsersColumn::PdfFormat->value => (PdfFormat::tryFrom($this->content) ?? PdfFormat::A4)->value,
            default => throw new ImproperActionException('Invalid target for user update.'),
        };
    }

    public function getStringContent(): string
    {
        return (string) $this->getContent();
    }

    private function filterOrcid(string $input): string
    {
        if (empty($input)) {
            return '';
        }
        // first check basic structure
        // note: the input field should prevent any incorrect value from being submitted in the first place
        if (preg_match('/\d{4}-\d{4}-\d{4}-\d{3}[0-9X]/', $input) === 0) {
            throw new ImproperActionException('Incorrect orcid: invalid format.');
        }
        // now check the sum
        $baseNumbers = str_replace('-', '', mb_substr($input, 0, -1));
        if (Check::digit($baseNumbers, $this->getChecksumFromOrcid($input)) === false) {
            throw new ImproperActionException('Invalid orcid: checksum failed.');
        }
        return $input;
    }

    private function getChecksumFromOrcid(string $orcid): int
    {
        // it is the last character
        $checksum = $orcid[strlen($orcid) - 1];
        // X means 10
        if ($checksum === 'X') {
            return 10;
        }
        return (int) $checksum;
    }
}
