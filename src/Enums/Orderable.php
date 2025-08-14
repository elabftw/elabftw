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

/**
 * All the tables that have an "ordering" column
 */
enum Orderable: string
{
    case ExperimentsCategories = 'experiments_categories';
    case ResourcesCategories = 'items_categories';
    case ExperimentsStatus = 'experiments_status';
    case ItemsStatus = 'items_status';
    case ExperimentsSteps = 'experiments_steps';
    case ItemsSteps = 'items_steps';
    case Todolist = 'todolist';
    case ExperimentsTemplates = 'experiments_templates';
    case ExperimentsTemplatesSteps = 'experiments_templates_steps';
    case ItemsTypesSteps = 'items_types_steps';
    case ExtraFields = 'extra_fields';
}
