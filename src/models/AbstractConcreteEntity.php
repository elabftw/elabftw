<?php declare(strict_types=1);
/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @copyright 2022 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */

namespace Elabftw\Models;

use Elabftw\Enums\Action;
use Elabftw\Exceptions\ImproperActionException;
use Elabftw\Interfaces\CreateFromTemplateInterface;
use Elabftw\Make\MakeBloxberg;
use GuzzleHttp\Client;

/**
 * An entity like Experiments or Items. Concrete as opposed to TemplateEntity for experiments templates or items types
 */
abstract class AbstractConcreteEntity extends AbstractEntity implements CreateFromTemplateInterface
{
    public function postAction(Action $action, array $reqBody): int
    {
        return match ($action) {
            Action::Create => $this->create((int) ($reqBody['category_id'] ?? -1), $reqBody['tags'] ?? array()),
            Action::Duplicate => $this->duplicate(),
            default => throw new ImproperActionException('Invalid action parameter.'),
        };
    }

    public function patch(Action $action, array $params): array
    {
        return match ($action) {
            Action::Bloxberg => $this->bloxberg(),
            default => parent::patch($action, $params),
        };
    }

    protected function bloxberg(): array
    {
        $Config = Config::getConfig();
        $config = $Config->configArr;
        if ($config['blox_enabled'] !== '1') {
            throw new ImproperActionException('Bloxberg timestamping is disabled on this instance.');
        }
        (new MakeBloxberg(new Client(), $this))->timestamp();
        return $this->readOne();
    }
}
