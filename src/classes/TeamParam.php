<?php

/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @copyright 2022 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */

declare(strict_types=1);

namespace Elabftw\Elabftw;

use Elabftw\Exceptions\ImproperActionException;
use Elabftw\Services\Check;

final class TeamParam extends ContentParams
{
    public function getContent(): mixed
    {
        return match ($this->target) {
            'name', 'orgid', 'link_name' => parent::getContent(),
            'announcement',
            'onboarding_email_subject',
            'onboarding_email_body' => $this->getNullableContent(),
            'common_template', 'common_template_md' => $this->getBody(),
            'user_create_tag',
            'force_exp_tpl',
            'do_force_canread',
            'do_force_canwrite',
            'visible',
            'onboarding_email_active' => parent::getBinary(),
            'link_href' => $this->getUrl(),
            'force_canread', 'force_canwrite' => Check::visibility($this->content),
            default => throw new ImproperActionException('Incorrect parameter for team.' . $this->target),
        };
    }

    private function getNullableContent(): ?string
    {
        if (empty($this->content)) {
            return null;
        }
        return parent::getContent();
    }
}
