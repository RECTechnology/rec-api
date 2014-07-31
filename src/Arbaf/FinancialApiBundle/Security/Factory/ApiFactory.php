<?php
/**
 * Created by PhpStorm.
 * User: lluis
 * Date: 6/26/14
 * Time: 2:11 PM
 */

namespace Arbaf\FinancialApiBundle\Security\Factory;

use Symfony\Bundle\SecurityBundle\DependencyInjection\Security\Factory\SecurityFactoryInterface;
use Symfony\Component\Config\Definition\Builder\NodeDefinition;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\DefinitionDecorator;
use Symfony\Component\DependencyInjection\Reference;

class ApiFactory implements SecurityFactoryInterface {

    public function create(ContainerBuilder $container, $id, $config, $userProvider, $defaultEntryPoint)
    {
        $providerId = 'security.authentication.provider.api.'.$id;
        $dd = new DefinitionDecorator('api.security.authentication.provider');

        $container->setDefinition($providerId, $dd)->replaceArgument(0, new Reference($userProvider));

        $listenerId = 'security.authentication.listener.api.'.$id;
        $container->setDefinition($listenerId, new DefinitionDecorator('api.security.authentication.listener'));

        return array($providerId, $listenerId, $defaultEntryPoint);
    }

    public function getPosition()
    {
        return 'pre_auth';
    }

    public function getKey()
    {
        return 'api_arbaf';
    }

    public function addConfiguration(NodeDefinition $builder){ }
}