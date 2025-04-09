<?php

/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @copyright 2024 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */

declare(strict_types=1);

namespace Elabftw\Elabftw;

use Elabftw\Models\StorageUnits;
use Elabftw\Models\Users;

require_once dirname(__DIR__, 2) . '/vendor/autoload.php';

$StorageUnits = new StorageUnits(new Users(1, 1));
$paris = $StorageUnits->create('Paris');
$StorageUnits->create('Pavillon des Sources', $paris);
$StorageUnits->create('Musée', $paris);
$hospital = $StorageUnits->create('Hôpital', $paris);
$StorageUnits->create('1er étage', $hospital);
$StorageUnits->create('2ème étage', $hospital);
$floor3 = $StorageUnits->create('296ème étage', $hospital);
$StorageUnits->create('Pièce radioactivité', $floor3);
$chemroom = $StorageUnits->create('Pièce stockage chimie', $floor3);
$placard1 = $StorageUnits->create('Placard du haut', $chemroom);
$placard2 = $StorageUnits->create('Placard du bas', $chemroom);
$StorageUnits->create('Étagère A', $placard2);
$StorageUnits->create('Étagère B', $placard2);
$StorageUnits->create('Étagère B', $placard1);

$versailles = $StorageUnits->create('Versailles');
$StorageUnits->create('Château', $versailles);
$StorageUnits->create('Écuries', $versailles);
$StorageUnits->create('Catacombes', $versailles);
