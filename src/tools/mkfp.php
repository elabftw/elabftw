<?php

/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @copyright 2021 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */

declare(strict_types=1);

namespace Elabftw\Elabftw;

use Elabftw\Models\Fingerprints;
use Elabftw\Models\Items;
use Elabftw\Models\Users;
use Elabftw\Services\HttpGetter;
use GuzzleHttp\Client;

require_once dirname(__DIR__, 2) . '/vendor/autoload.php';

$smiles = array(
    array(
        "smiles" => "CCO",
        "name" => "Ethanol"
    ),
    array(
        "smiles" => "CC(=O)O",
        "name" => "Acetic acid"
    ),
    array(
        "smiles" => "CCN",
        "name" => "Ethylamine"
    ),
    array(
        "smiles" => "CC(=O)N",
        "name" => "Acetamide"
    ),
    array(
        "smiles" => "C1CCCCC1",
        "name" => "Cyclohexane"
    ),
    array(
        "smiles" => "CC(C)O",
        "name" => "Isopropanol"
    ),
    array(
        "smiles" => "CC(C)C(=O)O",
        "name" => "Isobutyric acid"
    ),
    array(
        "smiles" => "C1=CC=CC=C1",
        "name" => "Benzene"
    ),
    array(
        "smiles" => "C1=CC=C(C=C1)O",
        "name" => "Phenol"
    ),
    array(
        "smiles" => "C1=CC=C(C=C1)C(=O)O",
        "name" => "Benzoic acid"
    ),
    array(
        "smiles" => "CC(C)C",
        "name" => "Isobutane"
    ),
    array(
        "smiles" => "CCCC",
        "name" => "Butane"
    ),
    array(
        "smiles" => "CCOCC",
        "name" => "Diethyl ether"
    ),
    array(
        "smiles" => "CCC(=O)O",
        "name" => "Propionic acid"
    ),
    array(
        "smiles" => "CCCCCC",
        "name" => "Hexane"
    ),
    array(
        "smiles" => "CC(C)N",
        "name" => "Isopropylamine"
    ),
    array(
        "smiles" => "CC(C)C(=O)N",
        "name" => "Isobutyramide"
    ),
    array(
        "smiles" => "CC(C)(C)O",
        "name" => "tert-Butyl alcohol"
    ),
    array(
        "smiles" => "CC(C)C(C)C",
        "name" => "2-Methylpentane"
    ),
    array(
        "smiles" => "C1CC1",
        "name" => "Cyclopropane"
    ),
);
$startTime = microtime(true);
$Items = new Items(new Users(1, 1));
/*
foreach ($smiles as $mol) {
    $fp = new Fingerprints(new HttpGetter(new Client()), 'http://fingerprinter:8000');
    $fingerprint = $fp->calculate($mol['smiles']);
    $id = $Items->create(1, title: $mol['name'], body: $mol['smiles']);
    $fp->create($id, $fingerprint['data']);
}
 */
foreach ($smiles as $mol) {
    $fp = new Fingerprints(new HttpGetter(new Client()), 'http://fingerprinter:8000');
    $fingerprint = $fp->calculate($mol['smiles']);
    echo $mol['name'] . "\n";
    echo $mol['smiles'] . "\n";
    var_dump($fp->search($fingerprint['data']));
}
$endTime = microtime(true);
$executionTime = $endTime - $startTime;
printf('Executed in %.4f seconds', $executionTime);