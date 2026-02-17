<?php

/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @copyright 2021 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */

declare(strict_types=1);

namespace Elabftw\Elabftw;

use DateTimeImmutable;
use Elabftw\Enums\EntityType;
use Elabftw\Models\Notifications\CommentCreated;
use Elabftw\Models\Notifications\StepDeadline;
use Elabftw\Models\Notifications\UserCreated;
use Elabftw\Models\Notifications\UserNeedValidation;
use Elabftw\Models\Users\Users;

require_once dirname(__DIR__, 2) . '/vendor/autoload.php';

$user = new Users(1);
$Notifications = new CommentCreated(EntityType::Experiments->toPage(), 32, 2, $user);
$Notifications->create();
$Notifications = new UserCreated(3, 'Some team name', $user);
$Notifications->create();
$Notifications = new UserNeedValidation(3, 'Some team name', $user);
$Notifications->create();
$d = new DateTimeImmutable();
$Notifications = new StepDeadline(1, 1, EntityType::Items->toPage(), $d->format('Y-m-d H:i:s'), $user);
$Notifications->create();
