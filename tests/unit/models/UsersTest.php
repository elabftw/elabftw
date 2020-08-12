<?php declare(strict_types=1);

namespace Elabftw\Models;

use Elabftw\Maps\UserPreferences;

class UsersTest extends \PHPUnit\Framework\TestCase
{
    protected function setUp(): void
    {
        $this->Users= new Users(1, 1);
    }

    public function testPopulate()
    {
        $this->assertTrue(is_array($this->Users->userData));
    }

    public function testUpdatePreferences()
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
        );
        $Prefs = new UserPreferences((int) $this->Users->userData['userid']);
        $Prefs->hydrate($prefsArr);
        $Prefs->save();

        // reload from db
        $u = new Users(1, 1);
        $this->assertEquals($u->userData['limit_nb'], '12');
    }
}
