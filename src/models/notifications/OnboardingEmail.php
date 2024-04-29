<?php

/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @author Marcel Bolten <github@marcelbolten.de>
 * @copyright 2023 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */

declare(strict_types=1);

namespace Elabftw\Models\Notifications;

use Elabftw\Enums\Notifications;
use Elabftw\Interfaces\MailableInterface;
use Elabftw\Models\Config;
use Elabftw\Models\Teams;
use Elabftw\Models\Users;

/**
 * When a new user joins
 */
class OnboardingEmail extends EmailOnlyNotifications implements MailableInterface
{
    protected Notifications $category = Notifications::OnboardingEmail;

    /**
     * @param int $teamId team id or -1 for onboarding email from system (sysadmin panel)
     */
    public function __construct(private int $teamId, private bool $forAdmin = false)
    {
        parent::__construct();
    }

    public function getEmail(): array
    {
        $dataArr = array(
            'onboarding_email_subject' => '',
            'onboarding_email_body' => '',
            'onboarding_email_admins_subject' => '',
            'onboarding_email_admins_body' => '',
        );

        if ($this->teamId > 0) {
            $Team = new Teams(new Users(), $this->teamId);
            $Team->bypassReadPermission = true;
            $dataArr = $Team->readOne();
        } elseif ($this->teamId === -1) {
            $dataArr = Config::getConfig()->configArr;
        }

        return array(
            'subject' => $this->teamId === -1 && $this->forAdmin
                ? $dataArr['onboarding_email_admins_subject']
                : $dataArr['onboarding_email_subject'],
            'body' => '',
            'htmlBody' => $this->teamId === -1 && $this->forAdmin
                ? $dataArr['onboarding_email_admins_body']
                : $dataArr['onboarding_email_body'],
            'team' => $this->teamId,
            'forAdmin' => $this->forAdmin,
        );
    }

    protected function getBody(): array
    {
        return array(
            'team' => $this->teamId,
            ...($this->forAdmin ? array('forAdmin' => $this->forAdmin) : array()),
        );
    }
}
