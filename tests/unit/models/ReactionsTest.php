<?php

declare(strict_types=1);

namespace Elabftw\Models;

use Elabftw\Controllers\Apiv2Controller;
use Elabftw\Elabftw\Db;
use Elabftw\Enums\Action;
use Elabftw\Enums\BasePermissions;
use Elabftw\Enums\EntityType;
use Elabftw\Exceptions\ForbiddenException;
use Elabftw\Exceptions\ImproperActionException;
use Elabftw\Exceptions\ResourceNotFoundException;
use Elabftw\Interfaces\FingerprinterInterface;
use Elabftw\Models\Users\AnonymousUser;
use Elabftw\Models\Users\Users;
use Elabftw\Params\BaseQueryParams;
use Elabftw\Traits\TestsUtilsTrait;
use PDO;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\InputBag;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

use function array_column;
use function array_diff_key;
use function array_fill;
use function array_flip;
use function array_map;
use function base64_decode;
use function intval;
use function array_merge;
use function json_decode;
use function json_encode;
use function sprintf;

final class ReactionsTest extends TestCase
{
    use TestsUtilsTrait;

    // 1x1 pixel
    private const string PNG = 'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNkYPhfDwAChwGA60e6kgAAAABJRU5ErkJggg==';

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

    public function testAnonymousUserCannotReadHistory(): void
    {
        $user = $this->getRandomUserInTeam(1);
        $entity = EntityType::Experiments->toInstance($user);
        $entity->setId($entity->create(canreadBase: BasePermissions::Full));
        $id = new Reactions($entity)->postAction(Action::Create, $this->data());
        $uri = sprintf('/%s%d/reactions', $entity->getApiPath(), $entity->id);
        $historyUri = sprintf('%s?history_of=%d', $uri, $id);

        // current reactions are public with their entry, their history is not
        $this->assertSame(Response::HTTP_OK, new Apiv2Controller(new AnonymousUser(1), Request::create($uri))->getResponse()->getStatusCode());
        $this->assertSame(Response::HTTP_FORBIDDEN, new Apiv2Controller(new AnonymousUser(1), Request::create($historyUri))->getResponse()->getStatusCode());
        $this->assertSame(Response::HTTP_OK, new Apiv2Controller($user, Request::create($historyUri))->getResponse()->getStatusCode());

        $this->assertSame(array(), EntityType::Experiments->toInstance(new AnonymousUser(1), $entity->id)->readOneFull()['reaction_history']);
        $this->assertCount(1, $entity->readOneFull()['reaction_history']);
    }

    public function testReactionsKeepCreationOrder(): void
    {
        $entity = $this->getFreshExperiment();
        $first = new Reactions($entity)->postAction(Action::Create, $this->data());
        new Reactions($entity, $first)->destroy();
        new Reactions($entity)->postAction(Action::Create, array_merge($this->data(), array('title' => 'Second')));
        new Reactions($entity)->postAction(Action::Create, array_merge($this->data(), array('title' => 'Third')));
        $this->assertSame(array('Second', 'Third'), array_column(new Reactions($entity)->readAll(), 'title'));
    }

    public function testEntryReadsSummariesAndTheApiServesThePreview(): void
    {
        $entity = $this->getFreshExperiment();
        $id = new Reactions($entity)->postAction(Action::Create, array_merge($this->data(), array('preview_png' => self::PNG)));
        $summary = $entity->readOne()['reactions'][0];
        foreach (array('scheme', 'rxn', 'preview_png') as $column) {
            $this->assertArrayNotHasKey($column, $summary);
        }
        $this->assertArrayNotHasKey('molfile', $summary['components'][0]);
        $this->assertSame(1, (int) $summary['has_preview']);
        // quantities are still calculated, for display
        $this->assertArrayHasKey('actual', $summary['components'][0]);
        $this->assertArrayHasKey('rxn', $entity->readOneFull()['reactions'][0]);

        $uri = sprintf('/api/v2/experiments/%d/reactions/%d?format=binary', $entity->id, $id);
        $response = new Apiv2Controller($entity->Users, Request::create($uri))->getResponse();
        $this->assertSame(Response::HTTP_OK, $response->getStatusCode(), (string) $response->getContent());
        $this->assertSame('image/png', $response->headers->get('Content-Type'));
        $this->assertSame(base64_decode(self::PNG, true), $response->getContent());
    }

    public function testDuplicationCopiesTheStoredReaction(): void
    {
        $entity = $this->getFreshExperiment();
        new Reactions($entity)->postAction(Action::Create, array_merge($this->data(), array('preview_png' => self::PNG, 'temperature_c' => 25)));
        $copy = EntityType::Experiments->toInstance($entity->Users, $entity->duplicate());
        $source = new Reactions($entity)->readFull()[0];
        $copied = new Reactions($copy)->readFull()[0];
        $this->assertNotSame($source['id'], $copied['id']);
        // a column missing from the copy would show here
        $own = array_flip(array('id', 'created_at', 'modified_at', 'components'));
        $this->assertSame(array_diff_key($source, $own), array_diff_key($copied, $own));
        $stripIds = static fn(array $component): array => array_diff_key($component, array_flip(array('id', 'reaction_id')));
        $this->assertSame(array_map($stripIds, $source['components']), array_map($stripIds, $copied['components']));
        // the copy has its own history, starting with its creation
        $this->assertCount(1, new Reactions($copy)->readAll(new BaseQueryParams(query: new InputBag(array('history_of' => $copied['id'])))));
    }

    public function testHistoryStoresTheDrawingOnlyWhenItChanges(): void
    {
        $entity = $this->getFreshExperiment();
        $id = new Reactions($entity)->postAction(Action::Create, array_merge($this->data(), array('preview_png' => self::PNG)));
        $model = new Reactions($entity, $id);
        $model->patch(Action::Update, array('version' => 1, 'title' => 'Renamed'));
        $model->patch(Action::Update, array('version' => 2, 'scheme' => array('root' => array('nodes' => array(array('type' => 'arrow'))))));

        $Db = Db::getConnection();
        $req = $Db->prepare('SELECT JSON_CONTAINS_PATH(snapshot, \'one\', \'$.rxn\') FROM reaction_revisions WHERE reaction_id = :id ORDER BY version');
        $req->bindValue(':id', $id, PDO::PARAM_INT);
        $Db->execute($req);
        $this->assertSame(array(1, 0, 1), array_map(intval(...), $req->fetchAll(PDO::FETCH_COLUMN)));

        // but each revision is read with the drawing it had
        $history = array_column($model->readAll(new BaseQueryParams(query: new InputBag(array('history_of' => $id)))), 'snapshot');
        $this->assertSame($history[0]['scheme'], $history[1]['scheme']);
        $this->assertNotSame($history[1]['scheme'], $history[2]['scheme']);
        $this->assertArrayNotHasKey('preview_png', $history[0]);
        $this->assertArrayHasKey('actual', $history[1]['components'][0]);
    }

    public function testUnchangedStructuresAreNotFingerprintedAgain(): void
    {
        $fingerprinter = new class implements FingerprinterInterface {
            public int $calls = 0;

            public function calculate(string $fmt, string $data): array
            {
                $this->calls++;
                return array('data' => array_fill(0, 32, 1));
            }
        };
        $entity = $this->getFreshExperiment();
        $data = $this->data();
        $data['components'][0]['smiles'] = 'CC=C';
        $data['components'][1]['smiles'] = 'CCC';
        $id = new Reactions($entity, null, $fingerprinter)->postAction(Action::Create, $data);
        $this->assertSame(2, $fingerprinter->calls);

        $model = new Reactions($entity, $id, $fingerprinter);
        $model->patch(Action::Update, array('version' => 1, 'title' => 'Renamed'));
        $this->assertSame(2, $fingerprinter->calls);
        $this->assertSame(2, $this->countFingerprints($id));

        $components = $model->readOne()['components'];
        $components[1]['smiles'] = 'CCCC';
        $model->patch(Action::Update, array('version' => 2, 'components' => $components));
        $this->assertSame(3, $fingerprinter->calls);
        $this->assertSame(2, $this->countFingerprints($id));
    }

    private function countFingerprints(int $reactionId): int
    {
        $req = Db::getConnection()->prepare('SELECT COUNT(*) FROM reaction_component_fingerprints AS f
            INNER JOIN reaction_components AS c ON (c.id = f.component_id) WHERE c.reaction_id = :id');
        $req->bindValue(':id', $reactionId, PDO::PARAM_INT);
        $req->execute();
        return (int) $req->fetchColumn();
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
