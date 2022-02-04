<?php declare(strict_types=1);
/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @copyright 2012 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */

namespace Elabftw\Traits;

use function dirname;
use Elabftw\Elabftw\App;
use Elabftw\Elabftw\FsTools;
use Elabftw\Models\Config;
use function is_readable;

/**
 * To get Twig
 */
trait TwigTrait
{
    /**
     * Prepare the Twig object
     */
    protected function getTwig(Config $config): \Twig\Environment
    {
        // load templates
        $loader = new \Twig\Loader\FilesystemLoader(dirname(__DIR__, 2) . '/src/templates');

        // use local cache
        $options = array('cache' => FsTools::getCacheFolder('twig'));

        // Twig debug mode will allow to use dump() and force autoreload
        // so it will not use the cache
        if ($config->configArr['debug']) {
            $options['debug'] = true;
        }

        $TwigEnvironment = new \Twig\Environment($loader, $options);

        // custom twig filters
        $filterOptions = array('is_safe' => array('html'));
        $msgFilter = new \Twig\TwigFilter('msg', '\Elabftw\Elabftw\Tools::displayMessage', $filterOptions);
        $mdFilter = new \Twig\TwigFilter('md2html', '\Elabftw\Elabftw\Tools::md2html', $filterOptions);
        $starsFilter = new \Twig\TwigFilter('stars', '\Elabftw\Elabftw\Tools::showStars', $filterOptions);
        $bytesFilter = new \Twig\TwigFilter('formatBytes', '\Elabftw\Elabftw\Tools::formatBytes', $filterOptions);
        $extFilter = new \Twig\TwigFilter('getExt', '\Elabftw\Elabftw\Tools::getExt', $filterOptions);
        $filesizeFilter = new \Twig\TwigFilter('filesize', '\filesize', $filterOptions);
        $qFilter = new \Twig\TwigFilter('qFilter', '\Elabftw\Elabftw\Tools::qFilter', $filterOptions);
        $langFilter = new \Twig\TwigFilter('jslang', '\Elabftw\Elabftw\Tools::getCalendarLang', $filterOptions);
        $metadataFilter = new \Twig\TwigFilter('formatMetadata', '\Elabftw\Elabftw\Tools::formatMetadata', $filterOptions);
        $csrfFilter = new \Twig\TwigFilter('csrf', '\Elabftw\Services\Transform::csrf', $filterOptions);
        $notifWebFilter = new \Twig\TwigFilter('notifWeb', '\Elabftw\Services\Transform::notif', $filterOptions);
        // custom twig functions
        $limitOptions = new \Twig\TwigFunction('limitOptions', '\Elabftw\Elabftw\TwigFunctions::getLimitOptions');
        $generationTime = new \Twig\TwigFunction('generationTime', '\Elabftw\Elabftw\TwigFunctions::getGenerationTime');
        $memoryUsage = new \Twig\TwigFunction('memoryUsage', '\Elabftw\Elabftw\TwigFunctions::getMemoryUsage');
        $numberOfQueries = new \Twig\TwigFunction('numberOfQueries', '\Elabftw\Elabftw\TwigFunctions::getNumberOfQueries');
        $minPasswordLength = new \Twig\TwigFunction('minPasswordLength', '\Elabftw\Elabftw\TwigFunctions::getMinPasswordLength');

        // custom test to check for a file
        $test = new \Twig\TwigTest('readable', function (string $path) {
            return is_readable(dirname(__DIR__, 2) . '/uploads/' . $path);
        });
        $TwigEnvironment->addTest($test);

        $TwigEnvironment->addFilter($msgFilter);
        $TwigEnvironment->addFilter($mdFilter);
        $TwigEnvironment->addFilter($starsFilter);
        $TwigEnvironment->addFilter($bytesFilter);
        $TwigEnvironment->addFilter($extFilter);
        $TwigEnvironment->addFilter($filesizeFilter);
        $TwigEnvironment->addFilter($qFilter);
        $TwigEnvironment->addFilter($langFilter);
        $TwigEnvironment->addFilter($metadataFilter);
        $TwigEnvironment->addFilter($csrfFilter);
        $TwigEnvironment->addFilter($notifWebFilter);
        // functions
        $TwigEnvironment->addFunction($limitOptions);
        $TwigEnvironment->addFunction($generationTime);
        $TwigEnvironment->addFunction($memoryUsage);
        $TwigEnvironment->addFunction($numberOfQueries);
        $TwigEnvironment->addFunction($minPasswordLength);

        // i18n for twig
        $TwigEnvironment->addExtension(new \Twig\Extensions\I18nExtension());

        // add the version as a global var so we can have it for the ?v=x.x.x for js files
        $TwigEnvironment->addGlobal('v', App::INSTALLED_VERSION);

        if ($config->configArr['debug']) {
            $TwigEnvironment->addExtension(new \Twig\Extension\DebugExtension());
        }

        return $TwigEnvironment;
    }
}
