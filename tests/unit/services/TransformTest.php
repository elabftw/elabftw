<?php

declare(strict_types=1);
/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @copyright 2012 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */

namespace Elabftw\Services;

use Elabftw\Elabftw\App;
use Elabftw\Enums\EntityType;
use Elabftw\Enums\Notifications;
use ValueError;

class TransformTest extends \PHPUnit\Framework\TestCase
{
    public function testCsrf(): void
    {
        $token = 'fake-token';
        $input = Transform::csrf($token);
        $this->assertEquals("<input type='hidden' name='csrf' value='$token' />", $input);
    }

    /**
     * @dataProvider notifProvider
     */
    public function testNotif(array $input, string $expected): void
    {
        $this->assertEquals($expected, Transform::notif($input));
    }

    public static function notifProvider(): array
    {
        return array(
            'CommentCreated' => array(
                array(
                    'category' => Notifications::CommentCreated->value,
                    'id' => 1,
                    'created_at' => 'DATE',
                    'body' => array('page' => 'experiments.php', 'entity_id' => 42),
                ),
                '<span data-action="ack-notif" data-id="1" data-href="experiments.php?mode=view&amp;id=42">'
                . 'New comment on your entry.'
                . '</span><br><span class="relative-moment" title="DATE"></span>',
            ),
            'EventDeleted' => array(
                array(
                    'category' => Notifications::EventDeleted->value,
                    'id' => 1,
                    'created_at' => 'DATE',
                    'body' => array('event' => array('item' => 42), 'actor' => 'John'),
                ),
                '<span data-action="ack-notif" data-id="1" data-href="scheduler.php?item=42">'
                . 'A booked slot was deleted from the scheduler. (John)'
                . '</span><br><span class="relative-moment" title="DATE"></span>',
            ),
            'UserCreated' => array(
                array('category' => Notifications::UserCreated->value, 'id' => 1, 'created_at' => 'DATE'),
                '<span data-action="ack-notif" data-id="1">'
                . 'New user added to your team'
                . '</span><br><span class="relative-moment" title="DATE"></span>',
            ),
            'UserNeedValidation' => array(
                array('category' => Notifications::UserNeedValidation->value, 'id' => 1, 'created_at' => 'DATE'),
                '<span data-action="ack-notif" data-id="1" data-href="admin.php">'
                . 'A user needs account validation.'
                . '</span><br><span class="relative-moment" title="DATE"></span>',
            ),
            'PdfGenericError' => array(
                array('category' => Notifications::PdfGenericError->value, 'id' => 1, 'created_at' => 'DATE'),
                '<span data-action="ack-notif" data-id="1">'
                . 'There was a problem during PDF creation.'
                . '</span><br><span class="relative-moment" title="DATE"></span>',
            ),
            'MathjaxFailed' => array(
                array(
                    'category' => Notifications::MathjaxFailed->value,
                    'id' => 1,
                    'created_at' => 'DATE',
                    'body' => array('entity_page' => EntityType::Experiments->toPage(), 'entity_id' => 2),
                ),
                '<span data-action="ack-notif" data-id="1" data-href="experiments.php?mode=view&amp;id=2">'
                . 'Tex rendering failed during PDF generation. The raw tex commands are retained but you might want to carefully check the generated PDF.'
                . '</span><br><span class="relative-moment" title="DATE"></span>',
            ),
            'PdfAppendmentFailed' => array(
                array(
                    'category' => Notifications::PdfAppendmentFailed->value,
                    'id' => 1,
                    'created_at' => 'DATE',
                    'body' => array('entity_page' => EntityType::Experiments->toPage(), 'entity_id' => 2, 'file_names' => 'file1.pdf'),
                ),
                '<span data-action="ack-notif" data-id="1" data-href="experiments.php?mode=view&amp;id=2">'
                . 'Some attached PDFs could not be appended. (file1.pdf)'
                . '</span><br><span class="relative-moment" title="DATE"></span>',
            ),
            'StepDeadline' => array(
                array(
                    'category' => Notifications::StepDeadline->value,
                    'id' => 1,
                    'created_at' => 'DATE',
                    'body' => array('entity_page' => 'experiments.php', 'entity_id' => 2, 'step_id' => 5),
                ),
                '<span data-action="ack-notif" data-id="1" data-href="experiments.php?mode=view&amp;id=2&amp;highlightstep=5#step_view_5">'
                . 'A step deadline is approaching.'
                . '</span><br><span class="relative-moment" title="DATE"></span>',
            ),
            'NewVersionInstalled' => array(
                array('category' => Notifications::NewVersionInstalled->value, 'created_at' => 'DATE'),
                '<a class="color-white" href="'
                . App::getWhatsnewLink(App::INSTALLED_VERSION_INT)
                . '" target="_blank">'
                . sprintf('A new eLabFTW version has been installed since your last visit.%sRead the release notes by clicking this message.', '<br>')
                . '</a><br><span class="relative-moment" title="DATE"></span>',
            ),
            'ActionRequested' => array(
                array(
                    'category' => Notifications::ActionRequested->value,
                    'id' => 1,
                    'created_at' => 'DATE',
                    'body' => array('entity_page' => 'experiments.php', 'entity_id' => 2, 'requester_fullname' => 'Alice', 'action' => 'approval'),
                ),
                '<span data-action="ack-notif" data-id="1" data-href="experiments.php?mode=view&amp;id=2">'
                . 'Alice has requested approval from you.'
                . '</span><br><span class="relative-moment" title="DATE"></span>',
            ),
        );
    }

    public function testNotifInvalidTypeThrows(): void
    {
        $this->expectException(ValueError::class);
        Transform::notif(array('category' => 999999, 'id' => 1, 'created_at' => 'DATE'));
    }
}
