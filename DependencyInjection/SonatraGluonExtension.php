<?php

/*
 * This file is part of the Sonatra package.
 *
 * (c) François Pluchino <francois.pluchino@sonatra.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Sonatra\Bundle\GluonBundle\DependencyInjection;

use Fxp\Component\RequireAsset\Assetic\Util\ResourceUtils;
use Fxp\Component\RequireAsset\Tag\Config\RequireStyleTagConfiguration;
use Symfony\Component\Config\Definition\Processor;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader;
use Symfony\Component\Config\FileLocator;

/**
 * This is the class that loads and manages your bundle configuration.
 *
 * @author François Pluchino <francois.pluchino@sonatra.com>
 */
class SonatraGluonExtension extends Extension
{
    /**
     * {@inheritdoc}
     */
    public function load(array $configs, ContainerBuilder $container)
    {
        $configuration = new Configuration();
        $config = $this->processConfiguration($configuration, $configs);

        $loader = new Loader\XmlFileLoader($container, new FileLocator(__DIR__.'/../Resources/config'));
        $loader->load('response.xml');
        $loader->load('block.xml');
        $loader->load('form.xml');
        $loader->load('assetic_variables.xml');

        $this->configGoogleFonts($config['google_fonts'], $container);
        $this->configFontAwesome($config['font_awesome'], $container);
    }

    /**
     * Configures the google fonts resource.
     *
     * @param array            $config    The config
     * @param ContainerBuilder $container The container builder
     */
    protected function configGoogleFonts(array $config, ContainerBuilder $container)
    {
        $url = 'https://fonts.googleapis.com/css?family=%s:%s';
        $inputs = array();

        /* @var array $weights */
        foreach ($config['fonts'] as $name => $weights) {
            $name = str_replace(' ', '+', $name);
            $weights = implode(',', $weights);
            $inputs[] = sprintf($url, $name, $weights);
        }

        if (count($inputs) > 0) {
            $this->addGoogleFontsLoader($container);
            $this->addGoogleFontsResource($container, $config, $inputs);
            $this->addTwigRequireTag($container, $config['common_name'], $config['attributes']);
        }
    }

    /**
     * Configure the font awesome.
     *
     * @param array            $config    The config
     * @param ContainerBuilder $container The container builder
     */
    protected function configFontAwesome(array $config, ContainerBuilder $container)
    {
        if ($config['enabled']) {
            $this->addTwigRequireTag($container, $config['path'], $config['attributes']);
        }
    }

    /**
     * Add google fonts loader.
     *
     * @param ContainerBuilder $container The container builder
     */
    protected function addGoogleFontsLoader(ContainerBuilder $container)
    {
        $def = new Definition('Symfony\Bundle\AsseticBundle\Factory\Loader\ConfigurationLoader');
        $def->setPublic(false);
        $def->addTag('assetic.formula_loader', array('alias' => 'google_fonts_loader'));
        $container->setDefinition('sonatra_gluon.assetic.loader.google_fonts', $def);
    }

    /**
     * Add google fonts resource.
     *
     * @param ContainerBuilder $container The container builder
     * @param array            $config    The config of fonts resource
     * @param array            $inputs    The list of font stylesheets
     */
    protected function addGoogleFontsResource(ContainerBuilder $container, array $config, array $inputs)
    {
        $prefix = $container->getParameter('fxp_require_asset.output_prefix').'/';
        $args = array($config['common_name'] => array(
            $inputs,
            ResourceUtils::cleanDebugFilters($config['filters'], $container->getParameter('assetic.debug')),
            array_merge($config['options'], array('output' => $prefix.$config['output'], 'debug' => false)),
        ));

        $definition = new Definition('Symfony\Bundle\AsseticBundle\Factory\Resource\ConfigurationResource', array($args));
        $definition->setPublic(false);
        $definition->addTag('assetic.formula_resource', array('loader' => 'google_fonts_loader'));

        $container->setDefinition('sonatra_gluon.assetic.resource.google_fonts', $definition);
    }

    /**
     * Add twig require tag.
     *
     * @param ContainerBuilder $container  The container builder
     * @param string           $name       The assetic name of google fonts resource
     * @param array            $attributes The HTML tag attributes
     */
    protected function addTwigRequireTag(ContainerBuilder $container, $name, array $attributes = array())
    {
        $processor = new Processor();
        $configuration = new RequireStyleTagConfiguration();
        $attributes = $processor->process($configuration->getNode(), array($attributes));

        $definition = new Definition('Fxp\Component\RequireAsset\Tag\RequireStyleTag', array($name, $attributes));
        $definition->setPublic(false);
        $definition->addTag('fxp_require_asset.require_tag');

        $container->setDefinition('sonatra_gluon.twig.require_tag.'.$name, $definition);
    }
}
