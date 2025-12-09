<?php

/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @copyright 2024 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */

declare(strict_types=1);

namespace Elabftw\Enums;

use function array_map;

enum ApiEndpoint: string
{
    case ApiKeys = 'apikeys';
    case Batch = 'batch';
    case Compounds = 'compounds';
    case Config = 'config';
    case Dspace = 'dspace';
    case Experiments = 'experiments';
    case ExperimentsTemplates = 'experiments_templates';
    case Export = 'exports';
    case ExtraFieldsKeys = 'extra_fields_keys';
    case Event = 'event';
    case Events = 'events';
    case FavTags = 'favtags';
    case Idps = 'idps';
    case IdpsSources = 'idps_sources';
    case Import = 'import';
    case Info = 'info';
    case Instance = 'instance';
    case Items = 'items';
    case ItemsTypes = 'items_types';
    case Reports = 'reports';
    case StorageUnits = 'storage_units';

    // @deprecated
    case TeamTags = 'team_tags';
    case Teams = 'teams';
    case Todolist = 'todolist';
    case UnfinishedSteps = 'unfinished_steps';
    case Users = 'users';

    public static function getCases(): array
    {
        return array_map(fn(self $case): string => $case->value, self::cases());
    }
}
