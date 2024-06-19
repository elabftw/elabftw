<?php

/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @author Marcel Bolten <github@marcelbolten.de>
 * @copyright 2024 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */

declare(strict_types=1);

namespace Elabftw\Enums;

use Elabftw\Exceptions\ImproperActionException;

use function array_map;

enum ApiSubModels: string
{
    case Comments = 'comments';
    case ExperimentsCategories = 'experiments_categories';
    case ExperimentsLinks = 'experiments_links';
    case ExperimentsStatus = 'experiments_status';
    case ItemsCategories = 'items_categories';
    case ItemsLinks = 'items_links';
    case ItemsStatus = 'items_status';
    case Notifications = 'notifications';
    case ProcurementRequests = 'procurement_requests';
    case RequestActions = 'request_actions';
    case Revisions = 'revisions';
    case SigKeys = 'sig_keys';
    case Status = 'status';
    case Steps = 'steps';
    case Tags = 'tags';
    case Teamgroups = 'teamgroups';
    case Uploads = 'uploads';

    public static function validSubModelsForEndpoint(ApiEndpoint $apiEndpoint): array
    {
        return match ($apiEndpoint) {
            ApiEndpoint::Experiments,
            ApiEndpoint::Items,
            ApiEndpoint::ItemsTypes,
            ApiEndpoint::ExperimentsTemplates => self::getAbstractEntityCases(),
            ApiEndpoint::Teams => self::getTeamsCases(),
            ApiEndpoint::Users => self::getUsersCases(),
            ApiEndpoint::Event => self::getSchedulerCases(),
            default => throw new ImproperActionException('Incorrect endpoint.'),
        };
    }

    private static function getAbstractEntityCases(): array
    {
        return array_map(
            fn(self $case): string => $case->value,
            array(
                self::Comments,
                self::ExperimentsLinks,
                self::ItemsLinks,
                self::RequestActions,
                self::Revisions,
                self::Steps,
                self::Tags,
                self::Uploads,
            ),
        );
    }

    private static function getTeamsCases(): array
    {
        return array_map(
            fn(self $case): string => $case->value,
            array(
                self::ExperimentsCategories,
                self::ExperimentsStatus,
                self::ItemsCategories,
                self::ItemsStatus,
                self::ProcurementRequests,
                self::Status,
                self::Tags,
                self::Teamgroups,
            ),
        );
    }

    private static function getUsersCases(): array
    {
        return array_map(
            fn(self $case): string => $case->value,
            array(
                self::Notifications,
                self::RequestActions,
                self::SigKeys,
                self::Uploads,
            ),
        );
    }

    private static function getSchedulerCases(): array
    {
        return array_map(
            fn(self $case): string => $case->value,
            array(
                self::Notifications,
            ),
        );
    }
}
