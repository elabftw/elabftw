<?php

/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @copyright 2023 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */

declare(strict_types=1);

namespace Elabftw\Factories;

use Elabftw\Enums\EmailTarget;
use Elabftw\Enums\EntityType;
use Elabftw\Enums\Notifications;
use Elabftw\Enums\RequestableAction;
use Elabftw\Exceptions\ImproperActionException;
use Elabftw\Interfaces\MailableInterface;
use Elabftw\Models\Notifications\ActionRequested;
use Elabftw\Models\Notifications\CommentCreated;
use Elabftw\Models\Notifications\EventDeleted;
use Elabftw\Models\Notifications\OnboardingEmail;
use Elabftw\Models\Notifications\SelfIsValidated;
use Elabftw\Models\Notifications\SelfNeedValidation;
use Elabftw\Models\Notifications\StepDeadline;
use Elabftw\Models\Notifications\UserCreated;
use Elabftw\Models\Notifications\UserNeedValidation;
use Elabftw\Models\Users\Users;

/**
 * Get a Notification instance based on data from sql row
 */
final class NotificationsFactory
{
    private array $body;

    public function __construct(private int $category, string $jsonBody)
    {
        $this->body = json_decode($jsonBody, true, 10, JSON_THROW_ON_ERROR);
    }

    public function getMailable(): MailableInterface
    {
        return match (Notifications::from($this->category)) {
            Notifications::CommentCreated => new CommentCreated($this->body['page'], $this->body['entity_id'], $this->body['commenter_userid']),
            Notifications::UserCreated => new UserCreated($this->body['userid'], $this->body['team']),
            Notifications::UserNeedValidation => new UserNeedValidation($this->body['userid'], $this->body['team']),
            Notifications::StepDeadline => new StepDeadline($this->body['step_id'], $this->body['entity_id'], $this->body['entity_page'], $this->body['deadline']),
            Notifications::EventDeleted => new EventDeleted($this->body['event'], $this->body['actor'], $this->body['msg'], EmailTarget::from($this->body['target'])),
            Notifications::SelfNeedValidation => new SelfNeedValidation(),
            Notifications::SelfIsValidated => new SelfIsValidated(),
            Notifications::OnboardingEmail => new OnboardingEmail($this->body['team'], $this->body['forAdmin'] ?? false),
            // note: not sure why the bypassReadPermission is necessary here...
            Notifications::ActionRequested => new ActionRequested(new Users($this->body['requester_userid']), RequestableAction::from($this->body['action_enum_value']), EntityType::from($this->body['entity_type_value'])->toInstance(new Users($this->body['requester_userid']), $this->body['entity_id'], bypassReadPermission: true)),
            default => throw new ImproperActionException(sprintf('This notification (%d) is not mailable.', $this->category)),
        };
    }
}
