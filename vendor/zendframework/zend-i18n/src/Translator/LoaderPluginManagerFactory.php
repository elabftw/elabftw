<?php
/**
 * @link      http://github.com/zendframework/zend-i18n for the canonical source repository
 * @copyright Copyright (c) 2005-2016 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   http://framework.zend.com/license/new-bsd New BSD License
 */

namespace Zend\I18n\Translator;

use Interop\Container\ContainerInterface;
use Zend\ServiceManager\FactoryInterface;
use Zend\ServiceManager\ServiceLocatorInterface;

class LoaderPluginManagerFactory implements FactoryInterface
{
    /**
     * zend-servicemanager v2 options passed to factory.
     *
     * @param array
     */
    protected $creationOptions = [];

    /**
     * Create and return a LoaderPluginManager.
     *
     * @param ContainerInterface $container
     * @param string $name
     * @param null|array $options
     * @return LoaderPluginManager
     */
    public function __invoke(ContainerInterface $container, $name, array $options = null)
    {
        $options = $options ?: [];
        return new LoaderPluginManager($container, $options);
    }

    /**
     * zend-servicemanager v2 factory to return LoaderPluginManager
     *
     * @param ServiceLocatorInterface $container
     * @return LoaderPluginManager
     */
    public function createService(ServiceLocatorInterface $container)
    {
        return $this($container, 'TranslatorPluginManager', $this->creationOptions);
    }

    /**
     * v2 support for instance creation options.
     *
     * @param array $options
     * @return void
     */
    public function setCreationOptions(array $options)
    {
        $this->creationOptions = $options;
    }
}
