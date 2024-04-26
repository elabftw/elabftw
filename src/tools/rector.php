<?php

/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @copyright 2023 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */

declare(strict_types=1);
use Rector\Config\RectorConfig;
use Rector\Set\ValueObject\SetList;

/**
 * This is the configuration file for "yarn rector"
 * See rules: https://github.com/rectorphp/rector/blob/main/docs/rector_rules_overview.md
 */
return static function (RectorConfig $rectorConfig): void {
    // allow more time
    $rectorConfig->parallel(240);

    $rectorConfig->skip(array(
        Rector\CodeQuality\Rector\Identical\SimplifyBoolIdenticalTrueRector::class,
        Rector\CodeQuality\Rector\If_\SimplifyIfElseToTernaryRector::class,
        Rector\SOLID\Rector\ClassMethod\UseInterfaceOverImplementationInConstructorRector::class,
        Rector\DeadCode\Rector\Concat\RemoveConcatAutocastRector::class,
        Rector\CodeQuality\Rector\Empty_\SimplifyEmptyCheckOnEmptyArrayRector::class,
        Rector\DeadCode\Rector\Cast\RecastingRemovalRector::class,
        Rector\DeadCode\Rector\ClassConst\RemoveUnusedClassConstantRector::class => array(
            dirname(__DIR__) . '/classes/Extensions.php',
        ),
        Rector\DeadCode\Rector\ClassMethod\RemoveEmptyClassMethodRector::class => array(
            dirname(__DIR__) . '/classes/Db.php',
            dirname(__DIR__) . '/models/Config.php',
        ),
    ));

    // here we can define, what sets of rules will be applied
    // tip: use "SetList" class to autocomplete sets with your IDE
    $rectorConfig->sets(array(
        SetList::CODE_QUALITY,
        SetList::DEAD_CODE,
    ));
};
