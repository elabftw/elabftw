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
use jblond\TwigTrans\Translation;
use Twig\Environment;
use Twig\Extra\Intl\IntlExtension;
use Twig\Loader\FilesystemLoader;
use Twig\TwigFilter;
use Twig\TwigFunction;

/**
 * To get Twig
 */
trait TwigTrait
{
    /**
     * Prepare the Twig object
     */
    protected function getTwig(Config $config): Environment
    {
        // load templates
        $loader = new FilesystemLoader(dirname(__DIR__, 2) . '/src/templates');

        $options = array(
            // use local cache
            'cache' => FsTools::getCacheFolder('twig'),
            // debug mode means the cache is not used (useful in dev of course)
            'debug' => (bool) $config->configArr['debug'],
        );

        $TwigEnvironment = new Environment($loader, $options);

        // custom twig filters
        $filterOptions = array('is_safe' => array('html'));
        $msgFilter = new TwigFilter('msg', '\Elabftw\Elabftw\Tools::displayMessage', $filterOptions);
        $mdFilter = new TwigFilter('md2html', '\Elabftw\Elabftw\Tools::md2html', $filterOptions);
        $starsFilter = new TwigFilter('stars', '\Elabftw\Elabftw\Tools::showStars', $filterOptions);
        $bytesFilter = new TwigFilter('formatBytes', '\Elabftw\Elabftw\Tools::formatBytes', $filterOptions);
        $extFilter = new TwigFilter('getExt', '\Elabftw\Elabftw\Tools::getExt', $filterOptions);
        $qFilter = new TwigFilter('qFilter', '\Elabftw\Elabftw\Tools::qFilter', $filterOptions);
        $langFilter = new TwigFilter('jslang', '\Elabftw\Elabftw\Tools::getCalendarLang', $filterOptions);
        $metadataFilter = new TwigFilter('formatMetadata', '\Elabftw\Elabftw\Tools::formatMetadata', $filterOptions);
        $csrfFilter = new TwigFilter('csrf', '\Elabftw\Services\Transform::csrf', $filterOptions);
        $notifWebFilter = new TwigFilter('notifWeb', '\Elabftw\Services\Transform::notif', $filterOptions);
        // |trans filter
        $transFilter = new TwigFilter(
            'trans',
            function ($context, $string) {
                return Translation::transGetText($string, $context);
            },
            array('needs_context' => true)
        );
        $toDatetimeFilter = new TwigFilter('toDatetime', '\Elabftw\Elabftw\TwigFunctions::toDatetime', $filterOptions);

        // custom twig functions
        $limitOptions = new TwigFunction('limitOptions', '\Elabftw\Elabftw\TwigFunctions::getLimitOptions');
        $generationTime = new TwigFunction('generationTime', '\Elabftw\Elabftw\TwigFunctions::getGenerationTime');
        $memoryUsage = new TwigFunction('memoryUsage', '\Elabftw\Elabftw\TwigFunctions::getMemoryUsage');
        $numberOfQueries = new TwigFunction('numberOfQueries', '\Elabftw\Elabftw\TwigFunctions::getNumberOfQueries');
        $minPasswordLength = new TwigFunction('minPasswordLength', '\Elabftw\Elabftw\TwigFunctions::getMinPasswordLength');
        $ext2icon = new TwigFunction('ext2icon', '\Elabftw\Elabftw\Extensions::getIconFromExtension');


        // load the i18n extension for using the translation tag for twig
        // {% trans %}my string{% endtrans %}
        $TwigEnvironment->addExtension(new Translation());
        // intl extension
        $TwigEnvironment->addExtension(new IntlExtension());

        $TwigEnvironment->addFilter($msgFilter);
        $TwigEnvironment->addFilter($mdFilter);
        $TwigEnvironment->addFilter($starsFilter);
        $TwigEnvironment->addFilter($bytesFilter);
        $TwigEnvironment->addFilter($extFilter);
        $TwigEnvironment->addFilter($qFilter);
        $TwigEnvironment->addFilter($langFilter);
        $TwigEnvironment->addFilter($metadataFilter);
        $TwigEnvironment->addFilter($csrfFilter);
        $TwigEnvironment->addFilter($notifWebFilter);
        $TwigEnvironment->addFilter($transFilter);
        $TwigEnvironment->addFilter($toDatetimeFilter);
        // functions
        $TwigEnvironment->addFunction($limitOptions);
        $TwigEnvironment->addFunction($generationTime);
        $TwigEnvironment->addFunction($memoryUsage);
        $TwigEnvironment->addFunction($numberOfQueries);
        $TwigEnvironment->addFunction($minPasswordLength);
        $TwigEnvironment->addFunction($ext2icon);

        // add the version as a global var so we can have it for the ?v=x.x.x for js files
        $TwigEnvironment->addGlobal('v', App::INSTALLED_VERSION);

        return $TwigEnvironment;
    }
}
