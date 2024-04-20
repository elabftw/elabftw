<?php

/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @copyright 2022 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */

declare(strict_types=1);

namespace Elabftw\Elabftw;

use Elabftw\Enums\DisplayMode;
use Elabftw\Enums\Entrypoint;
use Elabftw\Enums\Language;
use Elabftw\Enums\Orderby;
use Elabftw\Enums\PasswordComplexity;
use Elabftw\Enums\PdfFormat;
use Elabftw\Enums\Scope;
use Elabftw\Enums\Sort;
use Elabftw\Exceptions\ImproperActionException;
use Elabftw\Interfaces\ContentParamsInterface;
use Elabftw\Models\Config;
use Elabftw\Services\Check;
use Elabftw\Services\Filter;
use Elabftw\Services\PasswordValidator;

use function trim;

final class UserParams extends ContentParams implements ContentParamsInterface
{
    public function getContent(): string
    {
        return match ($this->target) {
            // checked in update
            'email' => trim($this->content),
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
            'password' => $this->validateAndHashPassword(),
            'orcid' => $this->filterOrcid(),
            'limit_nb' => (string) Check::limit((int) $this->content),
            'display_mode' => (DisplayMode::tryFrom($this->content) ?? DisplayMode::Normal)->value,
            'sort' => (Sort::tryFrom($this->content) ?? Sort::Desc)->value,
            'orderby' => (Orderby::tryFrom($this->content) ?? Orderby::Date)->value,
            'scope_experiments', 'scope_items', 'scope_experiments_templates' => (string) (Scope::tryFrom((int) $this->content) ?? Scope::Team)->value,
            'sc_create', 'sc_favorite', 'sc_todo', 'sc_edit', 'sc_search' => Filter::firstLetter($this->content),
            'is_sysadmin', 'uploads_layout', 'cjk_fonts', 'pdf_sig', 'use_markdown', 'use_isodate', 'inc_files_pdf', 'append_pdfs', 'disable_shortcuts', 'validated', 'notif_comment_created', 'notif_comment_created_email', 'notif_step_deadline', 'notif_step_deadline_email', 'notif_user_created', 'notif_user_created_email', 'notif_user_need_validation', 'notif_user_need_validation_email', 'notif_event_deleted', 'notif_event_deleted_email' => (string) Filter::toBinary($this->content),
            'lang' => (Language::tryFrom($this->content) ?? Language::EnglishGB)->value,
            'entrypoint' => (string) (Entrypoint::tryFrom((int) $this->content) ?? Entrypoint::Dashboard)->value,
            'default_read', 'default_write' => Check::visibility($this->content),
            'pdf_format' => (PdfFormat::tryFrom($this->content) ?? PdfFormat::A4)->value,
            default => throw new ImproperActionException('Invalid target for user update.'),
        };
    }

    private function validateAndHashPassword(): string
    {
        $Config = Config::getConfig();
        $min = (int) $Config->configArr['min_password_length'];
        $passwordComplexity = PasswordComplexity::from((int) $Config->configArr['password_complexity_requirement']);
        $PasswordValidator = new PasswordValidator($min, $passwordComplexity);
        $PasswordValidator->validate($this->content);

        return password_hash($this->content, PASSWORD_DEFAULT);
    }

    private function filterOrcid(): string
    {
        // first check basic structure
        // note: the input field should prevent any incorrect value from being submitted in the first place
        if (preg_match('/\d{4}-\d{4}-\d{4}-\d{3}[0-9X]/', $this->content) === 0) {
            throw new ImproperActionException('Incorrect orcid: invalid format.');
        }
        // now check the sum
        $baseNumbers = str_replace('-', '', substr($this->content, 0, -1));
        if (Check::digit($baseNumbers, $this->getChecksumFromOrcid($this->content)) === false) {
            throw new ImproperActionException('Invalid orcid: checksum failed.');
        }
        return $this->content;
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
