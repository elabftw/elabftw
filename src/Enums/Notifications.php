<?php

/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @copyright 2023 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */

declare(strict_types=1);

namespace Elabftw\Enums;

enum Notifications: int
{
    case CommentCreated = 10;
    case UserCreated = 11;
    case UserNeedValidation = 12;
    case StepDeadline = 13;
    case EventDeleted = 14;
    case SelfNeedValidation = 20;
    case SelfIsValidated = 30;
    case MathjaxFailed = 40;
    case PdfAppendmentFailed = 50;
    case PdfGenericError = 60;
    case NewVersionInstalled = 70;
    case OnboardingEmail = 80;
    case ActionRequested = 90;
}
