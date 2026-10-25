<?php

declare(strict_types=1);

namespace Elabftw\Models;

use Elabftw\Controllers\Apiv2Controller;
use Elabftw\Enums\Action;
use Elabftw\Enums\EntityType;
use Elabftw\Exceptions\ForbiddenException;
use Elabftw\Exceptions\ImproperActionException;
use Elabftw\Exceptions\ResourceNotFoundException;
use Elabftw\Models\Users\Users;
use Elabftw\Params\BaseQueryParams;
use Elabftw\Traits\TestsUtilsTrait;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\InputBag;
use Symfony\Component\HttpFoundation\Request;

use function array_merge;
use function json_decode;
use function json_encode;
use function sprintf;

final class ReactionsTest extends TestCase
{
    use TestsUtilsTrait;

    public function testAllEntryTypesAndIndependentDuplication(): void
    {
        foreach (EntityType::cases() as $type) {
            $entity = $type->toInstance(new Users(1, 1));
            $entity->setId($entity->create());
            $id = new Reactions($entity)->postAction(Action::Create, $this->data());
            $source = new Reactions($entity, $id);
            $created = $source->readOne();
            $this->assertSame('Hydrogenation', $created['title']);
            $this->assertCount(2, $created['components']);
            $this->assertCount(1, $entity->readOne()['reactions']);
            $copy = $type->toInstance($entity->Users, $entity->duplicate());
            $copied = new Reactions($copy)->readAll()[0];
            $this->assertNotSame($id, (int) $copied['id']);
            $source->patch(Action::Update, array('version' => $created['version'], 'title' => 'Changed'));
            $this->assertSame('Hydrogenation', new Reactions($copy)->readAll()[0]['title']);
            $this->assertTrue($source->destroy());
            $this->assertCount(0, new Reactions($entity)->readAll());
            $this->assertCount(3, new Reactions($entity)->readAll(new BaseQueryParams(query: new InputBag(array('history_of' => $id)))));
        }
    }

    public function testForeignReactionCannotBeReadUpdatedOrDeleted(): void
    {
        $first = $this->getFreshExperiment();
        $second = $this->getFreshExperiment();
        $id = new Reactions($first)->postAction(Action::Create, $this->data());
        $foreign = new Reactions($second, $id);
        foreach (array('read', 'patch', 'delete') as $operation) {
            try {
                match ($operation) {
                    'read' => $foreign->readOne(),
                    'patch' => $foreign->patch(Action::Update, array('version' => 1, 'title' => 'Foreign')),
                    'delete' => $foreign->destroy(),
                };
                $this->fail('Accepted a foreign reaction ' . $operation);
            } catch (ResourceNotFoundException) {
                $this->addToAssertionCount(1);
            }
        }
        $this->assertSame('Hydrogenation', new Reactions($first, $id)->readOne()['title']);
    }

    public function testStaleSaveAndFailedLinksDoNotPartiallyWrite(): void
    {
        $entity = $this->getFreshExperiment();
        $id = new Reactions($entity)->postAction(Action::Create, $this->data());
        $model = new Reactions($entity, $id);
        $model->patch(Action::Update, array('version' => 1, 'title' => 'Saved'));
        try {
            $model->patch(Action::Update, array('version' => 1, 'title' => 'Stale'));
            $this->fail('Accepted a stale save');
        } catch (ImproperActionException) {
            $this->assertSame('Saved', $model->readOne()['title']);
        }
        $invalid = $this->data();
        $invalid['components'][0]['compound_id'] = 4294967295;
        try {
            $model->patch(Action::Update, array_merge($invalid, array('version' => 2)));
            $this->fail('Accepted a missing compound');
        } catch (ResourceNotFoundException) {
            $this->assertSame(2, (int) $model->readOne()['version']);
            $this->assertCount(2, $model->readOne()['components']);
        }
    }

    public function testLockedEntryCannotChangeReaction(): void
    {
        $entity = $this->getFreshExperiment();
        $id = new Reactions($entity)->postAction(Action::Create, $this->data());
        $entity->lock();
        $this->expectException(ForbiddenException::class);
        new Reactions($entity, $id)->patch(Action::Update, array('version' => 1, 'title' => 'Changed'));
    }

    public function testImportClearsForeignInstallationLinks(): void
    {
        $entity = $this->getFreshExperiment();
        $data = $this->data();
        $data['components'][0]['compound_id'] = 4294967295;
        $data['components'][0]['item_id'] = 4294967295;
        $id = new Reactions($entity)->import($data);
        $component = new Reactions($entity, $id)->readOne()['components'][0];
        $this->assertNull($component['compound_id']);
        $this->assertNull($component['item_id']);
        $this->assertSame('100.000000000000', $component['molecular_weight']);
    }

    public function testApiCreationCalculationAndScopedReads(): void
    {
        $entity = $this->getFreshExperiment();
        $uri = sprintf('/api/v2/experiments/%d/reactions', $entity->id);
        $controller = new Apiv2Controller($entity->Users, Request::create($uri, 'POST', server: array('CONTENT_TYPE' => 'application/json'), content: json_encode($this->data(), JSON_THROW_ON_ERROR)));
        $controller->canWrite = true;
        $response = $controller->getResponse();
        $this->assertSame(201, $response->getStatusCode(), (string) $response->getContent());
        $location = $response->headers->get('Location');
        $this->assertNotNull($location);
        $response = new Apiv2Controller($entity->Users, Request::create($location))->getResponse();
        $this->assertSame(200, $response->getStatusCode());
        $reaction = json_decode((string) $response->getContent(), true, 512, JSON_THROW_ON_ERROR);
        $this->assertSame('Hydrogenation', $reaction['title']);

        $calculation = array_merge($this->data(), array('action' => 'calculate'));
        $controller = new Apiv2Controller($entity->Users, Request::create($uri, 'PATCH', server: array('CONTENT_TYPE' => 'application/json'), content: json_encode($calculation, JSON_THROW_ON_ERROR)));
        $controller->canWrite = true;
        $response = $controller->getResponse();
        $this->assertSame(200, $response->getStatusCode(), (string) $response->getContent());
        $this->assertCount(1, new Reactions($entity)->readAll());
        $this->assertSame(1, (int) new Reactions($entity, $reaction['id'])->readOne()['version']);

        $other = $this->getFreshExperiment();
        $uri = sprintf('/api/v2/experiments/%d/reactions/%d', $other->id, $reaction['id']);
        $this->assertSame(404, new Apiv2Controller($other->Users, Request::create($uri))->getResponse()->getStatusCode());
    }

    private function data(): array
    {
        $reference = '00000000-0000-4000-8000-000000000001';
        return array('title' => 'Hydrogenation', 'scheme' => array('root' => array('nodes' => array())),
            'reference_component_key' => $reference, 'yield_basis_component_key' => $reference,
            'components' => array(
                array('component_key' => $reference, 'name' => 'Starting material', 'molecular_weight' => 100, 'actual_value' => 1),
                array('role' => 'product', 'name' => 'Product', 'molecular_weight' => 102, 'actual_value' => 0.8),
            ));
    }
}
