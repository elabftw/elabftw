<?php
/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @copyright 2012 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */
declare(strict_types=1);

use Rector\Core\Configuration\Option;
use Rector\Core\ValueObject\PhpVersion;
use Rector\Set\ValueObject\SetList;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;

return static function (ContainerConfigurator $containerConfigurator): void {
    $parameters = $containerConfigurator->parameters();

    $parameters->set(Option::PHP_VERSION_FEATURES, PhpVersion::PHP_80);

    //$parameters->set(Option::SETS, array(SetList::CODE_QUALITY, 'twig-underscore-to-namespace', 'dead-code', 'phpstan'));
    $parameters->set(Option::SETS, array(SetList::CODE_QUALITY, SetList::DEAD_CODE, SetList::PHP_80));

    $parameters->set(Option::SKIP, array(
        Rector\CodeQuality\Rector\Identical\SimplifyBoolIdenticalTrueRector::class,
        Rector\CodeQuality\Rector\If_\SimplifyIfElseToTernaryRector::class,
        Rector\SOLID\Rector\ClassMethod\UseInterfaceOverImplementationInConstructorRector::class,
        Rector\DeadCode\Rector\Concat\RemoveConcatAutocastRector::class,
        Rector\DeadCode\Rector\Cast\RecastingRemovalRector::class,
    ));
};
