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
use Elabftw\Enums\Scope;
use Elabftw\Enums\State;
use Elabftw\Exceptions\IllegalActionException;
use Elabftw\Exceptions\ImproperActionException;
use Elabftw\Models\Users\Users;
use Elabftw\Params\DisplayParams;
use Override;

/**
 * Process a single request targeting multiple entities
 */
final class Batch extends AbstractRest
{
    private int $processed = 0;

    public function __construct(private Users $requester) {}

    #[Override]
    public function postAction(Action $action, array $reqBody): int
    {
        $action = Action::from($reqBody['action']);
        $state = null;
        // on Unarchive action, search for 'Archived' entities to perform the patch.
        if ($action === Action::Unarchive) {
            $state = State::Archived;
        }
        // same - search for deleted items when we want to Restore them.
        if ($action === Action::Restore) {
            $state = State::Deleted;
        }
        if ($reqBody['items_tags']) {
            $this->processTags($reqBody['items_tags'], new Items($this->requester), $action, $reqBody);
        }
        if ($reqBody['items_categories']) {
            $model = new Items($this->requester);
            $this->processEntities($reqBody['items_categories'], $model, FilterableColumn::Category, $action, $reqBody, $state);
        }
        if ($reqBody['items_status']) {
            $model = new Items($this->requester);
            $this->processEntities($reqBody['items_status'], $model, FilterableColumn::Status, $action, $reqBody, $state);
        }
        if ($reqBody['experiments_categories']) {
            $model = new Experiments($this->requester);
            $this->processEntities($reqBody['experiments_categories'], $model, FilterableColumn::Category, $action, $reqBody, $state);
        }
        if ($reqBody['experiments_status']) {
            $model = new Experiments($this->requester);
            $this->processEntities($reqBody['experiments_status'], $model, FilterableColumn::Status, $action, $reqBody, $state);
        }
        if ($reqBody['experiments_tags']) {
            $this->processTags($reqBody['experiments_tags'], new Experiments($this->requester), $action, $reqBody);
        }
        if ($reqBody['users']) {
            // only process experiments
            $model = new Experiments($this->requester);
            $this->processEntities($reqBody['users'], $model, FilterableColumn::Owner, $action, $reqBody, $state);
        }
        return $this->processed;
    }

    #[Override]
    public function getApiPath(): string
    {
        return 'api/v2/batch/';
    }

    private function processEntities(array $idArr, AbstractConcreteEntity $model, FilterableColumn $column, Action $action, array $params, ?State $state = null): void
    {
        $entries = $this->getEntriesByIds($idArr, $model, $column, $state);
        $this->loopOverEntries($entries, $model, $action, $params);
    }

    private function processTags(array $tags, AbstractConcreteEntity $model, Action $action, array $params): void
    {
        $Tags2Entity = new Tags2Entity($this->requester, $model->entityType);
        $targetIds = $Tags2Entity->getEntitiesIdFromTags('id', $tags, Scope::Team);
        // Format tags as associative array to be processed the same way as other entries
        $tagEntries = array_map(fn($id) => array('id' => $id), $targetIds);
        $this->loopOverEntries($tagEntries, $model, $action, $params);
    }

    private function getEntriesByIds(array $idArr, AbstractConcreteEntity $model, FilterableColumn $column, ?State $state): array
    {
        $allEntries = array();
        foreach ($idArr as $id) {
            $DisplayParams = new DisplayParams(
                requester: $this->requester,
                // this is needed so psalm is happy (might be a bug in psalm)
                entityType: $model->entityType,
                query: null,
                limit: 100000,
                states: $state ? array($state) : array(State::Normal),
            );
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
