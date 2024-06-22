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

use Elabftw\Enums\EntityType;
use Elabftw\Models\Notifications\CommentCreated;
use Elabftw\Models\Notifications\UserCreated;
use Elabftw\Models\Notifications\UserNeedValidation;

require_once dirname(__DIR__, 2) . '/vendor/autoload.php';

$Notifications = new CommentCreated(EntityType::Experiments->toPage(), 32, 2);
$Notifications->create(1);
$Notifications = new UserCreated(3, 'Some team name');
$Notifications->create(1);
$Notifications = new UserNeedValidation(3, 'Some team name');
$Notifications->create(1);
