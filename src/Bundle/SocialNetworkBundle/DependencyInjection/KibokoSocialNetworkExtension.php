<?php

/*
 * This file is part of KibokoSocialNetworkBundle.
 *
 * (c) Grégory Planchat <gregory@kiboko.fr>
 *
 * Thanks to Vincent GUERARD <v.guerard@fulgurio.net> for his work on FulgurioSocialNetworkBundle
 */

namespace Kiboko\Bundle\SocialNetworkBundle\DependencyInjection;

use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;

/**
 * This is the class that loads and manages your bundle configuration.
 *
 * To learn more see {@link http://symfony.com/doc/current/cookbook/bundles/extension.html}
 */
class KibokoSocialNetworkExtension extends Extension
{
    private $defaultEmailValue;

    /**
     * {@inheritdoc}
     */
    public function load(array $configs, ContainerBuilder $container)
    {
        $configuration = new Configuration();
        $config = $this->processConfiguration($configuration, $configs);

        $loader = new Loader\YamlFileLoader($container, new FileLocator(__DIR__.'/../Resources/config'));
        $loader->load('services.yml');

        $this->defaultEmailValue = $config['from_email'];
        $this->addAvatarConfig($container, $config['avatar']);
        $this->addRegistrationConfig($container, $config['registration']);
        $this->addResettingConfig($container, $config['resetting']);
        $this->addConfirmationConfig($container, $config['confirmation']);
        $this->addMessengerConfig($container, $config['messenger']);
        $this->addContactConfig($container, $config['contact']);
        $this->addFriendshipConfig($container, $config['friendship']);
    }

    /**
     * Adding avatar data config.
     *
     * @param ContainerBuilder $container
     * @param array            $config
     */
    private function addAvatarConfig(ContainerBuilder $container, array $config)
    {
        $container->setParameter('kiboko_social_network.avatar.default', $config['default']);
        $container->setParameter('kiboko_social_network.avatar.width', $config['width']);
        $container->setParameter('kiboko_social_network.avatar.height', $config['height']);
        $this->addEmailsConfig($container, 'avatar.admin.remove.email', $config['admin']['remove']['email']);
    }

    /**
     * Adding registration data config.
     *
     * @param ContainerBuilder $container
     * @param array            $config
     */
    private function addRegistrationConfig(ContainerBuilder $container, array $config)
    {
        $this->addEmailsConfig($container, 'registration.email', $config['email']);
    }

    /**
     * Adding resetting data config.
     *
     * @param ContainerBuilder $container
     * @param array            $config
     */
    private function addResettingConfig(ContainerBuilder $container, array $config)
    {
        $this->addEmailsConfig($container, 'resetting.email', $config['email']);
    }

    /**
     * Adding confirmation data config.
     *
     * @param ContainerBuilder $container
     * @param array            $config
     */
    private function addConfirmationConfig(ContainerBuilder $container, array $config)
    {
        $this->addEmailsConfig($container, 'confirmation.email', $config['email']);
    }

    /**
     * Adding messenger data config.
     *
     * @param ContainerBuilder $container
     * @param array            $config
     */
    private function addMessengerConfig(ContainerBuilder $container, array $config)
    {
        $this->addEmailsConfig($container, 'messenger.message.email', $config['message']['email']);
        $this->addEmailsConfig($container, 'messenger.answer.email', $config['answer']['email']);
    }

    /**
     * Adding avatar data config.
     *
     * @param ContainerBuilder $container
     * @param array            $config
     */
    private function addContactConfig(ContainerBuilder $container, array $config)
    {
        $this->addEmailsConfig($container, 'contact.admin.email', $config['admin']['email']);
    }

    /**
     * Add friendship config.
     *
     * @param ContainerBuilder $container
     * @param array            $config
     */
    private function addFriendshipConfig(ContainerBuilder $container, array $config)
    {
        $container->setParameter('kiboko_social_network.friendship.nb_refusals', $config['nb_refusals']);
        $this->addEmailsConfig($container, 'friendship.email', $config['email']);
        $this->addEmailsConfig($container, 'friendship.email.invit', $config['email']['invit']);
        $this->addEmailsConfig($container, 'friendship.email.accept', $config['email']['accept']);
        $this->addEmailsConfig($container, 'friendship.email.refuse', $config['email']['refuse']);
        $this->addEmailsConfig($container, 'friendship.email.remove', $config['email']['remove']);
    }

    /**
     * Adding email data config.
     *
     * @param ContainerBuilder $container
     * @param string           $parameterName
     * @param array            $config
     */
    private function addEmailsConfig(ContainerBuilder $container, $parameterName, array $config)
    {
        $container->setParameter(
                'kiboko_social_network.'.$parameterName.'.from',
                isset($config['address']) ? $config['address'] : $this->defaultEmailValue['address']
        );
        $container->setParameter(
                'kiboko_social_network.'.$parameterName.'.from_name',
                isset($config['sender_name']) ? $config['sender_name'] : $this->defaultEmailValue['sender_name']
        );
        if (isset($config['subject'])) {
            $container->setParameter('kiboko_social_network.'.$parameterName.'.subject', $config['subject']);
        }
        if (isset($config['text'])) {
            $container->setParameter('kiboko_social_network.'.$parameterName.'.text', $config['text']);
        }
        if (isset($config['html'])) {
            $container->setParameter('kiboko_social_network.'.$parameterName.'.html', $config['html']);
        }
        if (isset($config['msn'])) {
            $container->setParameter('kiboko_social_network.'.$parameterName.'.msn', $config['msn']);
        }
    }
}
