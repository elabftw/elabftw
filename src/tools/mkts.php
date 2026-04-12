<?php

declare(strict_types=1);

$root = dirname(__DIR__);
$input = $root . '../../src/Enums/State.php';
$outputDir = $root . '../../src/ts';
$output = $outputDir . '/state.auto.ts';

$contents = file_get_contents($input);
if ($contents === false) {
    fwrite(STDERR, 'Failed to read: ' . $input . PHP_EOL);
    exit(1);
}

preg_match_all('/case\s+([A-Za-z_][A-Za-z0-9_]*)\s*=\s*([0-9]+)\s*;/', $contents, $matches, PREG_SET_ORDER);

if ($matches === array()) {
    fwrite(STDERR, 'No enum cases found in: ' . $input . PHP_EOL);
    exit(1);
}

$stateLines = array();
$labelLines = array();

foreach ($matches as $match) {
    $name = $match[1];
    $value = $match[2];

    $stateLines[] = sprintf('  %s: %s,', $name, $value);
    $labelLines[] = sprintf("  [State.%s]: '%s',", $name, $name);
}

$generated = implode(PHP_EOL, array(
    '// This file is auto-generated from src/Enums/State.php by src/tools/mkts.php',
    '// Do not edit manually.',
    '',
    'export const State = {',
    implode(PHP_EOL, $stateLines),
    '} as const;',
    '',
    'export type StateValue = (typeof State)[keyof typeof State];',
    'export type StateKey = keyof typeof State;',
    '',
    'export const stateLabel: Record<StateValue, string> = {',
    implode(PHP_EOL, $labelLines),
    '};',
    '',
));

if (file_put_contents($output, $generated) === false) {
    fwrite(STDERR, 'Failed to write: ' . $output . PHP_EOL);
    exit(1);
}

fwrite(STDOUT, 'Generated ' . $output . PHP_EOL);
