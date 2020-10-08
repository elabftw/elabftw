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
    public $name = 'Unnamed';

    public $color = '#cccccc';

    public $isTimestampable = 0;

    public $isDefault = 0;

    public $id = 0;

    public $template = '';

    public $tag = 'blah';

    public $comment = 'blah';

    public $bookable = 0;

    public function __construct(array $params)
    {
        $this->name = Filter::sanitize($params['name'] ?? $this->name);
        $this->tag = Filter::tag($params['tag'] ?? $this->tag);
        $this->color = Check::color($params['color'] ?? $this->color);
        $this->isTimestampable = $params['isTimestampable'] ?? $this->isTimestampable ? 1 : 0;
        $this->isDefault = $params['isDefault'] ?? $this->isDefault ? 1 : 0;
        $this->id = (int) ($params['id'] ?? $this->id);
        // TODO rename to body?
        $this->template = Filter::body($params['template'] ?? $this->template);
        $this->bookable = $params['bookable'] ?? $this->bookable ? 1 : 0;
        $this->comment = Filter::comment($params['comment'] ?? $this->comment);
    }
}
