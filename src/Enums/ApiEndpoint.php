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

enum ApiEndpoint: string
{
    case ApiKeys = 'apikeys';
    case Config = 'config';
    case Idps = 'idps';
    case Info = 'info';
    case Experiments = 'experiments';
    case Items = 'items';
    case ExperimentsTemplates = 'experiments_templates';
    case ItemsTypes = 'items_types';
    case Event = 'event';
    case Events = 'events';
    case ExtraFieldsKeys = 'extra_fields_keys';
    case FavTags = 'favtags';
    case SigKeys = 'sig_keys';
    case TeamTags = 'team_tags';
    case Teams = 'teams';
    case Todolist = 'todolist';
    case UnfinishedSteps = 'unfinished_steps';
    case Users = 'users';

    public static function getCases(): array
    {
        return array_map(fn($case) => $case->value, ApiEndpoint::cases());
    }
}
