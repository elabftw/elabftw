<?php
/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @copyright 2012 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */
declare(strict_types=1);

namespace Elabftw\Elabftw;

use Elabftw\Services\Check;
use Elabftw\Services\Filter;

/**
 * Parameters passed for status/items types create/update
 *
 */
class ParamsProcessor
{
    public $name;

    public $color;

    public $isTimestampable;

    public $isDefault;

    public $id;

    public $template;

    public function __construct(array $params)
    {
        $this->name = Filter::sanitize($params['name'] ?? 'Unnamed');
        $this->color = Check::color($params['color'] ?? '#cccccc');
        $this->isTimestampable = $params['isTimestampable'] ?? 0 ? 1 : 0;
        $this->isDefault = $params['isDefault'] ?? 0 ? 1 : 0;
        $this->id = (int) ($params['id'] ?? 0);
        $this->template = Filter::body($params['template'] ?? '');
        $this->bookable = (int) ($params['bookable'] ?? 0) ? 1 : 0;
    }
}
