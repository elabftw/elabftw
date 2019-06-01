<?php declare(strict_types=1);

namespace Elabftw\Models;

class UsersTest extends \PHPUnit\Framework\TestCase
{
    protected function setUp(): void
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
            'pdf_format' => 'A4',
            'default_vis' => 'organization',
        );
        $this->Users->updatePreferences($prefsArr);
    }
}
