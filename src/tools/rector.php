<?php
/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @copyright 2012 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */
declare(strict_types=1);

use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;

return static function (ContainerConfigurator $containerConfigurator): void { /** @phpstan-ignore-line */
    $parameters = $containerConfigurator->parameters(); /** @phpstan-ignore-line */

    $parameters->set('php_version_features', '7.3');

    $parameters->set('sets', array('code-quality', 'twig-underscore-to-namespace', 'dead-code', 'phpstan'));

    $parameters->set('exclude_rectors', array('Rector\CodeQuality\Rector\Identical\SimplifyBoolIdenticalTrueRector', 'Rector\CodeQuality\Rector\If_\SimplifyIfElseToTernaryRector', 'Rector\SOLID\Rector\ClassMethod\UseInterfaceOverImplementationInConstructorRector', 'Rector\DeadCode\Rector\Concat\RemoveConcatAutocastRector', 'Rector\PHPStan\Rector\Cast\RecastingRemovalRector'));
};
