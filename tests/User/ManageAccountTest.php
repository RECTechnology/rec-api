<?php

namespace App\Tests\User;

use App\DataFixtures\UserFixtures;
use App\DependencyInjection\Commons\DiscourseApiManager;
use App\Tests\BaseApiTest;

/**
 * Class ManageAccountTest
 * @package App\Tests\User
 */
class ManageAccountTest extends BaseApiTest
{

    function setUp(): void
    {
        parent::setUp();
    }

    function testUpdateB2BNameAccountShouldUpdateOnDiscourse()
    {
        $this->signIn(UserFixtures::TEST_REZERO_USER_2_CREDENTIALS);
        $user = $this->getSignedInUser();
        $params = array("name" => "changed name");
        $this->useDiscourseUpdateNameMock();
        $resp = $this->rest(
            'PUT',
            '/user/v3/accounts/'.$user->group_data->id,
            $params,
            [],
            200
        );

        self::assertIsObject($resp);
        self::assertTrue(property_exists($resp, 'name'));
        self::assertEquals("changed name", $resp->name);

    }

    function testUpdateB2BCompanyImageAccountShouldUpdateOnDiscourse()
    {
        $this->signIn(UserFixtures::TEST_REZERO_USER_2_CREDENTIALS);
        $user = $this->getSignedInUser();
        $this->useDiscourseChangeAvatarMock();
        $params = array("company_image" => 'https://rec.barcelona/wp-content/uploads/2018/12/RecNadal-2.jpg');
        $resp = $this->rest(
            'PUT',
            '/user/v3/accounts/'.$user->group_data->id,
            $params,
            [],
            200
        );

    }

    private function useDiscourseUpdateNameMock()
    {
        $discMock = $this->createMock(DiscourseApiManager::class);
        $response = $this->getUpdateNameMock();
        $discMock->method('updateName')->willReturn($response);

        $this->inject('net.app.commons.discourse.api_manager', $discMock);
    }

    private function useDiscourseChangeAvatarMock()
    {
        $discMock = $this->createMock(DiscourseApiManager::class);
        $discMock->method('updateCompanyImage')->willReturn(null);

        $this->inject('net.app.commons.discourse.api_manager', $discMock);
    }

    private function getUpdateNameMock(){
        return array (
            'success' => 'OK',
            'user' =>
                array (
                    'id' => 40,
                    'username' => 'anbton',
                    'name' => 'changed name',
                    'avatar_template' => '/letter_avatar_proxy/v4/letter/a/5f8ce5/{size}.png',
                    'email' => 'anbton@atarca-b2b.es',
                    'secondary_emails' =>
                        array (
                        ),
                    'unconfirmed_emails' =>
                        array (
                        ),
                    'last_posted_at' => NULL,
                    'last_seen_at' => NULL,
                    'created_at' => '2022-03-17T12:45:50.840Z',
                    'ignored' => false,
                    'muted' => false,
                    'can_ignore_user' => false,
                    'can_mute_user' => false,
                    'can_send_private_messages' => false,
                    'can_send_private_message_to_user' => false,
                    'trust_level' => 0,
                    'moderator' => false,
                    'admin' => false,
                    'title' => NULL,
                    'badge_count' => 0,
                    'user_fields' =>
                        array (
                            1 => NULL,
                        ),
                    'custom_fields' =>
                        array (
                        ),
                    'time_read' => 0,
                    'recent_time_read' => 0,
                    'primary_group_id' => NULL,
                    'primary_group_name' => NULL,
                    'primary_group_flair_url' => NULL,
                    'primary_group_flair_bg_color' => NULL,
                    'primary_group_flair_color' => NULL,
                    'featured_topic' => NULL,
                    'timezone' => NULL,
                    'can_edit' => true,
                    'can_edit_username' => true,
                    'can_edit_email' => true,
                    'can_edit_name' => true,
                    'uploaded_avatar_id' => NULL,
                    'has_title_badges' => false,
                    'pending_count' => 0,
                    'profile_view_count' => 0,
                    'second_factor_enabled' => false,
                    'second_factor_backup_enabled' => false,
                    'associated_accounts' =>
                        array (
                        ),
                    'can_upload_profile_header' => true,
                    'can_upload_user_card_background' => true,
                    'locale' => NULL,
                    'muted_category_ids' =>
                        array (
                        ),
                    'regular_category_ids' =>
                        array (
                        ),
                    'watched_tags' =>
                        array (
                        ),
                    'watching_first_post_tags' =>
                        array (
                        ),
                    'tracked_tags' =>
                        array (
                        ),
                    'muted_tags' =>
                        array (
                        ),
                    'tracked_category_ids' =>
                        array (
                        ),
                    'watched_category_ids' =>
                        array (
                        ),
                    'watched_first_post_category_ids' =>
                        array (
                        ),
                    'system_avatar_upload_id' => NULL,
                    'system_avatar_template' => '/letter_avatar_proxy/v4/letter/a/5f8ce5/{size}.png',
                    'muted_usernames' =>
                        array (
                        ),
                    'ignored_usernames' =>
                        array (
                        ),
                    'allowed_pm_usernames' =>
                        array (
                        ),
                    'mailing_list_posts_per_day' => 0,
                    'can_change_bio' => true,
                    'can_change_location' => true,
                    'can_change_website' => true,
                    'user_api_keys' => NULL,
                    'user_auth_tokens' =>
                        array (
                            0 =>
                                array (
                                    'id' => 70,
                                    'client_ip' => '173.212.198.201',
                                    'location' => 'desconocido',
                                    'browser' => 'navegador desconocido',
                                    'device' => 'dispositivo desconocido',
                                    'os' => 'sistema operativo desconocido',
                                    'icon' => 'question',
                                    'created_at' => '2022-03-17T12:45:51.286Z',
                                    'seen_at' => '2022-03-17T12:45:51.286Z',
                                    'is_active' => false,
                                ),
                        ),
                    'user_notification_schedule' =>
                        array (
                            'enabled' => false,
                            'day_0_start_time' => 480,
                            'day_0_end_time' => 1020,
                            'day_1_start_time' => 480,
                            'day_1_end_time' => 1020,
                            'day_2_start_time' => 480,
                            'day_2_end_time' => 1020,
                            'day_3_start_time' => 480,
                            'day_3_end_time' => 1020,
                            'day_4_start_time' => 480,
                            'day_4_end_time' => 1020,
                            'day_5_start_time' => 480,
                            'day_5_end_time' => 1020,
                            'day_6_start_time' => 480,
                            'day_6_end_time' => 1020,
                        ),
                    'use_logo_small_as_avatar' => false,
                    'featured_user_badge_ids' =>
                        array (
                        ),
                    'invited_by' => NULL,
                    'groups' =>
                        array (
                            0 =>
                                array (
                                    'id' => 10,
                                    'automatic' => true,
                                    'name' => 'nivel_de_confianza_0',
                                    'display_name' => 'nivel_de_confianza_0',
                                    'user_count' => 34,
                                    'mentionable_level' => 0,
                                    'messageable_level' => 0,
                                    'visibility_level' => 1,
                                    'primary_group' => false,
                                    'title' => NULL,
                                    'grant_trust_level' => NULL,
                                    'has_messages' => false,
                                    'flair_url' => NULL,
                                    'flair_bg_color' => NULL,
                                    'flair_color' => NULL,
                                    'bio_cooked' => NULL,
                                    'bio_excerpt' => NULL,
                                    'public_admission' => false,
                                    'public_exit' => false,
                                    'allow_membership_requests' => false,
                                    'full_name' => NULL,
                                    'default_notification_level' => 3,
                                    'membership_request_template' => NULL,
                                    'members_visibility_level' => 0,
                                    'can_see_members' => true,
                                    'publish_read_state' => false,
                                ),
                        ),
                    'group_users' =>
                        array (
                            0 =>
                                array (
                                    'group_id' => 10,
                                    'user_id' => 40,
                                    'notification_level' => 3,
                                    'owner' => false,
                                ),
                        ),
                    'user_option' =>
                        array (
                            'user_id' => 40,
                            'mailing_list_mode' => false,
                            'mailing_list_mode_frequency' => 1,
                            'email_digests' => true,
                            'email_level' => 1,
                            'email_messages_level' => 0,
                            'external_links_in_new_tab' => false,
                            'color_scheme_id' => NULL,
                            'dark_scheme_id' => NULL,
                            'dynamic_favicon' => false,
                            'enable_quoting' => true,
                            'enable_defer' => false,
                            'digest_after_minutes' => 10080,
                            'automatically_unpin_topics' => true,
                            'auto_track_topics_after_msecs' => 240000,
                            'notification_level_when_replying' => 2,
                            'new_topic_duration_minutes' => 2880,
                            'email_previous_replies' => 2,
                            'email_in_reply_to' => false,
                            'like_notification_frequency' => 1,
                            'include_tl0_in_digests' => false,
                            'theme_ids' =>
                                array (
                                    0 => 7,
                                ),
                            'theme_key_seq' => 0,
                            'allow_private_messages' => true,
                            'enable_allowed_pm_users' => false,
                            'homepage_id' => NULL,
                            'hide_profile_and_presence' => false,
                            'text_size' => 'normal',
                            'text_size_seq' => 0,
                            'title_count_mode' => 'notifications',
                            'timezone' => NULL,
                            'skip_new_user_tips' => false,
                        ),
                ),
        );
    }



}
