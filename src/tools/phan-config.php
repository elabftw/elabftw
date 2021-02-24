<?php declare(strict_types=1);
/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @copyright 2012 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */

/**
 * Config file for phan
 */

return array(
    'target_php_version' => '7.3',
    'max_literal_string_type_length' => 20000,

    // A list of directories that should be parsed for class and
    // method information. After excluding the directories
    // defined in exclude_analysis_directory_list, the remaining
    // files will be statically analyzed for errors.
    //
    // Thus, both first-party and third-party code being used by
    // your application should be included in this list.
    'directory_list' => array(
        'src',
        'web',
        'vendor',
    ),

    // A directory list that defines files that will be excluded
    // from static analysis, but whose class and method
    // information should be included.
    //
    // Generally, you'll want to include the directories for
    // third-party code (such as "vendor/") in this list.
    //
    // n.b.: If you'd like to parse but not analyze 3rd
    //       party code, directories containing that code
    //       should be added to the `directory_list` as
    //       to `exclude_analysis_directory_list`.
    'exclude_analysis_directory_list' => array(
        'vendor/',
        'uploads/',
        'cache',
        'node_modules',
    ),

    // A list of plugin files to execute.
    // See https://github.com/phan/phan/tree/master/.phan/plugins for even more.
    // (Pass these in as relative paths.
    // Base names without extensions such as 'AlwaysReturnPlugin'
    // can be used to refer to a plugin that is bundled with Phan)
    'plugins' => array(
        // checks if a function, closure or method unconditionally returns.
        // can also be written as 'vendor/phan/phan/.phan/plugins/AlwaysReturnPlugin.php'
        'AlwaysReturnPlugin',
        // Checks for syntactically unreachable statements in
        // the global scope or function bodies.
        'UnreachableCodePlugin',
        'DollarDollarPlugin',
        'DuplicateArrayKeyPlugin',
        'PregRegexCheckerPlugin',
        'PrintfCheckerPlugin',
    ),
    'suppress_issue_types' => array(
        'PhanUndeclaredGlobalVariable',
        'PhanUndeclaredMethod',
        'PhanUndeclaredClassMethod',
        'PhanUndeclaredClassProperty',
        'PhanUndeclaredClassConstant',
        'PhanUndeclaredClassCatch',
        'PhanUndeclaredTypeProperty',
        'PhanUndeclaredTypeParameter',
        'PhanUndeclaredConstant',
        'PhanUndeclaredExtendedClass',
        'PhanUndeclaredTypeThrowsType',
        'PhanUnextractableAnnotationElementName',
        'PhanUnextractableAnnotationSuffix',
    ),
);
