<?php declare(strict_types=1);
/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @copyright 2012 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */

namespace Elabftw\Models;

/**
 * An anonymous user is "logged in" in a team but doesn't have a userid
 */
final class AnonymousUser extends Users
{
    public function __construct(public int $team, private string $lang)
    {
        parent::__construct(null, $team);
        $this->fillUserData();
    }

    private function fillUserData(): void
    {
        $this->userData['team'] = $this->team;
        $this->userData['limit_nb'] = 15;
        $this->userData['display_size'] = 'lg';
        $this->userData['display_mode'] = 'it';
        $this->userData['orderby'] = 'date';
        $this->userData['sort'] = 'desc';
        $this->userData['sc_create'] = 'c';
        $this->userData['sc_edit'] = 'e';
        $this->userData['sc_submit'] = 's';
        $this->userData['sc_todo'] = 'w';
        $this->userData['show_team'] = 1;
        $this->userData['show_team_templates'] = 0;
        $this->userData['show_public'] = 0;
        $this->userData['fullname'] = 'Anon Ymous';
        $this->userData['is_admin'] = 0;
        $this->userData['is_sysadmin'] = 0;
        $this->userData['lang'] = $this->lang;
        $this->userData['use_isodate'] = '0';
        $this->userData['uploads_layout'] = '1';
        $this->userData['inc_files_pdf'] = '1';
        $this->userData['pdf_format'] = 'A4';
    }
}
