<?php

namespace App\Tests\Discourse;

use App\DataFixtures\UserFixtures;
use App\Tests\BaseApiTest;

class DiscourseNotificationsTest extends BaseApiTest{
    function setUp(): void
    {
        parent::setUp();
    }

    function testPostNotification(){

        $route = '/notifications/v1/discourse';
        $data = $this->getAnbtonPostCreatedNotificationData();

        $headers = array(
            'x-discourse-event' => 'post_created',
            'x-discourse-event-type' => 'post',
            'x-discourse-event-id' => 1,
            'x-discourse-event-instance' => 'https://community.stage.atarca.es',
            'content-type' => 'application/json',
            'connection' => 'close',
            'accept' => '*/*',
            'user-agent' => 'Discourse/2.7.13'
        );
        $resp = $this->requestJson('POST', $route, $data, $headers);
        $content = json_decode($resp->getContent(),true);
        self::assertArrayHasKey('data', $content);

        $this->getAwardsFromAnbtonAccount();

        $data = $this->getRezero3PostCreatedNotificationData();
        $resp = $this->requestJson('POST', $route, $data, $headers);
        $content = json_decode($resp->getContent(),true);
        self::assertArrayHasKey('data', $content);

        $this->getAwardsFromUser3Account();

    }

    function testLikeNotification(){

        $route = '/notifications/v1/discourse';
        $data = $this->getLikeNotification();

        $headers = array(
            'x-discourse-event' => 'post_liked',
            'x-discourse-event-type' => 'like',
            'x-discourse-event-id' => 1,
            'x-discourse-event-instance' => 'https://community.stage.atarca.es',
            'content-type' => 'application/json',
            'connection' => 'close',
            'accept' => '*/*',
            'user-agent' => 'Discourse/2.7.13'
        );
        $resp = $this->requestJson('POST', $route, $data, $headers);
        $content = json_decode($resp->getContent(),true);
        self::assertArrayHasKey('data', $content);

        $this->getAwardsFromAnbtonAccount();
        $this->notifyDislike();
        //TODO get awards again and check substracted scores

    }

    function notifyDislike(){
        $route = '/notifications/v1/discourse';
        $data = $this->getDislikeNotification();

        $headers = array(
            'x-discourse-event' => 'post_like_removed',
            'x-discourse-event-type' => 'post_like_removed',
            'x-discourse-event-id' => 1,
            'x-discourse-event-instance' => 'https://community.stage.atarca.es',
            'content-type' => 'application/json',
            'connection' => 'close',
            'accept' => '*/*',
            'user-agent' => 'Discourse/2.7.13'
        );
        $resp = $this->requestJson('POST', $route, $data, $headers);
        $content = json_decode($resp->getContent(),true);
        self::assertArrayHasKey('data', $content);
    }

    function getAnbtonPostCreatedNotificationData(){
        return array (
            'post' =>
                array (
                    'id' => 582,
                    'name' => 'almohadas rana',
                    'username' => 'anbton',
                    'avatar_template' => '/user_avatar/community.stage.atarca.es/rana/{size}/98_2.png',
                    'created_at' => '2022-05-10T10:23:11.638Z',
                    'cooked' => '<p>Ahora mismo los webhooks estan siendo notificados en este endpoint por si quereis ir comprobando cuando realiceis acciones</p>
<p><a href="https://webhook.site/#!/2b7aa46d-b771-4c45-bf54-a8e839ad6669" class="onebox" target="_blank" rel="noopener nofollow ugc">https://webhook.site/#!/2b7aa46d-b771-4c45-bf54-a8e839ad6669</a></p>',
                    'post_number' => 2,
                    'post_type' => 1,
                    'updated_at' => '2022-05-10T10:23:11.638Z',
                    'reply_count' => 0,
                    'reply_to_post_number' => NULL,
                    'quote_count' => 0,
                    'incoming_link_count' => 0,
                    'reads' => 0,
                    'score' => 0,
                    'topic_id' => 289,
                    'topic_slug' => 'probando-webhooks',
                    'topic_title' => 'Probando webhooks',
                    'category_id' => 5,
                    'display_username' => 'almohadas rana',
                    'primary_group_name' => NULL,
                    'version' => 1,
                    'user_title' => NULL,
                    'bookmarked' => false,
                    'raw' => 'Ahora mismo los webhooks estan siendo notificados en este endpoint por si quereis ir comprobando cuando realiceis acciones

https://webhook.site/#!/2b7aa46d-b771-4c45-bf54-a8e839ad6669',
                    'moderator' => false,
                    'admin' => false,
                    'staff' => false,
                    'user_id' => 76,
                    'hidden' => false,
                    'trust_level' => 0,
                    'deleted_at' => NULL,
                    'user_deleted' => false,
                    'edit_reason' => NULL,
                    'wiki' => false,
                    'reviewable_id' => NULL,
                    'reviewable_score_count' => 0,
                    'reviewable_score_pending_count' => 0,
                    'topic_posts_count' => 1,
                    'topic_filtered_posts_count' => 1,
                    'topic_archetype' => 'regular',
                    'category_slug' => 'actualitat',
                ),
        );
    }

    function getRezero3PostCreatedNotificationData(){
        return array (
            'post' =>
                array (
                    'id' => 582,
                    'name' => 'almohadas rana',
                    'username' => 'rezero3',
                    'avatar_template' => '/user_avatar/community.stage.atarca.es/rana/{size}/98_2.png',
                    'created_at' => '2022-05-10T10:23:11.638Z',
                    'cooked' => '<p>Ahora mismo los webhooks estan siendo notificados en este endpoint por si quereis ir comprobando cuando realiceis acciones</p>
<p><a href="https://webhook.site/#!/2b7aa46d-b771-4c45-bf54-a8e839ad6669" class="onebox" target="_blank" rel="noopener nofollow ugc">https://webhook.site/#!/2b7aa46d-b771-4c45-bf54-a8e839ad6669</a></p>',
                    'post_number' => 7,
                    'post_type' => 1,
                    'updated_at' => '2022-05-10T10:23:11.638Z',
                    'reply_count' => 0,
                    'reply_to_post_number' => NULL,
                    'quote_count' => 0,
                    'incoming_link_count' => 0,
                    'reads' => 0,
                    'score' => 0,
                    'topic_id' => 289,
                    'topic_slug' => 'probando-webhooks',
                    'topic_title' => 'Probando webhooks',
                    'category_id' => 5,
                    'display_username' => 'almohadas rana',
                    'primary_group_name' => NULL,
                    'version' => 1,
                    'user_title' => NULL,
                    'bookmarked' => false,
                    'raw' => 'Ahora mismo los webhooks estan siendo notificados en este endpoint por si quereis ir comprobando cuando realiceis acciones

https://webhook.site/#!/2b7aa46d-b771-4c45-bf54-a8e839ad6669',
                    'moderator' => false,
                    'admin' => false,
                    'staff' => false,
                    'user_id' => 76,
                    'hidden' => false,
                    'trust_level' => 0,
                    'deleted_at' => NULL,
                    'user_deleted' => false,
                    'edit_reason' => NULL,
                    'wiki' => false,
                    'reviewable_id' => NULL,
                    'reviewable_score_count' => 0,
                    'reviewable_score_pending_count' => 0,
                    'topic_posts_count' => 1,
                    'topic_filtered_posts_count' => 1,
                    'topic_archetype' => 'regular',
                    'category_slug' => 'actualitat',
                ),
        );
    }

    function getLoggedInNotificationData(){
        return array (
            'user' =>
                array (
                    'id' => 92,
                    'username' => 'b2b_test_carlos',
                    'name' => 'test_carlos4',
                    'avatar_template' => '/letter_avatar_proxy/v4/letter/b/e274bd/{size}.png',
                    'email' => 'b2b_test_carlos@atarca-b2b.es',
                    'secondary_emails' =>
                        array (
                        ),
                    'last_posted_at' => NULL,
                    'last_seen_at' => NULL,
                    'created_at' => '2022-05-18T07:42:10.065Z',
                    'muted' => false,
                    'trust_level' => 0,
                    'moderator' => false,
                    'admin' => false,
                    'title' => NULL,
                    'badge_count' => 0,
                    'user_fields' =>
                        array (
                            1 => NULL,
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
                    'staged' => false,
                    'pending_count' => 0,
                    'profile_view_count' => 0,
                    'second_factor_enabled' => false,
                    'can_upload_profile_header' => true,
                    'can_upload_user_card_background' => true,
                    'post_count' => 0,
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
                    'system_avatar_template' => '/letter_avatar_proxy/v4/letter/b/e274bd/{size}.png',
                    'muted_usernames' =>
                        array (
                        ),
                    'ignored_usernames' =>
                        array (
                        ),
                    'allowed_pm_usernames' =>
                        array (
                        ),
                    'mailing_list_posts_per_day' => 1,
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
                                    'user_count' => 75,
                                    'mentionable_level' => 0,
                                    'messageable_level' => 0,
                                    'visibility_level' => 1,
                                    'primary_group' => false,
                                    'title' => NULL,
                                    'grant_trust_level' => NULL,
                                    'incoming_email' => NULL,
                                    'has_messages' => false,
                                    'flair_url' => NULL,
                                    'flair_bg_color' => NULL,
                                    'flair_color' => NULL,
                                    'bio_raw' => NULL,
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
                                    'can_admin_group' => true,
                                    'publish_read_state' => false,
                                ),
                        ),
                    'user_option' =>
                        array (
                            'user_id' => 92,
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

    function getLikeNotification(){
        return array (
            'like' =>
                array (
                    'post' =>
                        array (
                            'id' => 97,
                            'name' => 'Diego',
                            'username' => 'anbton',
                            'avatar_template' => '/user_avatar/community.stage.atarca.es/diegomtz/{size}/25_2.png',
                            'created_at' => '2022-02-05T07:43:26.511Z',
                            'cooked' => '<p>Hola ayer estuve trabajando en la paleta de colores para la nueva plataforma. Decir que es muy difícil combinar nuestro color naranja REC con los tonos marrones hexa de Rezero o incluso el hexa verde de comerç verd, son colores que no tienen ninguna relación armónica entre ellos y quedan mal o raros combinados. Por ejemplo, habéis visto alguna vez una web naranja y marrón, o naranja y verde? Yo al menos no.</p>
<p>Por lo tanto esta es la paleta que más me encaja, he partido del naranja REC, que es el color primario y a partir de este he ido creando los demás. He intentado meter un color dark que sea un naranja oscuro que sería lo más parecido al marrón que encaja, yo almenos no he podido tener una combinación mejor. Decir que este color naranja primario, para mí es un poco claro y quizás estaría bien re-hacerlo y hacer uno más oscuro, así el dark también sería mas dark y el secondary lo mismo ya que los saco a partir del primario.</p>
<p>Esta sería la paleta, he escogido un azul distinto del clásico de REC porque me parecía mas business.</p>
<p><img src="https://community.stage.atarca.es/uploads/default/original/1X/77f164006403e10d7d40143d525f8a5d69390baf.png" alt="Color scheme" data-base62-sha1="h73ZKIFcCXqjtevvuAhwQ9hEFKT" width="690" height="194"></p>
<p>A continuación he realizado un diseño de como podría ser la web nada más acceder mostrando el login. He integrado todos o casi todos los colores del esquema anterior para tener un ejemplo visual de como quedarían. Dejo dos propuestas, una con el header muy light y otra con el header dark.</p>
<p><img src="https://community.stage.atarca.es/uploads/default/original/1X/9025760df28af18652c73c12505efd15e0144a2d.jpeg" alt="Login" data-base62-sha1="kzb0Z4Of3FtjFcjs5DXVo1ysddP" width="690" height="377"></p>
<p><img src="https://community.stage.atarca.es/uploads/default/original/1X/f5144f9d6a32299def9e574ff67d78579e84e1d7.jpeg" alt="Login header dark" data-base62-sha1="yY4uM2iUbJpxrJeVhkZkKft7X2T" width="690" height="377"></p>
<p>Finalmente os dejo un par de encuestas para saber vuestra opinión.</p>
<p><strong>¿Que opinas sobre la paleta de colores?</strong></p>
<div class="poll" data-poll-status="open" data-poll-results="always" data-poll-charttype="bar" data-poll-type="regular" data-poll-name="poll">
<div>
<div class="poll-container">
<ul>
<li data-poll-option-id="f59142a1145a0f34eeeead91cf5464ec">Me gusta</li>
<li data-poll-option-id="b796b899165cb285a6e111fb8f00c16f">Cambiaría algún color</li>
<li data-poll-option-id="34a43957acc21f4ab60f988b90653662">Cambiaría de colores</li>
</ul>
</div>
<div class="poll-info">
<p>
<span class="info-number">0</span>
<span class="info-label">votantes</span>
</p>
</div>
</div>
</div>
<p><strong>¿Que header te gusta más?</strong></p>
<div class="poll" data-poll-status="open" data-poll-name="poll2" data-poll-results="always" data-poll-charttype="bar" data-poll-type="regular">
<div>
<div class="poll-container">
<ul>
<li data-poll-option-id="7484f78ed27f3809e3c830cb38c13071">Header claro</li>
<li data-poll-option-id="1507fc3d68bf142e9145f73898982acf">Header oscuro</li>
</ul>
</div>
<div class="poll-info">
<p>
<span class="info-number">0</span>
<span class="info-label">votantes</span>
</p>
</div>
</div>
</div>
<p><strong>DISEÑO 2 - REZERO COLORS</strong></p>
<p>Buenas, después de hablar sobre la marca de la plataforma B2B y que no podíamos incluir todo el rato el logo de REC y REZERO, llegamos a la conclusión de que el randing principal tendría que ser Rezero por lo cual haber escogido los colores coportativos del REC ahora carecía un poco de sentido. He estado estos días con <a class="mention" href="/u/keff_normal">@keff_normal</a> haciendo distintas pruebas de colores y probando muchas paletas de colores y os dejamos las que hemos considerado las 4 candidadtas finales. Os pongo una encuesta para que podáis votar cual es vuestra favorita.</p>
<p><img src="https://community.stage.atarca.es/uploads/default/original/1X/e2ebb1e8d190aa5f9c988e81de33c4c4fd42d8a0.png" alt="schemes_colors" data-base62-sha1="wnqSI6zTDrNJVQjyZZFFXYC9pXq" width="690" height="237"></p>
<p>Podéis ver ejemplos de distintas combinaciones de cada paleta en <a href="https://drive.google.com/drive/folders/18SocNwsqx-u4ZYPo-KR2Q753VM8uFfgz?usp=sharing">esta carpeta de drive</a></p>
<p>¿Que paleta de colores te gusta más?</p>
<div class="poll" data-poll-status="open" data-poll-name="poll4" data-poll-results="always" data-poll-charttype="bar" data-poll-type="regular">
<div>
<div class="poll-container">
<ul>
<li data-poll-option-id="801e29382b725af093f786d019a4a9de"><span class="hashtag">#3A7728</span></li>
<li data-poll-option-id="5b61ae727c5c550ba99b92ae80b376ef"><span class="hashtag">#3D8E33</span></li>
<li data-poll-option-id="14c0583535015d6735282a69a3aeda01"><span class="hashtag">#295F66</span></li>
<li data-poll-option-id="4005dd1d686c465a1b8c3f62c44d9f81"><span class="hashtag">#304351</span></li>
<li data-poll-option-id="ebb20a9328a4bd38f7d4b0506a3287af">Ninguna</li>
</ul>
</div>
<div class="poll-info">
<p>
<span class="info-number">0</span>
<span class="info-label">votantes</span>
</p>
</div>
</div>
</div>',
                            'post_number' => 1,
                            'post_type' => 1,
                            'updated_at' => '2022-02-14T18:58:24.189Z',
                            'reply_count' => 0,
                            'reply_to_post_number' => NULL,
                            'quote_count' => 0,
                            'incoming_link_count' => 0,
                            'reads' => 11,
                            'score' => 137.2,
                            'topic_id' => 50,
                            'topic_slug' => 'propuesta-diseno-plataforma-b2b',
                            'topic_title' => 'Propuesta diseño plataforma B2B',
                            'category_id' => 1,
                            'display_username' => 'Diego',
                            'primary_group_name' => NULL,
                            'version' => 6,
                            'user_title' => NULL,
                            'bookmarked' => false,
                            'raw' => 'Hola ayer estuve trabajando en la paleta de colores para la nueva plataforma. Decir que es muy difícil combinar nuestro color naranja REC con los tonos marrones hexa de Rezero o incluso el hexa verde de comerç verd, son colores que no tienen ninguna relación armónica entre ellos y quedan mal o raros combinados. Por ejemplo, habéis visto alguna vez una web naranja y marrón, o naranja y verde? Yo al menos no.

Por lo tanto esta es la paleta que más me encaja, he partido del naranja REC, que es el color primario y a partir de este he ido creando los demás. He intentado meter un color dark que sea un naranja oscuro que sería lo más parecido al marrón que encaja, yo almenos no he podido tener una combinación mejor. Decir que este color naranja primario, para mí es un poco claro y quizás estaría bien re-hacerlo y hacer uno más oscuro, así el dark también sería mas dark y el secondary lo mismo ya que los saco a partir del primario.

Esta sería la paleta, he escogido un azul distinto del clásico de REC porque me parecía mas business.

![Color scheme|690x194](upload://h73ZKIFcCXqjtevvuAhwQ9hEFKT.png)


A continuación he realizado un diseño de como podría ser la web nada más acceder mostrando el login. He integrado todos o casi todos los colores del esquema anterior para tener un ejemplo visual de como quedarían. Dejo dos propuestas, una con el header muy light y otra con el header dark.

![Login|690x377](upload://kzb0Z4Of3FtjFcjs5DXVo1ysddP.jpeg)

![Login header dark|690x377](upload://yY4uM2iUbJpxrJeVhkZkKft7X2T.jpeg)

Finalmente os dejo un par de encuestas para saber vuestra opinión. 

**¿Que opinas sobre la paleta de colores?**
[poll type=regular results=always chartType=bar]
* Me gusta
* Cambiaría algún color
* Cambiaría de colores
[/poll]

**¿Que header te gusta más?**
[poll name=poll2 type=regular results=always chartType=bar]
* Header claro
* Header oscuro
[/poll]

**DISEÑO 2 - REZERO COLORS**

Buenas, después de hablar sobre la marca de la plataforma B2B y que no podíamos incluir todo el rato el logo de REC y REZERO, llegamos a la conclusión de que el randing principal tendría que ser Rezero por lo cual haber escogido los colores coportativos del REC ahora carecía un poco de sentido. He estado estos días con @keff_normal haciendo distintas pruebas de colores y probando muchas paletas de colores y os dejamos las que hemos considerado las 4 candidadtas finales. Os pongo una encuesta para que podáis votar cual es vuestra favorita.

![schemes_colors|690x237](upload://wnqSI6zTDrNJVQjyZZFFXYC9pXq.png)

Podéis ver ejemplos de distintas combinaciones de cada paleta en [esta carpeta de drive](https://drive.google.com/drive/folders/18SocNwsqx-u4ZYPo-KR2Q753VM8uFfgz?usp=sharing)

¿Que paleta de colores te gusta más?
[poll name=poll4 type=regular results=always chartType=bar]
* #3A7728
* #3D8E33
* #295F66
* #304351
* Ninguna
[/poll]',
                            'moderator' => false,
                            'admin' => true,
                            'staff' => true,
                            'user_id' => 5,
                            'hidden' => false,
                            'trust_level' => 1,
                            'deleted_at' => NULL,
                            'user_deleted' => false,
                            'edit_reason' => NULL,
                            'wiki' => false,
                            'reviewable_id' => NULL,
                            'reviewable_score_count' => 0,
                            'reviewable_score_pending_count' => 0,
                            'topic_posts_count' => 45,
                            'topic_filtered_posts_count' => 43,
                            'topic_archetype' => 'regular',
                            'category_slug' => 'uncategorized',
                            'polls' =>
                                array (
                                    0 =>
                                        array (
                                            'name' => 'poll',
                                            'type' => 'regular',
                                            'status' => 'open',
                                            'results' => 'always',
                                            'options' =>
                                                array (
                                                    0 =>
                                                        array (
                                                            'id' => 'f59142a1145a0f34eeeead91cf5464ec',
                                                            'html' => 'Me gusta',
                                                            'votes' => 7,
                                                        ),
                                                    1 =>
                                                        array (
                                                            'id' => 'b796b899165cb285a6e111fb8f00c16f',
                                                            'html' => 'Cambiaría algún color',
                                                            'votes' => 0,
                                                        ),
                                                    2 =>
                                                        array (
                                                            'id' => '34a43957acc21f4ab60f988b90653662',
                                                            'html' => 'Cambiaría de colores',
                                                            'votes' => 0,
                                                        ),
                                                ),
                                            'voters' => 7,
                                            'chart_type' => 'bar',
                                            'title' => NULL,
                                        ),
                                    1 =>
                                        array (
                                            'name' => 'poll2',
                                            'type' => 'regular',
                                            'status' => 'open',
                                            'results' => 'always',
                                            'options' =>
                                                array (
                                                    0 =>
                                                        array (
                                                            'id' => '7484f78ed27f3809e3c830cb38c13071',
                                                            'html' => 'Header claro',
                                                            'votes' => 6,
                                                        ),
                                                    1 =>
                                                        array (
                                                            'id' => '1507fc3d68bf142e9145f73898982acf',
                                                            'html' => 'Header oscuro',
                                                            'votes' => 1,
                                                        ),
                                                ),
                                            'voters' => 7,
                                            'chart_type' => 'bar',
                                            'title' => NULL,
                                        ),
                                    2 =>
                                        array (
                                            'name' => 'poll4',
                                            'type' => 'regular',
                                            'status' => 'open',
                                            'results' => 'always',
                                            'options' =>
                                                array (
                                                    0 =>
                                                        array (
                                                            'id' => '801e29382b725af093f786d019a4a9de',
                                                            'html' => '<span class="hashtag">#3A7728</span>',
                                                            'votes' => 6,
                                                        ),
                                                    1 =>
                                                        array (
                                                            'id' => '5b61ae727c5c550ba99b92ae80b376ef',
                                                            'html' => '<span class="hashtag">#3D8E33</span>',
                                                            'votes' => 2,
                                                        ),
                                                    2 =>
                                                        array (
                                                            'id' => '14c0583535015d6735282a69a3aeda01',
                                                            'html' => '<span class="hashtag">#295F66</span>',
                                                            'votes' => 0,
                                                        ),
                                                    3 =>
                                                        array (
                                                            'id' => '4005dd1d686c465a1b8c3f62c44d9f81',
                                                            'html' => '<span class="hashtag">#304351</span>',
                                                            'votes' => 1,
                                                        ),
                                                    4 =>
                                                        array (
                                                            'id' => 'ebb20a9328a4bd38f7d4b0506a3287af',
                                                            'html' => 'Ninguna',
                                                            'votes' => 0,
                                                        ),
                                                ),
                                            'voters' => 9,
                                            'chart_type' => 'bar',
                                            'title' => NULL,
                                        ),
                                ),
                        ),
                    'user' =>
                        array (
                            'id' => 76,
                            'username' => 'rezero3',
                            'name' => 'almohadas rana',
                            'avatar_template' => '/user_avatar/community.stage.atarca.es/rana/{size}/98_2.png',
                        ),
                ),
        );
    }

    function getAwardsFromAnbtonAccount(){
        //TODO get self account awards items from anbton user
        $this->signIn(UserFixtures::TEST_REZERO_USER_2_CREDENTIALS);
        $user = $this->getSignedInUser();

        $routeItems = 'user/v3/account/'.$user->group_data->id.'/award_items';
        $respItems = $this->requestJson('GET', $routeItems);
        $contentItems = json_decode($respItems->getContent(),true);
        self::assertEquals('REZERO_2', $contentItems['data']['elements'][0]['account_name']);

        $routeItemsFilter = 'user/v3/account/'.$user->group_data->id.'/award_items?award_id=23';
        $respItemsFiltered = $this->requestJson('GET', $routeItemsFilter);
        $contentItemsFiltered = json_decode($respItemsFiltered->getContent(),true);
        self::assertEquals('Award not found', $contentItemsFiltered['message']);

        $routeItemsFilter = 'user/v3/account/'.$user->group_data->id.'/award_items?award_id=1';
        $respItemsFiltered = $this->requestJson('GET', $routeItemsFilter);
        $contentItemsFiltered = json_decode($respItemsFiltered->getContent(),true);
        self::assertEquals(0, $contentItemsFiltered['data']['total']);

        $routeItemsFilter = 'user/v3/account/'.$user->group_data->id.'/award_items?award_id=3';
        $respItemsFiltered = $this->requestJson('GET', $routeItemsFilter);
        $contentItemsFiltered = json_decode($respItemsFiltered->getContent(),true);
        self::assertEquals(1, $contentItemsFiltered['data']['total']);

        $this->signOut();
    }

    function getAwardsFromUser3Account(){
        $this->signIn(UserFixtures::TEST_REZERO_USER_3_CREDENTIALS);
        $user = $this->getSignedInUser();
        //get item awards
        $routeItems = 'user/v3/account/'.$user->group_data->id.'/award_items';
        $respItems = $this->requestJson('GET', $routeItems);
        $contentItems = json_decode($respItems->getContent(),true);
        self::assertEquals(1, $contentItems['data']['total']);
        self::assertEquals('REZERO_3', $contentItems['data']['elements'][0]['account_name']);

        //TODO get account awards
        $routeAwards = 'user/v3/account/'.$user->group_data->id.'/awards';
        $respAwards = $this->requestJson('GET', $routeAwards);
        $contentAwards = json_decode($respAwards->getContent(),true);

        self::assertEquals(1, $contentAwards['data']['total']);
        self::assertEquals(2, $contentAwards['data']['elements'][0]['score']);
    }

    function getDislikeNotification(){
        return array (
            'post_like_removed' =>
                array (
                    'post' =>
                        array (
                            'id' => 97,
                            'user_id' => 93,
                            'topic_id' => 321,
                            'post_number' => 1,
                            'raw' => 'hola hola hola hola comentario 4',
                            'cooked' => '<p>hola hola hola hola comentario 4</p>',
                            'created_at' => '2022-07-20T06:47:17.818Z',
                            'updated_at' => '2022-07-20T14:38:23.967Z',
                            'reply_to_post_number' => 624,
                            'reply_count' => 0,
                            'quote_count' => 0,
                            'deleted_at' => NULL,
                            'off_topic_count' => 0,
                            'like_count' => 0,
                            'incoming_link_count' => 0,
                            'bookmark_count' => 0,
                            'score' => 0.2,
                            'reads' => 1,
                            'post_type' => 1,
                            'sort_order' => 8,
                            'last_editor_id' => 93,
                            'hidden' => false,
                            'hidden_reason_id' => NULL,
                            'notify_moderators_count' => 0,
                            'spam_count' => 0,
                            'illegal_count' => 0,
                            'inappropriate_count' => 0,
                            'last_version_at' => '2022-07-20T06:47:17.852Z',
                            'user_deleted' => false,
                            'reply_to_user_id' => NULL,
                            'percent_rank' => 0.571428571428571,
                            'notify_user_count' => 0,
                            'like_score' => 0,
                            'deleted_by_id' => NULL,
                            'edit_reason' => NULL,
                            'word_count' => 6,
                            'version' => 1,
                            'cook_method' => 1,
                            'wiki' => false,
                            'baked_at' => '2022-07-20T14:38:23.965Z',
                            'baked_version' => 2,
                            'hidden_at' => NULL,
                            'self_edits' => 0,
                            'reply_quoted' => false,
                            'via_email' => false,
                            'raw_email' => NULL,
                            'public_version' => 1,
                            'action_code' => NULL,
                            'locked_by_id' => NULL,
                            'image_upload_id' => NULL,
                        ),
                    'user' =>
                        array (
                            'id' => 76,
                            'username' => 'rezero3',
                        ),
                ),
        );
    }

}