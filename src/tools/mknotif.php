<?php declare(strict_types=1);
/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @copyright 2021 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */

namespace Elabftw\Elabftw;

use Elabftw\Models\Notifications;
use Elabftw\Models\Users;

require_once dirname(__DIR__, 2) . '/vendor/autoload.php';
require_once dirname(__DIR__, 2) . '/config.php';
$Notifications = new Notifications(new Users(1));
        $body = array(
            'experiment_id' => 32,
            'commenter_userid' => 2,
        );
$Notifications->create(new CreateNotificationParams(1, $body));
$Notifications->create(new CreateNotificationParams(2));
$Notifications->create(new CreateNotificationParams(3));
