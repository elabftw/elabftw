<?php declare(strict_types=1);
/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @copyright 2012 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */

namespace Elabftw\Models;

use Elabftw\Maps\UserPreferences;

class UsersTest extends \PHPUnit\Framework\TestCase
{
    private Users $Users;

    protected function setUp(): void
    {
        $this->Users= new Users(1, 1);
    }

    public function testPopulate(): void
    {
        $this->assertTrue(is_array($this->Users->userData));
    }

    public function testUpdatePreferences(): void
    {
        $prefsArr = array(
            'limit_nb' => '12',
            'sc_create' => 'c',
            'sc_edit' => 'e',
            'sc_submit' => 's',
            'sc_todo' => 't',
            'show_team' => 'on',
            'chem_editor' => 'on',
            'json_editor' => 'on',
            'lang' => 'en_GB',
            'pdf_format' => 'A4',
            'default_vis' => 'organization',
            'display_size' => 'lg',
            'display_mode' => 'it',
        );
        $Prefs = new UserPreferences((int) $this->Users->userData['userid']);
        $Prefs->hydrate($prefsArr);
        $Prefs->save();

        // reload from db
        $u = new Users(1, 1);
        $this->assertEquals($u->userData['limit_nb'], '12');
    }
}
