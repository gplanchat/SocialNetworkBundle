<?php

/*
 * This file is part of KibokoSocialNetworkBundle.
 *
 * (c) Grégory Planchat <gregory@kiboko.fr>
 *
 * Thanks to Vincent GUERARD <v.guerard@fulgurio.net> for his work on FulgurioSocialNetworkBundle
 */

namespace Kiboko\Bundle\SocialNetworkBundle\Tests\Controller;

use Symfony\Bundle\FrameworkBundle\Client;

/**
 * Front page controller tests.
 *
 * @author Vincent GUERARD <v.guerard@fulgurio.net>
 */
class MessengerControllerTest extends WebTestCase
{
    /**
     * Add new message test.
     */
    public function testAddMessageAction()
    {
        $client1 = $this->getUserLoggedClient('user1', 'user1');
        $user1 = $client1->getContainer()->get('security.context')->getToken()->getUser();
        $client10 = $this->getUserLoggedClient('user10', 'user10');
        $user10 = $client10->getContainer()->get('security.context')->getToken()->getUser();
        $crawler = $client1->request('GET', '/messenger/');
        $messengerExtension = $client1->getContainer()->get('kiboko_social_network.twig.messenger_extension');
        $this->assertCount(1, $crawler->filter('body:contains("kiboko_social.socialnetwork.no_message")'));

        // No message
        $this->assertSame(0, $messengerExtension->nbOfUnreadMessage($user1));
        $this->assertSame(0, $messengerExtension->nbOfUnreadMessage($user10));

        // Send message to $user10
        $crawler = $client1->request('GET', '/messenger/new');
        $formCrawler = $crawler->filter('form[action="/messenger/new"]');
//        $formCrawler->addHtmlContent('<input type="hidden" name="message[id_targets][]"/>');
        $form = $formCrawler->filter('button[type="submit"]')->form();
        $formData = [
            'message[username_target]' => 'user10,user11',
//            'message[id_targets][]' => $user10->getId(),
            'message[subject]' => 'New subject',
            'message[content]' => 'Content<br> of user1 message',
        ];
        $crawler = $client1->submit($form, $formData);

        // we check sent email
        $mailCollector = $client1->getProfile()->getCollector('swiftmailer');
        $this->assertSame(1, $mailCollector->getMessageCount());
        $collectedMessages = $mailCollector->getMessages();
        $message = $collectedMessages[0];
        $this->assertSame('kiboko_social.socialnetwork.email.message.subject', $message->getSubject());

        // New message created by user 1
        $this->assertSame(0, $messengerExtension->nbOfUnreadMessage($user1));
        $this->assertSame(1, $messengerExtension->nbOfUnreadMessage($user10));
    }

    /**
     * Reply to a message test.
     */
    public function testAddReplyMessageAction()
    {
        $client1 = $this->getUserLoggedClient('user1', 'user1');
        $user1 = $client1->getContainer()->get('security.context')->getToken()->getUser();
        $client10 = $this->getUserLoggedClient('user10', 'user10');
        $user10 = $client10->getContainer()->get('security.context')->getToken()->getUser();
        $messengerExtension = $client1->getContainer()->get('kiboko_social_network.twig.messenger_extension');

        $crawler = $client1->request('GET', '/messenger/new');
        $formCrawler = $crawler->filter('form[action="/messenger/new"]');
        $form = $formCrawler->filter('button[type="submit"]')->form();
        $formData = [
            'message[username_target]' => 'user10',
            'message[subject]' => 'New subject',
            'message[content]' => 'Content<br> of user1 message',
        ];
        $crawler = $client1->submit($form, $formData);

        $crawler = $client10->request('GET', '/messenger/');
        $crawlerLink = $crawler->filter('a:contains("New subject")');
        $this->assertCount(1, $crawlerLink);
        $crawler = $client10->click($crawlerLink->link());

        // New message has been read
        $this->assertSame(0, $messengerExtension->nbOfUnreadMessage($user1));
        $this->assertSame(0, $messengerExtension->nbOfUnreadMessage($user10));

        $form = $crawler->filter('form[action$="/reply"] button[type="submit"]')->form();
        $form['answer[content]'] = 'Reply of user10';
        $crawler = $client10->submit($form);

        // we check sent email
        $mailCollector = $client1->getProfile()->getCollector('swiftmailer');
        $this->assertSame(1, $mailCollector->getMessageCount());
        $collectedMessages = $mailCollector->getMessages();
        $message = $collectedMessages[0];
        $this->assertSame('kiboko_social.socialnetwork.email.message.subject', $message->getSubject());

        // New message created by user 1
        $this->assertSame(1, $messengerExtension->nbOfUnreadMessage($user1));
        $this->assertSame(0, $messengerExtension->nbOfUnreadMessage($user10));

        $crawler = $client1->request('GET', '/messenger/');
        $crawlerLink = $crawler->filter('a:contains("New subject")');
        $crawler = $client1->click($crawlerLink->link());

        $this->assertSame(0, $messengerExtension->nbOfUnreadMessage($user1));
        $this->assertSame(0, $messengerExtension->nbOfUnreadMessage($user10));
    }

    /**
     * Message remove test.
     */
    public function testRemoveMessageAction()
    {
        $client1 = $this->getUserLoggedClient('user1', 'user1');
        $crawler = $client1->request('GET', '/messenger/');

        $doctrine = $client1->getContainer()->get('doctrine');
        $messageRepository = $doctrine->getRepository('KibokoSocialNetworkBundle:Message');
        $messageTargetRepository = $doctrine->getRepository('KibokoSocialNetworkBundle:MessageTarget');
        $messages = $messageRepository->findAll();
        $messageTargets = $messageTargetRepository->findAll();
        $this->assertCount(0, $messages);
        $this->assertCount(0, $messageTargets);

        // Send message to $user10
        $crawler = $client1->request('GET', '/messenger/new');
        $formCrawler = $crawler->filter('form[action="/messenger/new"]');
        $form = $formCrawler->filter('button[type="submit"]')->form();
        $formData = [
            'message[username_target]' => 'user10',
            'message[subject]' => 'New subject',
            'message[content]' => 'Content<br> of user1 message',
        ];
        $crawler = $client1->submit($form, $formData);

        $messages = $messageRepository->findAll();
        $messageTargets = $messageTargetRepository->findAll();
        $this->assertCount(1, $messages);
        $this->assertCount(2, $messageTargets);

        $this->removeMessage($client1);

        // Message is not removed because client10 has this message
        $messages = $messageRepository->findAll();
        $messageTargets = $messageTargetRepository->findAll();
        $this->assertCount(1, $messages);
        $this->assertCount(1, $messageTargets);

        $client10 = $this->getUserLoggedClient('user10', 'user10');
        $this->removeMessage($client10);

        // All messages has been removed
        $messages = $messageRepository->findAll();
        $messageTargets = $messageTargetRepository->findAll();
        $this->assertCount(0, $messages);
        $this->assertCount(0, $messageTargets);
    }

    /**
     * Remove all messages and messages target, with reply.
     */
    public function testRemoveMessageWithChildrenAction()
    {
        $client1 = $this->getUserLoggedClient('user1', 'user1');
        $user1 = $client1->getContainer()->get('security.context')->getToken()->getUser();
        $doctrine = $client1->getContainer()->get('doctrine');
        $client10 = $this->getUserLoggedClient('user10', 'user10');
        $user10 = $client10->getContainer()->get('security.context')->getToken()->getUser();
        $messengerExtension = $client1->getContainer()->get('kiboko_social_network.twig.messenger_extension');

        $crawler = $client1->request('GET', '/messenger/new');
        $formCrawler = $crawler->filter('form[action="/messenger/new"]');
        $form = $formCrawler->filter('button[type="submit"]')->form();
        $formData = [
            'message[username_target]' => 'user10',
            'message[subject]' => 'New subject',
            'message[content]' => 'Content<br> of user1 message',
        ];
        $crawler = $client1->submit($form, $formData);

        $crawler = $client10->request('GET', '/messenger/');
        $crawlerLink = $crawler->filter('a:contains("New subject")');
        $crawler = $client10->click($crawlerLink->link());

        $form = $crawler->filter('form[action$="/reply"] button[type="submit"]')->form();
        $form['answer[content]'] = 'Reply of user10';
        $crawler = $client10->submit($form);

        $crawler = $client1->request('GET', '/messenger/');
        $crawlerLink = $crawler->filter('a:contains("New subject")');
        $crawler = $client1->click($crawlerLink->link());

        $messageRepository = $doctrine->getRepository('KibokoSocialNetworkBundle:Message');
        $messageTargetRepository = $doctrine->getRepository('KibokoSocialNetworkBundle:MessageTarget');
        $messages = $messageRepository->findAll();
        $messageTargets = $messageTargetRepository->findAll();
        $this->assertCount(2, $messages);
        $this->assertCount(4, $messageTargets);

        $this->removeMessage($client1);
        $messages = $messageRepository->findAll();
        $messageTargets = $messageTargetRepository->findAll();
        $this->assertCount(2, $messages);
        $this->assertCount(2, $messageTargets);

        $this->removeMessage($client10);
        $messages = $messageRepository->findAll();
        $messageTargets = $messageTargetRepository->findAll();
        $this->assertCount(0, $messages);
        $this->assertCount(0, $messageTargets);
    }

    /**
     * Remove message.
     *
     * @param Client $client
     */
    private function removeMessage(Client $client)
    {
        // We remove message
        $crawler = $client->request('GET', '/messenger/');
        $crawlerLink = $crawler->filter('ol.messagesList a[href$="/remove"]');
        $crawler = $client->click($crawlerLink->link());

        // We confirm refusal action
        $buttonYes = $crawler->selectButton('kiboko_social.socialnetwork.yes');
        $form = $buttonYes->form();
        $client->submit($form);
    }
}
