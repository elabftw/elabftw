<?php

/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @copyright 2022 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */

declare(strict_types=1);

namespace Elabftw\Params;

use Elabftw\Exceptions\ImproperActionException;
use Elabftw\Services\Filter;
use Override;

final class TeamParam extends ContentParams
{
    #[Override]
    public function getContent(): mixed
    {
        return match ($this->target) {
            'name', 'orgid' => parent::getContent(),
            'announcement', 'newcomer_banner',
            'onboarding_email_subject',
            'onboarding_email_body' => $this->getNullableContent(),
            'user_create_tag',
            'force_exp_tpl',
            'users_canwrite_experiments_categories',
            'users_canwrite_experiments_status',
            'users_canwrite_resources_categories',
            'users_canwrite_resources_status',
            'visible',
            'newcomer_banner_active',
            'onboarding_email_active' => $this->getBinary(),
            'newcomer_threshold' => $this->asInt(),
            default => throw new ImproperActionException('Incorrect parameter for team.' . $this->target),
        };
    }

    private function getNullableContent(): ?string
    {
        if (empty($this->content)) {
            return null;
        }
        return Filter::body(parent::getContent());
    }
}
