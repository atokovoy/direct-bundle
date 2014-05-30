<?php
namespace Neton\DirectBundle\DependencyInjection;

use Symfony\Component\HttpKernel\DependencyInjection\Extension;
use Symfony\Component\DependencyInjection\Loader\XmlFileLoader;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\Config\FileLocator;

/**
 * DirectExtension is an extension for the ExtDirect.
 *
 * @author Otavio Fernandes <otavio@neton.com.br>
 */
class DirectExtension extends Extension
{
    /**
     * Loads the Direct configuration.
     *
     * @param array $config An array of configuration settings
     * @param ContainerBuilder $container A ContainerBuilder instance
     */
    public function load(array $configs, ContainerBuilder $container)
    {
        $loader = new XmlFileLoader($container, new FileLocator(__DIR__.'/../Resources/config'));
        $loader->load('direct.xml');

        foreach ($configs as $config) {
            $this->registerApiConfiguration($config, $container);
        }
    }

    /**
     * Register the api configuration to container.
     *
     * @param array            $config    An array of configuration settings
     * @param ContainerBuilder $container A ContainerBuilder instance
     */
    protected function registerApiConfiguration($config, ContainerBuilder $container)
    {
        if (isset($config['api']['route_name'])) {
            $container->setParameter('direct.api.route_name', $config['api']['route_name']);
        }

        if (isset($config['api']['use_absolute_url'])) {
            $container->setParameter('direct.api.use_absolute_url', $config['api']['use_absolute_url']);
        }

        if (isset($config['api']['allow_remote_configuration'])) {
            $container->setParameter('direct.api.allow_remote_configuration', $config['api']['allow_remote_configuration']);
        }

        if (isset($config['api']['type'])) {
            $container->setParameter('direct.api.type', $config['api']['type']);
        }

        if (isset($config['api']['namespace'])) {
            $container->setParameter('direct.api.namespace', $config['api']['namespace']);
        }

        if (isset($config['api']['id'])) {
            $container->setParameter('direct.api.id', $config['api']['id']);
        }

        if (isset($config['api']['remote_attribute'])) {
            $container->setParameter('direct.api.remote_attribute', $config['api']['remote_attribute']);
        }

        if (isset($config['api']['form_attribute'])) {
            $container->setParameter('direct.api.form_attribute', $config['api']['form_attribute']);
        }

        if (isset($config['api']['safe_attribute'])) {
            $container->setParameter('direct.api.safe_attribute', $config['api']['safe_attribute']);
        }

        if (isset($config['api']['unsafe_attribute'])) {
            $container->setParameter('direct.api.unsafe_attribute', $config['api']['unsafe_attribute']);
        }

        if (isset($config['api']['default_access'])) {
            $container->setParameter('direct.api.default_access', $config['api']['default_access']);
        }

        if (isset($config['api']['session_attribute'])) {
            $container->setParameter('direct.api.session_attribute', $config['api']['session_attribute']);
        }

        if (isset($config['exception']['message'])) {
            $container->setParameter('direct.exception.message', $config['exception']['message']);
        }

    }

    /**
     * Returns the base path for the XSD files.
     *
     * @return string The XSD base path
     */
    public function getXsdValidationBasePath()
    {
        return __DIR__.'/../Resources/config/schema';
    }

    public function getNamespace()
    {
        return 'http://www.neton.com.br/schema/dic/direct';
    }
}
