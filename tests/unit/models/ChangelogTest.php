<?php

declare(strict_types=1);
/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @copyright 2023 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */

namespace Elabftw\Models;

use Elabftw\Elabftw\EntityParams;
use Elabftw\Enums\Action;

class ChangelogTest extends \PHPUnit\Framework\TestCase
{
    public function testCreate(): void
    {
        $Experiments = new Experiments(new Users(1, 1));
        $id = $Experiments->create();
        $Experiments->setId($id);
        $body = 'initial body';
        $Experiments->patch(Action::Update, array('body' => $body));

        $Changelog = new Changelog($Experiments);
        $params = new EntityParams('body', $body);
        $this->assertFalse($Changelog->create($params));
    }

    public function testReadAllWithAbsoluteUrls(): void
    {
        $Experiments = new Experiments(new Users(1, 1));
        $id = $Experiments->create();
        $Experiments->setId($id);
        $body = 'initial body';
        $Experiments->patch(Action::Update, array('body' => $body));
        $Changelog = new Changelog($Experiments);
        $this->assertIsArray($Changelog->readAllWithAbsoluteUrls());
    }
}
