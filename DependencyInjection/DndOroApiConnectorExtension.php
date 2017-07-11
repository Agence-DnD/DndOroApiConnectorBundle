<?php

namespace Dnd\Bundle\DndOroApiConnectorBundle\DependencyInjection;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;
use Symfony\Component\DependencyInjection\Loader;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;

/**
 * Class DndOroApiConnectorExtension
 *
 * @package   Dnd\Bundle\DndOroApiConnectorBundle\DependencyInjection
 * @author    Auriau Maxime <maxime.auriau@dnd.fr>
 * @copyright Copyright (c) 2017 Agence Dn'D
 * @license   http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 * @link      http://www.dnd.fr/
 */
class DndOroApiConnectorExtension extends Extension
{
    /**
     * {@inheritdoc}
     */
    public function load(array $configs, ContainerBuilder $container)
    {
        /** @var YamlFileLoader  $loader */
        $loader = new Loader\YamlFileLoader($container, new FileLocator(__DIR__.'/../Resources/config'));
        $loader->load('repositories.yml');
        $loader->load('services.yml');
    }
}
