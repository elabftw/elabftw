<?php
/**
 * @link      http://github.com/zendframework/zend-filter for the canonical source repository
 * @copyright Copyright (c) 2005-2016 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   http://framework.zend.com/license/new-bsd New BSD License
 */

namespace Zend\Filter;

class Module
{
    /**
     * Return default zend-filter configuration for zend-mvc applications.
     */
    public function getConfig()
    {
        $provider = new ConfigProvider();

        return [
            'service_manager' => $provider->getDependencyConfig(),
        ];
    }

    /**
     * Register a specification for the FilterManager with the ServiceListener.
     *
     * @param \Zend\ModuleManager\ModuleManager $moduleManager
     * @return void
     */
    public function init($moduleManager)
    {
        $event = $moduleManager->getEvent();
        $container = $event->getParam('ServiceManager');
        $serviceListener = $container->get('ServiceListener');

        $serviceListener->addServiceManager(
            'FilterManager',
            'filters',
            'Zend\ModuleManager\Feature\FilterProviderInterface',
            'getFilterConfig'
        );
    }
}
