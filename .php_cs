<?php declare(strict_types=1);
/**
 * PHP-CS-Fixer config for elabftw
 * @author Nicolas CARPi <nicolas.carpi@curie.fr>
 * @copyright 2012 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */

return PhpCsFixer\Config::create()
    ->setRiskyAllowed(true)
    ->setRules(array(
        '@PSR2' => true,
        '@PHP71Migration' => true,
        'psr4' => true,
        'array_syntax' => ['syntax' => 'long'],
        'php_unit_construct' => true,
        'php_unit_fqcn_annotation' => true,
        'silenced_deprecation_error' => true,
        'declare_strict_types' => true,
        'strict_param' => true,
        'dir_constant' => true,
        'pow_to_exponentiation' => true,
        'is_null' => true,
        'no_homoglyph_names' => true,
        'no_null_property_initialization' => true,
        'no_php4_constructor' => true,
        'non_printable_character' => true,
        'ordered_imports' => true,
    ))
    ->setFinder(
        PhpCsFixer\Finder::create()
            ->name('/\.php|\.php.dist$/')
            ->in(['src', 'tests'])
    )
;
