<?php
namespace Elabftw\Elabftw;

use PDO;

class UsersTest extends \PHPUnit_Framework_TestCase
{
    protected function setUp()
    {
        $this->Users= new Users(1);
    }

    public function testPopulate()
    {
        $this->assertTrue(is_array($this->Users->userData));
    }

    public function testUpdatePreferences()
    {
        $prefsArr = array(
            'limit' => 12,
            'sc_create' => 'c',
            'sc_edit' => 'e',
            'sc_submit' => 's',
            'sc_todo' => 't',
            'show_team' => 'on',
            'close_warning' => 'on',
            'chem_editor' => 'on',
            'lang' => 'en_GB',
            'default_vis' => 'organization'
        );
        $this->assertTrue($this->Users->updatePreferences($prefsArr));
    }
}
