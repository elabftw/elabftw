<?php declare(strict_types=1);
/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @copyright 2012 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */

namespace Elabftw\Services;

use Elabftw\Models\Notifications;

class TransformTest extends \PHPUnit\Framework\TestCase
{
    public function testPermission(): void
    {
        $this->assertEquals('Public', Transform::permission('public'));
        $this->assertEquals('Organization', Transform::permission('organization'));
        $this->assertEquals('Team', Transform::permission('team'));
        $this->assertEquals('Owner + Admin(s)', Transform::permission('user'));
        $this->assertEquals('Owner only', Transform::permission('useronly'));
        $this->assertEquals('An error occurred!', Transform::permission('user2'));
    }

    public function testCsrf(): void
    {
        $token = 'fake-token';
        $input = Transform::csrf($token);
        $this->assertEquals("<input type='hidden' name='csrf' value='$token' />", $input);
    }

    public function testNotifPdfGenericError(): void
    {
        $expected = '<span class="clickable" data-action="ack-notif" data-id="1">';
        $expected .= 'There was a problem during PDF creation.';
        $expected .= '</span><br><span class="relative-moment" title="test"></span>';
        $actual = Transform::notif(array(
            'category' => Notifications::PDF_GENERIC_ERROR,
            'id' => '1',
            'created_at' => 'test',
        ));
        $this->assertEquals($expected, $actual);
    }

    public function testNotifMathJaxFailed(): void
    {
        $expected = '<span class="clickable" data-action="ack-notif" data-id="1" data-href="experiment.php?mode=view&id=2">';
        $expected .= 'Tex rendering failed during PDF generation. The raw tex commands are retained but you might want to carefully check the generated PDF.';
        $expected .= '</span><br><span class="relative-moment" title="test"></span>';
        $actual = Transform::notif(array(
            'category' => Notifications::MATHJAX_FAILED,
            'id' => '1',
            'created_at' => 'test',
            'body' => array(
                'entity_page' => 'experiment',
                'entity_id' => '2',
            ),
        ));
        $this->assertEquals($expected, $actual);
    }

    public function testNotifPdfAppendmentFailed(): void
    {
        $expected = '<span class="clickable" data-action="ack-notif" data-id="1" data-href="experiment.php?mode=view&id=2">';
        $expected .= 'Some attached PDFs could not be appended. (file1.pdf, file2.pdf)';
        $expected .= '</span><br><span class="relative-moment" title="TIMESTAMP"></span>';
        $actual = Transform::notif(array(
            'category' => Notifications::PDF_APPENDMENT_FAILED,
            'id' => '1',
            'created_at' => 'TIMESTAMP',
            'body' => array(
                'entity_page' => 'experiment',
                'entity_id' => '2',
                'file_names' => 'file1.pdf, file2.pdf',
            ),
        ));
        $this->assertEquals($expected, $actual);
    }
}
