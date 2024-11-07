<?php

/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @copyright 2024 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */

declare(strict_types=1);

namespace Elabftw\Models;

use Elabftw\Enums\Action;
use Elabftw\Enums\FilterableColumn;
use Elabftw\Exceptions\IllegalActionException;
use Elabftw\Exceptions\ImproperActionException;
use Elabftw\Interfaces\RestInterface;
use Elabftw\Params\DisplayParams;
use Symfony\Component\HttpFoundation\Request;

/**
 * Process a single request targeting multiple entities
 */
class Batch implements RestInterface
{
    private int $processed = 0;

    public function __construct(private Users $requester) {}

    public function postAction(Action $action, array $reqBody): int
    {
        $action = Action::from($reqBody['action']);
        if ($reqBody['items_types']) {
            $model = new Items($this->requester);
            $this->processEntities($reqBody['items_types'], $model, FilterableColumn::Category, $action, $reqBody);
        }
        if ($reqBody['items_status']) {
            $model = new Items($this->requester);
            $this->processEntities($reqBody['items_status'], $model, FilterableColumn::Status, $action, $reqBody);
        }
        if ($reqBody['experiments_categories']) {
            $model = new Experiments($this->requester);
            $this->processEntities($reqBody['experiments_categories'], $model, FilterableColumn::Category, $action, $reqBody);
        }
        if ($reqBody['experiments_status']) {
            $model = new Experiments($this->requester);
            $this->processEntities($reqBody['experiments_status'], $model, FilterableColumn::Status, $action, $reqBody);
        }
        if ($reqBody['tags']) {
            $models = array(
                'Experiments' => new Experiments($this->requester),
                'Items' => new Items($this->requester),
            );
            foreach ($models as $model) {
                $Tags2Entity = new Tags2Entity($model->entityType);
                $targetIds = $Tags2Entity->getEntitiesIdFromTags('id', $reqBody['tags']);
                // Format tags as associative array to be processed same way as other entries
                $tagEntries = array_map(fn($id) => array('id' => $id), $targetIds);
                $this->loopOverEntries($tagEntries, $model, $action, $reqBody);
            }
        }
        if ($reqBody['users']) {
            // only process experiments
            $model = new Experiments($this->requester);
            $this->processEntities($reqBody['users'], $model, FilterableColumn::Owner, $action, $reqBody);
        }
        return $this->processed;
    }

    public function patch(Action $action, array $params): array
    {
        throw new ImproperActionException('No PATCH action for batch.');
    }

    public function getApiPath(): string
    {
        return 'api/v2/';
    }

    public function readAll(): array
    {
        throw new ImproperActionException('No GET action for batch.');
    }

    public function readOne(): array
    {
        return $this->readAll();
    }

    public function destroy(): bool
    {
        throw new ImproperActionException('No DELETE action for batch.');
    }

    private function processEntities(array $idArr, AbstractConcreteEntity $model, FilterableColumn $column, Action $action, array $params): void
    {
        $entries = $this->getEntriesByIds($idArr, $model, $column);
        $this->loopOverEntries($entries, $model, $action, $params);
    }

    private function getEntriesByIds(array $idArr, AbstractConcreteEntity $model, FilterableColumn $column): array
    {
        $allEntries = array();
        foreach ($idArr as $id) {
            $DisplayParams = new DisplayParams($this->requester, Request::createFromGlobals(), $model->entityType);
            $DisplayParams->limit = 100000;
            $DisplayParams->appendFilterSql($column, $id);
            $entries = $model->readShow($DisplayParams, false);
            $allEntries = array_merge($allEntries, $entries);
        }
        return $allEntries;
    }

    private function loopOverEntries(array $entries, AbstractConcreteEntity $model, Action $action, array $params): void
    {
        // On transfer of ownership, only the target owner is required in params
        if ($params['action'] === Action::UpdateOwner->value) {
            $params = array('userid' => $params['target_owner'] ?? throw new ImproperActionException('Target owner is missing!'));
            $action = Action::Update;
        }
        foreach ($entries as $entry) {
            try {
                $model->setId($entry['id']);
                $model->patch($action, $params);
                $this->processed++;
            } catch (IllegalActionException) {
                continue;
            }
        }
    }
}
