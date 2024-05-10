<?php

/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @copyright 2012 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */

declare(strict_types=1);

namespace Elabftw\Traits;

use Elabftw\Elabftw\App;
use Elabftw\Elabftw\FsTools;
use jblond\TwigTrans\Translation;
use Twig\Environment;
use Twig\Extension\DebugExtension;
use Twig\Extra\Intl\IntlExtension;
use Twig\Loader\FilesystemLoader;
use Twig\TwigFilter;
use Twig\TwigFunction;

use function dirname;

/**
 * To get Twig
 */
trait TwigTrait
{
    /**
     * Prepare the Twig object
     */
    protected function getTwig(bool $debug): Environment
    {
        // load templates
        $loader = new FilesystemLoader(dirname(__DIR__, 2) . '/src/templates');

        $options = array(
            // use local cache
            'cache' => FsTools::getCacheFolder('twig'),
            // debug mode means the cache is not used (useful in dev of course)
            'debug' => $debug,
        );

        $TwigEnvironment = new Environment($loader, $options);

        // custom twig filters
        $filterOptions = array('is_safe' => array('html'));
        $msgFilter = new TwigFilter('msg', '\Elabftw\Elabftw\TwigFilters::displayMessage', $filterOptions);
        $mdFilter = new TwigFilter('md2html', '\Elabftw\Elabftw\Tools::md2html', $filterOptions);
        $bytesFilter = new TwigFilter('formatBytes', '\Elabftw\Elabftw\Tools::formatBytes', $filterOptions);
        $extFilter = new TwigFilter('getExt', '\Elabftw\Elabftw\Tools::getExt', $filterOptions);
        $metadataFilter = new TwigFilter('formatMetadata', '\Elabftw\Elabftw\TwigFilters::formatMetadata', $filterOptions);
        $csrfFilter = new TwigFilter('csrf', '\Elabftw\Services\Transform::csrf', $filterOptions);
        $notifWebFilter = new TwigFilter('notifWeb', '\Elabftw\Services\Transform::notif', $filterOptions);
        $toIconFilter = new TwigFilter('toIcon', '\Elabftw\Elabftw\TwigFilters::toIcon', $filterOptions);
        // |trans filter
        $transFilter = new TwigFilter(
            'trans',
            fn(array $context, string $string): string => Translation::transGetText($string, $context),
            array('needs_context' => true)
        );
        $toDatetimeFilter = new TwigFilter('toDatetime', '\Elabftw\Elabftw\TwigFunctions::toDatetime', $filterOptions);
        $extractJson = new TwigFilter('extractJson', '\Elabftw\Elabftw\TwigFunctions::extractJson', $filterOptions);
        $extractDisplayMainText = new TwigFilter('extractDisplayMainText', '\Elabftw\Elabftw\TwigFunctions::extractDisplayMainText', $filterOptions);
        $isInJsonArray = new TwigFilter('isInJsonArray', '\Elabftw\Elabftw\TwigFunctions::isInJsonArray', $filterOptions);
        $canToHuman = new TwigFilter('canToHuman', '\Elabftw\Elabftw\TwigFunctions::canToHuman', $filterOptions);
        $decrypt = new TwigFilter('decrypt', '\Elabftw\Elabftw\TwigFilters::decrypt', $filterOptions);
        $toSymbol = new TwigFilter('toSymbol', '\Elabftw\Elabftw\TwigFilters::toSymbol', $filterOptions);

        // custom twig functions
        $limitOptions = new TwigFunction('limitOptions', '\Elabftw\Elabftw\TwigFunctions::getLimitOptions');
        $generationTime = new TwigFunction('generationTime', '\Elabftw\Elabftw\TwigFunctions::getGenerationTime');
        $memoryUsage = new TwigFunction('memoryUsage', '\Elabftw\Elabftw\TwigFunctions::getMemoryUsage');
        $numberOfQueries = new TwigFunction('numberOfQueries', '\Elabftw\Elabftw\TwigFunctions::getNumberOfQueries');
        $ext2icon = new TwigFunction('ext2icon', '\Elabftw\Elabftw\Extensions::getIconFromExtension');
        $sortIcon = new TwigFunction('sortIcon', '\Elabftw\Elabftw\TwigFunctions::getSortIcon');
        $getExtendedSearchExample = new TwigFunction('getExtendedSearchExample', '\Elabftw\Elabftw\TwigFunctions::getExtendedSearchExample');

        // load the i18n extension for using the translation tag for twig
        // {% trans %}my string{% endtrans %}
        $TwigEnvironment->addExtension(new Translation());
        // intl extension
        $TwigEnvironment->addExtension(new IntlExtension());
        // enable twig dump function in debug mode {{ dump(variable) }}
        if ($debug) {
            $TwigEnvironment->addExtension(new DebugExtension());
        }

        $TwigEnvironment->addFilter($msgFilter);
        $TwigEnvironment->addFilter($mdFilter);
        $TwigEnvironment->addFilter($bytesFilter);
        $TwigEnvironment->addFilter($extFilter);
        $TwigEnvironment->addFilter($metadataFilter);
        $TwigEnvironment->addFilter($csrfFilter);
        $TwigEnvironment->addFilter($notifWebFilter);
        $TwigEnvironment->addFilter($toIconFilter);
        $TwigEnvironment->addFilter($transFilter);
        $TwigEnvironment->addFilter($toDatetimeFilter);
        $TwigEnvironment->addFilter($extractJson);
        $TwigEnvironment->addFilter($extractDisplayMainText);
        $TwigEnvironment->addFilter($isInJsonArray);
        $TwigEnvironment->addFilter($canToHuman);
        $TwigEnvironment->addFilter($decrypt);
        $TwigEnvironment->addFilter($toSymbol);
        // functions
        $TwigEnvironment->addFunction($limitOptions);
        $TwigEnvironment->addFunction($generationTime);
        $TwigEnvironment->addFunction($memoryUsage);
        $TwigEnvironment->addFunction($numberOfQueries);
        $TwigEnvironment->addFunction($ext2icon);
        $TwigEnvironment->addFunction($sortIcon);
        $TwigEnvironment->addFunction($getExtendedSearchExample);

        // use the image BUILD_ID to use as parameter for loading assets
        // this helps with busting the cache in browsers
        $elabimgBuildId = getenv('ELABIMG_BUILD_ID') ?: App::INSTALLED_VERSION;
        $TwigEnvironment->addGlobal('v', $elabimgBuildId);

        return $TwigEnvironment;
    }
}
