<?php

/*
 * This file is part of the Cilex framework.
 *
 * (c) Mike van Riel <mike.vanriel@naenius.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Cilex;

use Symfony\Component\Finder\Finder;
use Symfony\Component\Process\Process;

/**
 * The Compiler class compiles the Cilex framework.
 *
 * This is an adapted version of the Silex\Compiler class.
 *
 * @author Fabien Potencier <fabien@symfony.com>
 * @author Mike van Riel <mike.vanriel@naenius.com>
 */
class Compiler
{
    protected $version;

    /**
     * Compiles the Cilex source code into one single Phar file.
     *
     * @param string $pharFile Name of the output Phar file
     */
    public function compile($pharFile = 'cilex.phar')
    {
        if (file_exists($pharFile)) {
            unlink($pharFile);
        }

        $process = new Process('git log --pretty="%h %ci" -n1 HEAD');
        if ($process->run() > 0) {
            throw new \RuntimeException('The git binary cannot be found.');
        }
        $this->version = trim($process->getOutput());

        $phar = new \Phar($pharFile, 0, 'cilex.phar');
        $phar->setSignatureAlgorithm(\Phar::SHA1);

        $phar->startBuffering();

        $finder = new Finder();
        $finder->files()
            ->ignoreVCS(true)
            ->name('*.php')
            ->notName('Compiler.php')
            ->in(__DIR__.'/..')
            ->in(__DIR__.'/../../vendor/pimple/pimple/lib')
            ->in(__DIR__.'/../../vendor/cilex/console-service-provider')
            ->in(__DIR__.'/../../vendor/symfony/console/Symfony/Component/Console');

        foreach ($finder as $file) {
            $this->addFile($phar, $file);
        }

        $this->addFile($phar, new \SplFileInfo(__DIR__.'/../../LICENSE'), false);

        $this->addFile($phar, new \SplFileInfo(__DIR__.'/../../vendor/autoload.php'));
        $this->addFile($phar, new \SplFileInfo(__DIR__.'/../../vendor/composer/ClassLoader.php'));
        $this->addFile($phar, new \SplFileInfo(__DIR__.'/../../vendor/composer/autoload_real.php'));
        $this->addFile($phar, new \SplFileInfo(__DIR__.'/../../vendor/composer/autoload_namespaces.php'));
        $this->addFile($phar, new \SplFileInfo(__DIR__.'/../../vendor/composer/autoload_classmap.php'));

        // Stubs
        $phar->setStub($this->getStub());

        $phar->stopBuffering();

        // $phar->compressFiles(\Phar::GZ);

        unset($phar);
    }

    protected function addFile(\Phar $phar, \splFileInfo $file, $strip = true)
    {
        $path = str_replace(dirname(dirname(__DIR__)).DIRECTORY_SEPARATOR, '', $file->getRealPath());

        $content = file_get_contents($file);
        if ($strip) {
            $content = self::stripWhitespace($content);
        }
        $content = str_replace('@package_version@', $this->version, $content);

        $phar->addFromString($path, $content);
    }

    protected function getStub()
    {
        return <<<'EOF'
<?php

/*
 * This file is part of the Cilex framework.
 *
 * (c) Mike van Riel <mike.vanriel@naenius.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

Phar::mapPhar('cilex.phar');

require_once 'phar://cilex.phar/vendor/autoload.php';

if ('cli' === php_sapi_name() && basename(__FILE__) === basename($_SERVER['argv'][0]) && isset($_SERVER['argv'][1])) {
    switch ($_SERVER['argv'][1]) {
        case 'update':
            $remoteFilename = 'http://cilex.github.com/get/cilex.phar';
            $localFilename = __DIR__.'/cilex.phar';

            file_put_contents($localFilename, file_get_contents($remoteFilename));
            break;

        case 'check':
            $latest = trim(file_get_contents('http://cilex.github.com/get/version'));

            if ($latest != Cilex\Application::VERSION) {
                printf("A newer Cilex version is available (%s).\n", $latest);
            } else {
                print("You are using the latest Cilex version.\n");
            }
            break;

        case 'version':
            printf("Cilex version %s\n", Cilex\Application::VERSION);
            break;

        default:
            printf("Unknown command '%s' (available commands: version, check, and update).\n", $_SERVER['argv'][1]);
    }

    exit(0);
}

__HALT_COMPILER();
EOF;
    }

    /**
     * Removes whitespace from a PHP source string while preserving line numbers.
     *
     * Based on Kernel::stripComments(), but keeps line numbers intact.
     *
     * @param string $source A PHP string
     *
     * @return string The PHP string with the whitespace removed
     */
    public static function stripWhitespace($source)
    {
        if (!function_exists('token_get_all')) {
            return $source;
        }

        $output = '';
        foreach (token_get_all($source) as $token) {
            if (is_string($token)) {
                $output .= $token;
            } elseif (in_array($token[0], array(T_COMMENT, T_DOC_COMMENT))) {
                $output .= str_repeat("\n", substr_count($token[1], "\n"));
            } elseif (T_WHITESPACE === $token[0]) {
                // reduce wide spaces
                $whitespace = preg_replace('{[ \t]+}', ' ', $token[1]);
                // normalize newlines to \n
                $whitespace = preg_replace('{(?:\r\n|\r|\n)}', "\n", $whitespace);
                // trim leading spaces
                $whitespace = preg_replace('{\n +}', "\n", $whitespace);
                $output .= $whitespace;
            } else {
                $output .= $token[1];
            }
        }

        return $output;
    }
}
