<?php declare(strict_types=1);
/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @copyright 2022 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */

namespace Elabftw\Elabftw;

use Elabftw\Exceptions\ImproperActionException;
use Elabftw\Services\Check;

final class TeamParam extends ContentParams
{
    public function getContent(): mixed
    {
        return match ($this->target) {
            'name', 'orgid', 'link_name' => parent::getContent(),
            'common_template', 'common_template_md' => $this->getBody(),
            'deletable_xp', 'deletable_item', 'user_create_tag', 'force_exp_tpl', 'public_db', 'do_force_canread', 'do_force_canwrite', 'visible' => parent::getBinary(),
            'link_href' => $this->getUrl(),
            'force_canread', 'force_canwrite' => Check::visibility($this->content),
            default => throw new ImproperActionException('Incorrect parameter for team.' . $this->target),
        };
    }
}
