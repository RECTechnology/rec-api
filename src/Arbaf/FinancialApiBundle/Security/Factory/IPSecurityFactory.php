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

class IPSecurityFactory implements SecurityFactoryInterface {

    public function create(ContainerBuilder $container, $id, $config, $userProvider, $defaultEntryPoint)
    {
        $providerId = $id.'.ip.security.authentication.provider';
        $dd = new DefinitionDecorator('ip.security.authentication.provider');

        $container->setDefinition($providerId, $dd)
            ->replaceArgument(0, new Reference($userProvider));

        $listenerId = $id.'.ip.security.authentication.listener';
        $container->setDefinition(
            $listenerId, new DefinitionDecorator('ip.security.authentication.listener')
        );

        return array($providerId, $listenerId, $defaultEntryPoint);
    }

    public function getPosition() {
        return 'pre_auth';
    }

    public function getKey() {
        return 'ip_auth';
    }

    public function addConfiguration(NodeDefinition $builder){ }
}