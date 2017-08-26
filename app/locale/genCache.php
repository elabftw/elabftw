<?php
/**
 * genCache.php
 * force the generation of Twig cache files
 * so we can use them for gettext po/mo generation
 *
 * Usage: php locale/genCache.php
 */
require_once dirname(dirname(dirname(__FILE__))) . '/config.php';
require_once dirname(dirname(dirname(__FILE__))) . '/vendor/autoload.php';
$tplDir = dirname(dirname(dirname(__FILE__))) . '/app/tpl';
$tmpDir = '/tmp/elabftw-twig-cache/';

$loader = new \Twig_Loader_Filesystem($tplDir);

// force auto-reload to always have the latest version of the template
$Twig = new Twig_Environment($loader, array(
    'cache' => $tmpDir,
    'auto_reload' => true
));
// custom twig filters
$filterOptions = array('is_safe' => array('html'));
$msgFilter = new \Twig_SimpleFilter('msg', '\Elabftw\Elabftw\Tools::displayMessage', $filterOptions);
$dateFilter = new \Twig_SimpleFilter('kdate', '\Elabftw\Elabftw\Tools::formatDate', $filterOptions);
$mdFilter = new \Twig_SimpleFilter('md2html', '\Elabftw\Elabftw\Tools::md2html', $filterOptions);
$starsFilter = new \Twig_SimpleFilter('stars', '\Elabftw\Elabftw\Tools::showStars', $filterOptions);
$bytesFilter = new \Twig_SimpleFilter('formatBytes', '\Elabftw\Elabftw\Tools::formatBytes', $filterOptions);

$Twig->addFilter($msgFilter);
$Twig->addFilter($dateFilter);
$Twig->addFilter($mdFilter);
$Twig->addFilter($starsFilter);
$Twig->addFilter($bytesFilter);
$Twig->addExtension(new \Twig_Extensions_Extension_I18n());

// iterate over all your templates
foreach (new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($tplDir), \RecursiveIteratorIterator::LEAVES_ONLY) as $file) {
    // force compilation
    if ($file->isFile()) {
        $Twig->loadTemplate(str_replace($tplDir . '/', '', $file));
    }
}
