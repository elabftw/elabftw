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
use Elabftw\Enums\PasswordComplexity;
use Elabftw\Enums\PdfFormat;
use Elabftw\Enums\Scope;
use Elabftw\Enums\Sort;
use Elabftw\Exceptions\ImproperActionException;
use Elabftw\Models\Config;
use Elabftw\Services\Check;
use Elabftw\Services\Filter;
use Elabftw\Services\PasswordValidator;
use Override;

use function trim;

final class UserParams extends ContentParams
{
    #[Override]
    public function getContent(): string | int
    {
        return match ($this->target) {
            // checked in update
            'email' => trim($this->asString()),
            'firstname', 'lastname', 'orgid' => $this->content,
            'valid_until' => (
                function () {
                    // clicking the little cross on the input will send an empty string, so set a date far in the future instead
                    if (empty($this->content)) {
                        return '3000-01-01';
                    }
                    return $this->content;
                }
            )(),
            // return the hash of the password
            'password' => $this->validateAndHashPassword($this->asString()),
            'orcid' => $this->filterOrcid($this->asString()),
            'limit_nb' => (string) Check::limit($this->asInt()),
            'display_mode' => (DisplayMode::tryFrom($this->content) ?? DisplayMode::Normal)->value,
            'sort' => (Sort::tryFrom($this->content) ?? Sort::Desc)->value,
            'orderby' => (Orderby::tryFrom($this->content) ?? Orderby::Date)->value,
            'scope_experiments_templates',
            'scope_experiments',
            'scope_items',
            'scope_teamgroups' => (string) (Scope::tryFrom($this->asInt()) ?? Scope::Team)->value,
            'sc_create', 'sc_favorite', 'sc_todo', 'sc_edit', 'sc_search' => Filter::firstLetter($this->asString()),
            'always_show_owned',
            'append_pdfs',
            'cjk_fonts',
            'disable_shortcuts',
            'enforce_exclusive_edit_mode',
            'inc_files_pdf',
            'is_sysadmin',
            'notif_comment_created_email',
            'notif_comment_created',
            'notif_event_deleted_email',
            'notif_event_deleted',
            'notif_step_deadline_email',
            'notif_step_deadline',
            'notif_user_created_email',
            'notif_user_created',
            'notif_user_need_validation_email',
            'notif_user_need_validation',
            'pdf_sig',
            'scheduler_layout',
            'show_weekends',
            'uploads_layout',
            'use_isodate',
            'use_markdown',
            'validated' => (string) Filter::toBinary($this->content),
            'lang' => (Language::tryFrom($this->content) ?? Language::EnglishGB)->value,
            'entrypoint' => (Entrypoint::tryFrom($this->asInt()) ?? Entrypoint::Dashboard)->value,
            'default_read', 'default_write' => $this->getCanJson(),
            'pdf_format' => (PdfFormat::tryFrom($this->content) ?? PdfFormat::A4)->value,
            default => throw new ImproperActionException('Invalid target for user update.'),
        };
    }

    public function getStringContent(): string
    {
        return (string) $this->getContent();
    }

    private function validateAndHashPassword(string $password): string
    {
        $Config = Config::getConfig();
        $min = (int) $Config->configArr['min_password_length'];
        $passwordComplexity = PasswordComplexity::from((int) $Config->configArr['password_complexity_requirement']);
        $PasswordValidator = new PasswordValidator($min, $passwordComplexity);
        $PasswordValidator->validate($password);

        return password_hash($password, PASSWORD_DEFAULT);
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
        $baseNumbers = str_replace('-', '', substr($input, 0, -1));
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
