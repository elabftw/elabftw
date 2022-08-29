<?php declare(strict_types=1);
/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @copyright 2022 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */

namespace Elabftw\Elabftw;

use Elabftw\Enums\DisplayMode;
use Elabftw\Enums\DisplaySize;
use Elabftw\Enums\Language;
use Elabftw\Enums\Orderby;
use Elabftw\Enums\PdfFormat;
use Elabftw\Enums\Sort;
use Elabftw\Exceptions\ImproperActionException;
use Elabftw\Interfaces\ContentParamsInterface;
use Elabftw\Services\Check;
use Elabftw\Services\Filter;

final class UserParams extends ContentParams implements ContentParamsInterface
{
    public function __construct(string $target, string $content, private int $isSysadmin = 0)
    {
        parent::__construct($target, $content);
    }

    public function getContent(): string
    {
        return match ($this->target) {
            // checked in update
            'email' => $this->content,
            'firstname', 'lastname' => Filter::sanitize($this->content),
            'usergroup' => (string) $this->checkUserGroup((int) $this->content),
            // return the hash of the password
            'password' => password_hash(Check::passwordLength($this->content), PASSWORD_DEFAULT),
            'orcid' => $this->filterOrcid(),
            'limit_nb' => (string) Check::limit((int) $this->content),
            'display_size' => (DisplaySize::tryFrom($this->content) ?? DisplaySize::Large)->value,
            'display_mode' => (DisplayMode::tryFrom($this->content) ?? DisplayMode::Normal)->value,
            'sort' => (Sort::tryFrom($this->content) ?? Sort::Desc)->value,
            'orderby' => (Orderby::tryFrom($this->content) ?? Orderby::Date)->value,
            'sc_create', 'sc_submit', 'sc_todo', 'sc_edit' => Filter::firstLetter($this->content),
            'show_team', 'show_team_templates', 'show_public', 'single_column_layout', 'uploads_layout', 'cjk_fonts', 'pdfa', 'pdf_sig', 'use_markdown', 'use_isodate', 'inc_files_pdf', 'append_pdfs', 'validated' => (string) Filter::toBinary($this->content),
            'lang' => (Language::tryFrom($this->content) ?? Language::English)->value,
            'default_read', 'default_write' => Check::visibility($this->content),
            'pdf_format' => (PdfFormat::tryFrom($this->content) ?? PdfFormat::A4)->value,
            default => throw new ImproperActionException('Invalid target for user update.'),
        };
    }

    public function getColumn(): string
    {
        return match ($this->target) {
            'password' => 'password_hash',
            default => $this->target,
        };
    }

    private function checkUserGroup(int $usergroup): int
    {
        $usergroup = Check::usergroup($usergroup);
        if ($usergroup === 1 && $this->isSysadmin !== 1) {
            throw new ImproperActionException('Only a sysadmin can promote another user to sysadmin.');
        }
        // a non sysadmin cannot demote a sysadmin
        if ($usergroup !== 1 && $this->isSysadmin !== 1) {
            throw new ImproperActionException('Only a sysadmin can demote another sysadmin.');
        }
        return $usergroup;
    }

    private function filterOrcid(): string
    {
        if (preg_match('/\d{4}-\d{4}-\d{4}-\d{4}/', $this->content) === 1) {
            return $this->content;
        }
        // note: the input field should prevent any incorrect value from being submitted in the first place
        throw new ImproperActionException('Incorrect value for orcid.');
    }
}
