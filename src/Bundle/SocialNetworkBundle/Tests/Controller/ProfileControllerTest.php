<?php

/*
 * This file is part of KibokoSocialNetworkBundle.
 *
 * (c) Grégory Planchat <gregory@kiboko.fr>
 *
 * Thanks to Vincent GUERARD <v.guerard@fulgurio.net> for his work on FulgurioSocialNetworkBundle
 */

namespace Kiboko\Bundle\SocialNetworkBundle\Tests\Controller;

/**
 * Profil controller tests.
 *
 * @author Vincent GUERARD <v.guerard@fulgurio.net>
 */
class ProfileControllerTest extends WebTestCase
{
    /**
     * User data.
     *
     * @var array
     */
    private $userData = [
        'username' => 'user1',
        'email' => 'user1@example.com',
        'password' => 'user1',
    ];

    /**
     * Show profil page test.
     */
    public function testShowAction()
    {
        $client = self::createClient();
        $crawler = $client->request('GET', '/profile/');
        $this->assertTrue($client->getResponse()->isRedirect('/login'));

        $client = $this->getUserLoggedClient($this->userData['username'], $this->userData['password']);
        $crawler = $client->request('GET', '/profile/');
        $this->assertSame('kiboko_social.socialnetwork.profile.username: '.$this->userData['username'], $crawler->filter('section p')->first()->text());
        $this->assertSame('kiboko_social.socialnetwork.profile.email: '.$this->userData['email'], $crawler->filter('section p:nth-child(3)')->text());
    }

    /**
     * Edit profil page test.
     */
    public function testEditWithEmptyFormAction()
    {
        $client = self::createClient();
        $crawler = $client->request('GET', '/profile/');
        $this->assertTrue($client->getResponse()->isRedirect('/login'));

        $client = $this->getUserLoggedClient($this->userData['username'], $this->userData['password']);
        $crawler = $client->request('GET', '/profile/edit');

        $data = [
            'fos_user_profile_form[user][username]' => '',
            'fos_user_profile_form[user][email]' => '',
            'fos_user_profile_form[current]' => '',
        ];
        $form = $crawler->filter('form[action$="profile/edit"] button[name="_submit"]')->form();

        $crawler = $client->submit($form, $data);
        $this->assertCount(1, $crawler->filter('div.alert.alert-error:contains("fos_user.username.blank")'));
        $this->assertCount(1, $crawler->filter('div.alert.alert-error:contains("fos_user.email.blank")'));
        $this->assertCount(1, $crawler->filter('div.alert.alert-error:contains("fos_user.current_password.invalid")'));
    }

    /**
     * Edit profil page test.
     */
    public function testEditWithExistingUserAction()
    {
        $client = $this->getUserLoggedClient($this->userData['username'], $this->userData['password']);
        $crawler = $client->request('GET', '/profile/edit');

        $data = [
            'fos_user_profile_form[user][username]' => 'userDisabled',
            'fos_user_profile_form[user][email]' => 'userDisabled@example.com',
            'fos_user_profile_form[current]' => $this->userData['password'],
        ];
        $form = $crawler->filter('form[action$="profile/edit"] button[name="_submit"]')->form();

        $crawler = $client->submit($form, $data);
        $this->assertCount(1, $crawler->filter('div.alert.alert-error:contains("fos_user.username.already_used")'));
        $this->assertCount(1, $crawler->filter('div.alert.alert-error:contains("fos_user.email.already_used")'));
    }

    /**
     * Edit profil page test.
     */
    public function testEditWithoutPasswordChangeAction()
    {
        $client = $this->getUserLoggedClient($this->userData['username'], $this->userData['password']);
        $crawler = $client->request('GET', '/profile/edit');

        $data = [
            'fos_user_profile_form[user][username]' => 'foobar',
            'fos_user_profile_form[user][email]' => 'foobar@example.com',
            'fos_user_profile_form[user][plainPassword][first]' => '',
            'fos_user_profile_form[user][plainPassword][second]' => '',
            'fos_user_profile_form[current]' => $this->userData['password'],
        ];
        $userBeforeSave = $client->getContainer()->get('doctrine')->getEntityManager()->getRepository('KibokoSocialNetworkBundle:User')->findOneBy(['username' => $this->userData['username']]);
        $form = $crawler->filter('form[action$="profile/edit"] button[name="_submit"]')->form();

        $client->submit($form, $data);
        $crawler = $client->followRedirect();
        $this->assertSame('kiboko_social.socialnetwork.profile.username: '.$data['fos_user_profile_form[user][username]'], $crawler->filter('section p')->first()->text());
        $this->assertSame('kiboko_social.socialnetwork.profile.email: '.$data['fos_user_profile_form[user][email]'], $crawler->filter('section p:nth-child(3)')->text());
        $userAfterSave = $client->getContainer()->get('doctrine')->getEntityManager()->getRepository('KibokoSocialNetworkBundle:User')->findOneBy(['username' => $data['fos_user_profile_form[user][username]']]);
        $this->assertSame($userBeforeSave->getPassword(), $userAfterSave->getPassword());
    }

    /**
     * Edit profil page test.
     */
    public function testEditAction()
    {
        $client = $this->getUserLoggedClient($this->userData['username'], $this->userData['password']);
        $crawler = $client->request('GET', '/profile/edit');

        $userBeforeSave = $client->getContainer()->get('doctrine')->getEntityManager()->getRepository('KibokoSocialNetworkBundle:User')->findOneBy(['username' => $this->userData['username']]);
        $data = [
            'fos_user_profile_form[user][username]' => 'foobar',
            'fos_user_profile_form[user][email]' => 'foobar@example.com',
            'fos_user_profile_form[user][plainPassword][first]' => 'foobar',
            'fos_user_profile_form[user][plainPassword][second]' => 'foobar',
            'fos_user_profile_form[current]' => $this->userData['password'],
        ];
        $form = $crawler->filter('form[action$="profile/edit"] button[name="_submit"]')->form();

        $client->submit($form, $data);
        $crawler = $client->followRedirect();
        $this->assertSame('kiboko_social.socialnetwork.profile.username: '.$data['fos_user_profile_form[user][username]'], $crawler->filter('section p')->first()->text());
        $this->assertSame('kiboko_social.socialnetwork.profile.email: '.$data['fos_user_profile_form[user][email]'], $crawler->filter('section p:nth-child(3)')->text());
        $this->assertTrue('/bundles/kiboko_socialsocialnetwork/images/avatar.png' === $crawler->filter('section img')->attr('src'));

        $userAfterSave = $client->getContainer()->get('doctrine')->getEntityManager()->getRepository('KibokoSocialNetworkBundle:User')->findOneBy(['username' => $data['fos_user_profile_form[user][username]']]);
        $this->assertNotSame($userBeforeSave->getPassword(), $userAfterSave->getPassword());

        $encoder = $client->getContainer()->get('security.encoder_factory')->getEncoder($userAfterSave);
        $encryptedPassword = $encoder->encodePassword($data['fos_user_profile_form[user][username]'], $userAfterSave->getSalt());

        $this->assertSame($encryptedPassword, $userAfterSave->getPassword());
    }

    /**
     * Edit profil with avatar upload page test.
     */
    public function testEditWithUploadAction()
    {
        $client = $this->getUserLoggedClient($this->userData['username'], $this->userData['password']);
        $crawler = $client->request('GET', '/profile/edit');

        $userBeforeSave = $client->getContainer()->get('doctrine')->getEntityManager()->getRepository('KibokoSocialNetworkBundle:User')->findOneBy(['username' => $this->userData['username']]);
        $form = $crawler->filter('form[action$="profile/edit"] button[name="_submit"]')->form();
        $form['fos_user_profile_form[user][avatarFile]'] = __DIR__.'/../DataFixtures/icon.png';
        $form['fos_user_profile_form[current]'] = $this->userData['password'];

        $client->submit($form);
        $crawler = $client->followRedirect();
        $this->assertSame('kiboko_social.socialnetwork.profile.username: '.$this->userData['username'], $crawler->filter('section p')->first()->text());
        $this->assertSame('kiboko_social.socialnetwork.profile.email: '.$this->userData['email'], $crawler->filter('section p:nth-child(3)')->text());
        $this->assertFalse('/bundles/kiboko_socialsocialnetwork/images/avatar.png' === $crawler->filter('section img')->attr('src'));
    }

    /**
     * Unsubscribe page test.
     */
    public function testUnsubscribeAction()
    {
        $client = self::createClient();
        $crawler = $client->request('GET', '/profile/');
        $this->assertTrue($client->getResponse()->isRedirect('/login'));

        $client = $this->getUserLoggedClient($this->userData['username'], $this->userData['password']);
        $crawler = $client->followRedirect();

        // Authentified
        $security = $client->getContainer()->get('security.context');
        $this->assertTrue($security->isGranted('ROLE_USER'));

        $crawler = $client->request('GET', '/unsubscribe');
        $buttonNo = $crawler->filter('a:contains("kiboko_social.socialnetwork.no")')->link();
        $crawler = $client->click($buttonNo);
        $security = $client->getContainer()->get('security.context');
        $this->assertTrue($security->isGranted('ROLE_USER'));

        $crawler = $client->request('GET', '/unsubscribe');
        $buttonYes = $crawler->selectButton('kiboko_social.socialnetwork.yes');
        $form = $buttonYes->form();
        $client->submit($form);

        $this->assertTrue($client->getResponse()->isRedirect('/logout'));
        $client->followRedirect();
        $this->assertTrue($client->getResponse()->isRedirect('http://localhost/'));
        $crawler = $client->followRedirect();

        $security = $client->getContainer()->get('security.context');
        $this->assertFalse($security->isGranted('ROLE_USER'));

        // Try to reconnect
        $client = $this->getUserLoggedClient($this->userData['username'], $this->userData['password']);
        $this->assertTrue($client->getResponse()->isRedirect('http://localhost/login'));
        $client->followRedirect();

        $security = $client->getContainer()->get('security.context');
        $this->assertFalse($security->isGranted('ROLE_USER'));
    }
}
