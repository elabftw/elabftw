<?php

/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @author Marcel Bolten <github@marcelbolten.de>
 * @copyright 2024 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */

declare(strict_types=1);

namespace Elabftw\AuditEvent;

use Elabftw\Enums\AuditCategory;

class OnboardingEmailSent extends AbstractAuditEvent
{
    public function __construct(private int $teamId = 0, private int $targetUserid = 0, private bool $forAdmin = false)
    {
        parent::__construct(0, $targetUserid);
    }

    public function getBody(): string
    {
        $msg = 'Onboarding email for %s sent to %s with id %d.';

        // system email
        if ($this->teamId === -1) {
            return sprintf(
                $msg,
                'eLabFTW instance',
                $this->forAdmin ? 'admin' : 'user',
                $this->targetUserid
            );
        }

        // team email
        return sprintf(
            $msg,
            'team with id ' . (string) $this->teamId,
            'user',
            $this->targetUserid
        );
    }

    public function getCategory(): AuditCategory
    {
        return AuditCategory::OnboardingEmailSent;
    }
}
