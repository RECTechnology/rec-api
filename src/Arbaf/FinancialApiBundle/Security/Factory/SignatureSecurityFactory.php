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

class SignatureSecurityFactory implements SecurityFactoryInterface {

    public function create(ContainerBuilder $container, $id, $config, $userProvider, $defaultEntryPoint)
    {
        $providerId = $id.'.signature.security.authentication.provider';
        $dd = new DefinitionDecorator('signature.security.authentication.provider');

        $container->setDefinition($providerId, $dd)
            ->replaceArgument(0, new Reference($userProvider));

        $listenerId = $id.'.signature.security.authentication.listener';
        $container->setDefinition(
            $listenerId, new DefinitionDecorator('signature.security.authentication.listener')
        );

        return array($providerId, $listenerId, $defaultEntryPoint);
    }

    public function getPosition() {
        return 'pre_auth';
    }

    public function getKey() {
        return 'signature_auth';
    }

    public function addConfiguration(NodeDefinition $builder){ }
}