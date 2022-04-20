<?php

namespace Test\FinancialApiBundle\Discourse;


use App\FinancialApiBundle\DataFixture\UserFixture;
use App\FinancialApiBundle\DependencyInjection\App\Commons\DiscourseApiManager;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Test\FinancialApiBundle\BaseApiTest;

class DiscourseBridgeUsersTest extends BaseApiTest{
    function setUp(): void
    {
        parent::setUp();
        $this->signIn(UserFixture::TEST_REZERO_USER_2_CREDENTIALS);
    }

    function testListBridge(){

        $route = '/rezero_b2b/v1/bridge/latest.json';
        $this->useDiscourseMock();
        $resp = $this->requestJson('GET', $route);
        $content = json_decode($resp->getContent(),true);
        self::assertArrayHasKey('data', $content);

    }

    function testCallWithUrlParamsBridge(){

        $route = '/rezero_b2b/v1/bridge/directory_items.json?period=yearly&order=days_visited&asc=true';
        $this->useDiscourseMockDirectoryItems();
        $resp = $this->requestJson('GET', $route);
        $content = json_decode($resp->getContent(),true);
        self::assertArrayHasKey('data', $content);

    }

    function testCallWithPostParamsBridge(){
        $params = array (
            'raw' => $this->faker->sentence(28, true),
            'title' => $this->faker->sentence(4, true),
            'category' => 6,
            'tags' => ["test-tag"]
        );
        $route = '/rezero_b2b/v1/bridge/posts.json';
        $this->useCreateTopicMock();
        $resp = $this->requestJson('POST', $route, $params);
        $content = json_decode($resp->getContent(),true);
        self::assertArrayHasKey('data', $content);
        $post_id = $content['data']['id'];
        $topic_id = $content['data']['topic_id'];
        $routeUpdatePost = '/rezero_b2b/v1/bridge/posts/'.$post_id.'.json';
        $paramsUpdatePost = array (
            'post' => array(
                "raw" => $this->faker->sentence(28, true),
                "edit_reason" => "dolore in"
            )
        );

        $this->useUpdatePostMock();

        $resp = $this->requestJson('PUT', $routeUpdatePost, $paramsUpdatePost);

        $routeUpdateTopic = '/rezero_b2b/v1/bridge/t/-/'.$topic_id.'.json';
        $paramsUpdateTopic = array (
            "title" => $this->faker->sentence(4, true),
            'tags' => ["test-tag", "new-tag"]
        );

        $this->useUpdateTopicMock();
        $resp = $this->requestJson('PUT', $routeUpdateTopic, $paramsUpdateTopic);



    }

    function testCallDeleteBridge(){
        $params = array (
            'id' => 109,
            'post_action_type_id' => 2
        );
        $route = '/rezero_b2b/v1/bridge/post_actions.json';

        $this->useLikeMock();

        $resp = $this->requestJson('POST', $route, $params);
        $content = json_decode($resp->getContent(),true);
        self::assertArrayHasKey('data', $content);
        self::assertArrayHasKey('id', $content["data"]);
        $params = array (
            'post_action_type_id' => 2
        );
        $route = '/rezero_b2b/v1/bridge/post_actions/109.json';
        $resp = $this->requestJson('DELETE', $route, $params);
        $content = json_decode($resp->getContent(),true);
        self::assertArrayHasKey('data', $content);
        self::assertArrayHasKey('id', $content["data"]);


    }

    function testCallUploadsBridge(){

        $copied = copy(__DIR__.'/assets/foto.png', __DIR__.'/assets/foto2.png');
        $fp = new UploadedFile(__DIR__.'/assets/foto2.png', 'foto2.png');
        $params = array(
            "type" => "composer",
            "synchronous" => true
        );
        $route = '/rezero_b2b/v1/bridge/uploads.json';
        $this->useUploadMock();
        $resp = $this->request(
            'POST',
            $route,
            '',
            [],
            $params,
            ["file" => $fp]
        );

        $content = json_decode($resp->getContent(),true);
        self::assertArrayHasKey('data', $content);
        $data = $content['data'];
        self::assertArrayHasKey('id', $data);

    }

    private function useDiscourseMock()
    {
        $discMock = $this->createMock(DiscourseApiManager::class);
        $response = $this->getLatestMockResponse();
        $discMock->method('bridgeCall')->willReturn($response);

        $this->inject('net.app.commons.discourse.api_manager', $discMock);
    }

    private function useDiscourseMockDirectoryItems()
    {
        $discMock = $this->createMock(DiscourseApiManager::class);
        $response = $this->getDirectoryItemsMockResponse();
        $discMock->method('bridgeCall')->willReturn($response);

        $this->inject('net.app.commons.discourse.api_manager', $discMock);
    }

    private function useCreateTopicMock(){
        $discMock = $this->createMock(DiscourseApiManager::class);
        $response = $this->getCreateTopicMockResponse();
        $discMock->method('bridgeCall')->willReturn($response);

        $this->inject('net.app.commons.discourse.api_manager', $discMock);
    }

    private function useUploadMock(){
        $discMock = $this->createMock(DiscourseApiManager::class);
        $response = $this->getUploadMockResponse();
        $discMock->method('bridgeCall')->willReturn($response);

        $this->inject('net.app.commons.discourse.api_manager', $discMock);
    }

    private function useLikeMock(){
        $discMock = $this->createMock(DiscourseApiManager::class);
        $response = $this->getLikeMockResponse();
        $discMock->method('bridgeCall')->willReturn($response);

        $this->inject('net.app.commons.discourse.api_manager', $discMock);
    }

    private function useUpdatePostMock(){
        $discMock = $this->createMock(DiscourseApiManager::class);
        $response = $this->getUpdatePostResponse();
        $discMock->method('bridgeCall')->willReturn($response);

        $this->inject('net.app.commons.discourse.api_manager', $discMock);
    }

    private function useUpdateTopicMock(){
        $discMock = $this->createMock(DiscourseApiManager::class);
        $response = $this->getUpdateTopicResponse();
        $discMock->method('bridgeCall')->willReturn($response);

        $this->inject('net.app.commons.discourse.api_manager', $discMock);
    }

    private function getLatestMockResponse(){
        return array (
            'users' =>
                array (
                    0 =>
                        array (
                            'id' => 7,
                            'username' => 'Julia',
                            'name' => 'Julia',
                            'avatar_template' => '/letter_avatar_proxy/v4/letter/j/2bfe46/{size}.png',
                            'trust_level' => 1,
                        ),
                    1 =>
                        array (
                            'id' => 4,
                            'username' => 'Pere',
                            'name' => 'Pere',
                            'avatar_template' => '/user_avatar/community.stage.atarca.es/pere/{size}/31_2.png',
                            'admin' => true,
                            'trust_level' => 1,
                        ),
                    2 =>
                        array (
                            'id' => 6,
                            'username' => 'keff_normal',
                            'name' => 'Manolo Prasat Edge Tejero',
                            'avatar_template' => '/user_avatar/community.stage.atarca.es/keff_normal/{size}/6_2.png',
                            'admin' => true,
                            'trust_level' => 1,
                        ),
                    3 =>
                        array (
                            'id' => 5,
                            'username' => 'diegomtz',
                            'name' => 'Diego',
                            'avatar_template' => '/user_avatar/community.stage.atarca.es/diegomtz/{size}/25_2.png',
                            'admin' => true,
                            'trust_level' => 1,
                        ),
                    4 =>
                        array (
                            'id' => 8,
                            'username' => 'Sofia',
                            'name' => 'Sofia',
                            'avatar_template' => '/letter_avatar_proxy/v4/letter/s/35a633/{size}.png',
                            'trust_level' => 1,
                        ),
                    5 =>
                        array (
                            'id' => 12,
                            'username' => 'markku',
                            'name' => 'Markku Nousiainen',
                            'avatar_template' => '/letter_avatar_proxy/v4/letter/m/bc79bd/{size}.png',
                            'trust_level' => 0,
                        ),
                    6 =>
                        array (
                            'id' => 11,
                            'username' => 'Jarno',
                            'name' => 'Jarno Marttila',
                            'avatar_template' => '/letter_avatar_proxy/v4/letter/j/67e7ee/{size}.png',
                            'trust_level' => 0,
                        ),
                    7 =>
                        array (
                            'id' => 10,
                            'username' => 'qqmato',
                            'name' => 'Martin Moravek',
                            'avatar_template' => '/letter_avatar_proxy/v4/letter/q/f17d59/{size}.png',
                            'trust_level' => 0,
                        ),
                    8 =>
                        array (
                            'id' => -1,
                            'username' => 'system',
                            'name' => 'system',
                            'avatar_template' => '/uploads/default/original/1X/c88368d9b45006f6c54b55fc30211107f13dae3d.jpeg',
                            'admin' => true,
                            'moderator' => true,
                            'trust_level' => 4,
                        ),
                ),
            'primary_groups' =>
                array (
                ),
            'topic_list' =>
                array (
                    'can_create_topic' => true,
                    'per_page' => 30,
                    'top_tags' =>
                        array (
                            0 => 'test-tag',
                        ),
                    'topics' =>
                        array (
                            0 =>
                                array (
                                    'id' => 32,
                                    'title' => 'Green Shops 15 caracters',
                                    'fancy_title' => 'Green Shops 15 caracters',
                                    'slug' => 'green-shops-15-caracters',
                                    'posts_count' => 13,
                                    'reply_count' => 10,
                                    'highest_post_number' => 13,
                                    'image_url' => 'https://community.stage.atarca.es/uploads/default/original/1X/3137e01a2ca8a1ed904a2088cc769c239a87ccd0.jpeg',
                                    'created_at' => '2022-02-03T08:56:37.498Z',
                                    'last_posted_at' => '2022-02-04T14:44:52.465Z',
                                    'bumped' => true,
                                    'bumped_at' => '2022-03-01T10:02:45.233Z',
                                    'archetype' => 'regular',
                                    'unseen' => false,
                                    'pinned' => false,
                                    'unpinned' => NULL,
                                    'visible' => true,
                                    'closed' => false,
                                    'archived' => false,
                                    'bookmarked' => NULL,
                                    'liked' => NULL,
                                    'tags' =>
                                        array (
                                        ),
                                    'views' => 36,
                                    'like_count' => 11,
                                    'has_summary' => false,
                                    'last_poster_username' => 'Sofia',
                                    'category_id' => 1,
                                    'pinned_globally' => false,
                                    'featured_link' => NULL,
                                    'news_body' => NULL,
                                    'posters' =>
                                        array (
                                            0 =>
                                                array (
                                                    'extras' => NULL,
                                                    'description' => 'Autor original',
                                                    'user_id' => 7,
                                                    'primary_group_id' => NULL,
                                                ),
                                            1 =>
                                                array (
                                                    'extras' => NULL,
                                                    'description' => 'Autor frecuente',
                                                    'user_id' => 4,
                                                    'primary_group_id' => NULL,
                                                ),
                                            2 =>
                                                array (
                                                    'extras' => NULL,
                                                    'description' => 'Autor frecuente',
                                                    'user_id' => 6,
                                                    'primary_group_id' => NULL,
                                                ),
                                            3 =>
                                                array (
                                                    'extras' => NULL,
                                                    'description' => 'Autor frecuente',
                                                    'user_id' => 5,
                                                    'primary_group_id' => NULL,
                                                ),
                                            4 =>
                                                array (
                                                    'extras' => 'latest',
                                                    'description' => 'Autor más reciente',
                                                    'user_id' => 8,
                                                    'primary_group_id' => NULL,
                                                ),
                                        ),
                                ),
                            1 =>
                                array (
                                    'id' => 76,
                                    'title' => 'Hola què tal',
                                    'fancy_title' => 'Hola què tal',
                                    'slug' => 'hola-que-tal',
                                    'posts_count' => 1,
                                    'reply_count' => 0,
                                    'highest_post_number' => 1,
                                    'image_url' => NULL,
                                    'created_at' => '2022-02-18T09:34:56.252Z',
                                    'last_posted_at' => '2022-02-18T09:34:56.737Z',
                                    'bumped' => true,
                                    'bumped_at' => '2022-02-18T09:34:56.737Z',
                                    'archetype' => 'regular',
                                    'unseen' => false,
                                    'pinned' => false,
                                    'unpinned' => NULL,
                                    'visible' => true,
                                    'closed' => false,
                                    'archived' => false,
                                    'bookmarked' => NULL,
                                    'liked' => NULL,
                                    'tags' =>
                                        array (
                                            0 => 'test-tag',
                                        ),
                                    'views' => 7,
                                    'like_count' => 3,
                                    'has_summary' => false,
                                    'last_poster_username' => 'Julia',
                                    'category_id' => 7,
                                    'pinned_globally' => false,
                                    'featured_link' => NULL,
                                    'news_body' => NULL,
                                    'posters' =>
                                        array (
                                            0 =>
                                                array (
                                                    'extras' => 'latest single',
                                                    'description' => 'Autor original, Autor más reciente',
                                                    'user_id' => 7,
                                                    'primary_group_id' => NULL,
                                                ),
                                        ),
                                ),
                            2 =>
                                array (
                                    'id' => 57,
                                    'title' => 'Probando editado',
                                    'fancy_title' => 'Probando editado',
                                    'slug' => 'probando-editado',
                                    'posts_count' => 1,
                                    'reply_count' => 0,
                                    'highest_post_number' => 1,
                                    'image_url' => NULL,
                                    'created_at' => '2022-02-08T10:18:24.051Z',
                                    'last_posted_at' => '2022-02-08T10:18:24.439Z',
                                    'bumped' => true,
                                    'bumped_at' => '2022-02-18T08:46:12.057Z',
                                    'archetype' => 'regular',
                                    'unseen' => false,
                                    'pinned' => false,
                                    'unpinned' => NULL,
                                    'visible' => true,
                                    'closed' => false,
                                    'archived' => false,
                                    'bookmarked' => NULL,
                                    'liked' => NULL,
                                    'tags' =>
                                        array (
                                            0 => 'test-tag',
                                        ),
                                    'views' => 9,
                                    'like_count' => 1,
                                    'has_summary' => false,
                                    'last_poster_username' => 'Pere',
                                    'category_id' => 6,
                                    'pinned_globally' => false,
                                    'featured_link' => NULL,
                                    'news_body' => NULL,
                                    'posters' =>
                                        array (
                                            0 =>
                                                array (
                                                    'extras' => 'latest single',
                                                    'description' => 'Autor original, Autor más reciente',
                                                    'user_id' => 4,
                                                    'primary_group_id' => NULL,
                                                ),
                                        ),
                                ),
                            3 =>
                                array (
                                    'id' => 61,
                                    'title' => 'Primera noticia',
                                    'fancy_title' => 'Primera noticia',
                                    'slug' => 'primera-noticia',
                                    'posts_count' => 1,
                                    'reply_count' => 0,
                                    'highest_post_number' => 1,
                                    'image_url' => 'https://community.stage.atarca.es/uploads/default/original/1X/7072af9008796ca2f8d9f479ade86a248930c844.png',
                                    'created_at' => '2022-02-08T11:32:34.533Z',
                                    'last_posted_at' => '2022-02-08T11:32:35.005Z',
                                    'bumped' => true,
                                    'bumped_at' => '2022-02-08T11:32:35.005Z',
                                    'archetype' => 'regular',
                                    'unseen' => false,
                                    'pinned' => false,
                                    'unpinned' => NULL,
                                    'visible' => true,
                                    'closed' => false,
                                    'archived' => false,
                                    'bookmarked' => NULL,
                                    'liked' => NULL,
                                    'tags' =>
                                        array (
                                        ),
                                    'views' => 11,
                                    'like_count' => 1,
                                    'has_summary' => false,
                                    'last_poster_username' => 'Pere',
                                    'category_id' => 9,
                                    'pinned_globally' => false,
                                    'featured_link' => NULL,
                                    'news_body' => '
<p>Probanbdo la primera noticia</p>',
                                    'posters' =>
                                        array (
                                            0 =>
                                                array (
                                                    'extras' => 'latest single',
                                                    'description' => 'Autor original, Autor más reciente',
                                                    'user_id' => 4,
                                                    'primary_group_id' => NULL,
                                                ),
                                        ),
                                ),
                            4 =>
                                array (
                                    'id' => 50,
                                    'title' => 'Propuesta diseño plataforma B2B',
                                    'fancy_title' => 'Propuesta diseño plataforma B2B',
                                    'slug' => 'propuesta-diseno-plataforma-b2b',
                                    'posts_count' => 8,
                                    'reply_count' => 2,
                                    'highest_post_number' => 8,
                                    'image_url' => 'https://community.stage.atarca.es/uploads/default/original/1X/77f164006403e10d7d40143d525f8a5d69390baf.png',
                                    'created_at' => '2022-02-05T07:43:26.000Z',
                                    'last_posted_at' => '2022-02-08T09:30:58.050Z',
                                    'bumped' => true,
                                    'bumped_at' => '2022-02-08T11:07:37.300Z',
                                    'archetype' => 'regular',
                                    'unseen' => false,
                                    'pinned' => false,
                                    'unpinned' => NULL,
                                    'visible' => true,
                                    'closed' => false,
                                    'archived' => false,
                                    'bookmarked' => NULL,
                                    'liked' => NULL,
                                    'tags' =>
                                        array (
                                        ),
                                    'views' => 64,
                                    'like_count' => 9,
                                    'has_summary' => false,
                                    'last_poster_username' => 'Sofia',
                                    'category_id' => 1,
                                    'pinned_globally' => false,
                                    'featured_link' => NULL,
                                    'news_body' => NULL,
                                    'posters' =>
                                        array (
                                            0 =>
                                                array (
                                                    'extras' => NULL,
                                                    'description' => 'Autor original',
                                                    'user_id' => 5,
                                                    'primary_group_id' => NULL,
                                                ),
                                            1 =>
                                                array (
                                                    'extras' => NULL,
                                                    'description' => 'Autor frecuente',
                                                    'user_id' => 6,
                                                    'primary_group_id' => NULL,
                                                ),
                                            2 =>
                                                array (
                                                    'extras' => NULL,
                                                    'description' => 'Autor frecuente',
                                                    'user_id' => 12,
                                                    'primary_group_id' => NULL,
                                                ),
                                            3 =>
                                                array (
                                                    'extras' => NULL,
                                                    'description' => 'Autor frecuente',
                                                    'user_id' => 7,
                                                    'primary_group_id' => NULL,
                                                ),
                                            4 =>
                                                array (
                                                    'extras' => 'latest',
                                                    'description' => 'Autor más reciente',
                                                    'user_id' => 8,
                                                    'primary_group_id' => NULL,
                                                ),
                                        ),
                                ),
                            5 =>
                                array (
                                    'id' => 58,
                                    'title' => 'Testing images',
                                    'fancy_title' => 'Testing images',
                                    'slug' => 'testing-images',
                                    'posts_count' => 1,
                                    'reply_count' => 0,
                                    'highest_post_number' => 1,
                                    'image_url' => 'https://community.stage.atarca.es/uploads/default/original/1X/ad10234e04ec684a3dfd15ed4e91b9152e0bf006.png',
                                    'created_at' => '2022-02-08T10:23:40.484Z',
                                    'last_posted_at' => '2022-02-08T10:23:40.691Z',
                                    'bumped' => true,
                                    'bumped_at' => '2022-02-08T10:23:40.691Z',
                                    'archetype' => 'regular',
                                    'unseen' => false,
                                    'pinned' => false,
                                    'unpinned' => NULL,
                                    'visible' => true,
                                    'closed' => false,
                                    'archived' => false,
                                    'bookmarked' => NULL,
                                    'liked' => NULL,
                                    'tags' =>
                                        array (
                                        ),
                                    'views' => 4,
                                    'like_count' => 0,
                                    'has_summary' => false,
                                    'last_poster_username' => 'keff_normal',
                                    'category_id' => 1,
                                    'pinned_globally' => false,
                                    'featured_link' => NULL,
                                    'news_body' => NULL,
                                    'posters' =>
                                        array (
                                            0 =>
                                                array (
                                                    'extras' => 'latest single',
                                                    'description' => 'Autor original, Autor más reciente',
                                                    'user_id' => 6,
                                                    'primary_group_id' => NULL,
                                                ),
                                        ),
                                ),
                            6 =>
                                array (
                                    'id' => 49,
                                    'title' => 'Mentoria de sysadmin',
                                    'fancy_title' => 'Mentoria de sysadmin',
                                    'slug' => 'mentoria-de-sysadmin',
                                    'posts_count' => 5,
                                    'reply_count' => 1,
                                    'highest_post_number' => 5,
                                    'image_url' => NULL,
                                    'created_at' => '2022-02-04T19:15:53.068Z',
                                    'last_posted_at' => '2022-02-08T09:38:53.770Z',
                                    'bumped' => true,
                                    'bumped_at' => '2022-02-08T09:38:53.770Z',
                                    'archetype' => 'regular',
                                    'unseen' => false,
                                    'pinned' => false,
                                    'unpinned' => NULL,
                                    'visible' => true,
                                    'closed' => false,
                                    'archived' => false,
                                    'bookmarked' => NULL,
                                    'liked' => NULL,
                                    'tags' =>
                                        array (
                                            0 => 'test-tag',
                                        ),
                                    'views' => 21,
                                    'like_count' => 7,
                                    'has_summary' => false,
                                    'last_poster_username' => 'Pere',
                                    'category_id' => 8,
                                    'pinned_globally' => false,
                                    'featured_link' => NULL,
                                    'news_body' => NULL,
                                    'posters' =>
                                        array (
                                            0 =>
                                                array (
                                                    'extras' => 'latest',
                                                    'description' => 'Autor original, Autor más reciente',
                                                    'user_id' => 4,
                                                    'primary_group_id' => NULL,
                                                ),
                                            1 =>
                                                array (
                                                    'extras' => NULL,
                                                    'description' => 'Autor frecuente',
                                                    'user_id' => 5,
                                                    'primary_group_id' => NULL,
                                                ),
                                            2 =>
                                                array (
                                                    'extras' => NULL,
                                                    'description' => 'Autor frecuente',
                                                    'user_id' => 7,
                                                    'primary_group_id' => NULL,
                                                ),
                                        ),
                                ),
                            7 =>
                                array (
                                    'id' => 16,
                                    'title' => 'First topic test by Pere',
                                    'fancy_title' => 'First topic test by Pere',
                                    'slug' => 'first-topic-test-by-pere',
                                    'posts_count' => 10,
                                    'reply_count' => 6,
                                    'highest_post_number' => 10,
                                    'image_url' => NULL,
                                    'created_at' => '2022-01-10T15:36:34.242Z',
                                    'last_posted_at' => '2022-02-04T10:09:37.468Z',
                                    'bumped' => true,
                                    'bumped_at' => '2022-02-04T10:09:37.468Z',
                                    'archetype' => 'regular',
                                    'unseen' => false,
                                    'pinned' => false,
                                    'unpinned' => NULL,
                                    'visible' => true,
                                    'closed' => false,
                                    'archived' => false,
                                    'bookmarked' => NULL,
                                    'liked' => NULL,
                                    'tags' =>
                                        array (
                                        ),
                                    'views' => 29,
                                    'like_count' => 13,
                                    'has_summary' => false,
                                    'last_poster_username' => 'Julia',
                                    'category_id' => 2,
                                    'pinned_globally' => false,
                                    'featured_link' => NULL,
                                    'news_body' => NULL,
                                    'posters' =>
                                        array (
                                            0 =>
                                                array (
                                                    'extras' => NULL,
                                                    'description' => 'Autor original',
                                                    'user_id' => 4,
                                                    'primary_group_id' => NULL,
                                                ),
                                            1 =>
                                                array (
                                                    'extras' => NULL,
                                                    'description' => 'Autor frecuente',
                                                    'user_id' => 5,
                                                    'primary_group_id' => NULL,
                                                ),
                                            2 =>
                                                array (
                                                    'extras' => NULL,
                                                    'description' => 'Autor frecuente',
                                                    'user_id' => 8,
                                                    'primary_group_id' => NULL,
                                                ),
                                            3 =>
                                                array (
                                                    'extras' => NULL,
                                                    'description' => 'Autor frecuente',
                                                    'user_id' => 11,
                                                    'primary_group_id' => NULL,
                                                ),
                                            4 =>
                                                array (
                                                    'extras' => 'latest',
                                                    'description' => 'Autor más reciente',
                                                    'user_id' => 7,
                                                    'primary_group_id' => NULL,
                                                ),
                                        ),
                                ),
                            8 =>
                                array (
                                    'id' => 45,
                                    'title' => 'Me gusta! how can I switch this to english?',
                                    'fancy_title' => 'Me gusta! how can I switch this to english?',
                                    'slug' => 'me-gusta-how-can-i-switch-this-to-english',
                                    'posts_count' => 3,
                                    'reply_count' => 1,
                                    'highest_post_number' => 3,
                                    'image_url' => NULL,
                                    'created_at' => '2022-02-03T14:17:13.713Z',
                                    'last_posted_at' => '2022-02-04T08:58:35.994Z',
                                    'bumped' => true,
                                    'bumped_at' => '2022-02-04T08:58:35.994Z',
                                    'archetype' => 'regular',
                                    'unseen' => false,
                                    'pinned' => false,
                                    'unpinned' => NULL,
                                    'visible' => true,
                                    'closed' => false,
                                    'archived' => false,
                                    'bookmarked' => NULL,
                                    'liked' => NULL,
                                    'tags' =>
                                        array (
                                            0 => 'test-tag',
                                        ),
                                    'views' => 17,
                                    'like_count' => 4,
                                    'has_summary' => false,
                                    'last_poster_username' => 'qqmato',
                                    'category_id' => 1,
                                    'pinned_globally' => false,
                                    'featured_link' => NULL,
                                    'news_body' => NULL,
                                    'posters' =>
                                        array (
                                            0 =>
                                                array (
                                                    'extras' => 'latest',
                                                    'description' => 'Autor original, Autor más reciente',
                                                    'user_id' => 10,
                                                    'primary_group_id' => NULL,
                                                ),
                                            1 =>
                                                array (
                                                    'extras' => NULL,
                                                    'description' => 'Autor frecuente',
                                                    'user_id' => 5,
                                                    'primary_group_id' => NULL,
                                                ),
                                        ),
                                ),
                            9 =>
                                array (
                                    'id' => 7,
                                    'title' => 'Welcome to Discourse',
                                    'fancy_title' => 'Welcome to Discourse',
                                    'slug' => 'welcome-to-discourse',
                                    'posts_count' => 1,
                                    'reply_count' => 0,
                                    'highest_post_number' => 1,
                                    'image_url' => NULL,
                                    'created_at' => '2022-01-10T14:09:14.405Z',
                                    'last_posted_at' => '2022-01-10T14:09:15.306Z',
                                    'bumped' => true,
                                    'bumped_at' => '2022-01-10T14:34:17.724Z',
                                    'archetype' => 'regular',
                                    'unseen' => false,
                                    'pinned' => true,
                                    'unpinned' => NULL,
                                    'excerpt' => 'The first paragraph of this pinned topic will be visible as a welcome message to all new visitors on your homepage. It’s important! 
Edit this into a brief description of your community: 

Who is it for?
What can they fi&hellip;',
                                    'visible' => true,
                                    'closed' => false,
                                    'archived' => false,
                                    'bookmarked' => NULL,
                                    'liked' => NULL,
                                    'tags' =>
                                        array (
                                        ),
                                    'views' => 9,
                                    'like_count' => 0,
                                    'has_summary' => false,
                                    'last_poster_username' => 'system',
                                    'category_id' => 1,
                                    'pinned_globally' => true,
                                    'featured_link' => NULL,
                                    'news_body' => NULL,
                                    'posters' =>
                                        array (
                                            0 =>
                                                array (
                                                    'extras' => 'latest single',
                                                    'description' => 'Autor original, Autor más reciente',
                                                    'user_id' => -1,
                                                    'primary_group_id' => NULL,
                                                ),
                                        ),
                                ),
                        ),
                ),
        );
    }

    private function getDirectoryItemsMockResponse(){
        return array (
            'directory_items' =>
                array (
                    0 =>
                        array (
                            'id' => 1,
                            'likes_received' => 0,
                            'likes_given' => 0,
                            'topics_entered' => 0,
                            'topic_count' => 0,
                            'post_count' => 0,
                            'posts_read' => 0,
                            'days_visited' => 0,
                            'user' =>
                                array (
                                    'id' => 1,
                                    'username' => 'user',
                                    'name' => 'UserName LastName',
                                    'avatar_template' => '/letter_avatar_proxy/v4/letter/u/c0e974/{size}.png',
                                    'title' => NULL,
                                    'admin' => true,
                                    'trust_level' => 0,
                                ),
                        ),
                    1 =>
                        array (
                            'id' => 19,
                            'likes_received' => 0,
                            'likes_given' => 0,
                            'topics_entered' => 0,
                            'topic_count' => 0,
                            'post_count' => 0,
                            'posts_read' => 0,
                            'days_visited' => 0,
                            'user' =>
                                array (
                                    'id' => 19,
                                    'username' => 'uzuyu',
                                    'name' => 'uzuyu',
                                    'avatar_template' => '/letter_avatar_proxy/v4/letter/u/e79b87/{size}.png',
                                    'title' => NULL,
                                    'trust_level' => 0,
                                ),
                        ),
                    2 =>
                        array (
                            'id' => 24,
                            'likes_received' => 0,
                            'likes_given' => 0,
                            'topics_entered' => 0,
                            'topic_count' => 0,
                            'post_count' => 0,
                            'posts_read' => 0,
                            'days_visited' => 0,
                            'user' =>
                                array (
                                    'id' => 24,
                                    'username' => 'rezero_test',
                                    'name' => 'REZERO_1',
                                    'avatar_template' => '/letter_avatar_proxy/v4/letter/r/85f322/{size}.png',
                                    'title' => NULL,
                                    'trust_level' => 0,
                                ),
                        ),
                    3 =>
                        array (
                            'id' => 2,
                            'likes_received' => 0,
                            'likes_given' => 0,
                            'topics_entered' => 0,
                            'topic_count' => 0,
                            'post_count' => 0,
                            'posts_read' => 0,
                            'days_visited' => 1,
                            'user' =>
                                array (
                                    'id' => 2,
                                    'username' => 'lluis',
                                    'name' => 'Lluis Santos',
                                    'avatar_template' => '/letter_avatar_proxy/v4/letter/l/ee7513/{size}.png',
                                    'title' => NULL,
                                    'trust_level' => 0,
                                ),
                        ),
                    4 =>
                        array (
                            'id' => 11,
                            'likes_received' => 4,
                            'likes_given' => 0,
                            'topics_entered' => 1,
                            'topic_count' => 0,
                            'post_count' => 1,
                            'posts_read' => 7,
                            'days_visited' => 1,
                            'user' =>
                                array (
                                    'id' => 11,
                                    'username' => 'Jarno',
                                    'name' => 'Jarno Marttila',
                                    'avatar_template' => '/letter_avatar_proxy/v4/letter/j/67e7ee/{size}.png',
                                    'title' => NULL,
                                    'trust_level' => 0,
                                ),
                        ),
                    5 =>
                        array (
                            'id' => 14,
                            'likes_received' => 0,
                            'likes_given' => 0,
                            'topics_entered' => 0,
                            'topic_count' => 0,
                            'post_count' => 0,
                            'posts_read' => 0,
                            'days_visited' => 1,
                            'user' =>
                                array (
                                    'id' => 14,
                                    'username' => 'tolomeo1986',
                                    'name' => 'Perico matterhorn',
                                    'avatar_template' => '/letter_avatar_proxy/v4/letter/t/91b2a8/{size}.png',
                                    'title' => NULL,
                                    'trust_level' => 0,
                                ),
                        ),
                    6 =>
                        array (
                            'id' => 17,
                            'likes_received' => 0,
                            'likes_given' => 0,
                            'topics_entered' => 1,
                            'topic_count' => 0,
                            'post_count' => 0,
                            'posts_read' => 8,
                            'days_visited' => 1,
                            'user' =>
                                array (
                                    'id' => 17,
                                    'username' => 'Andrea',
                                    'name' => 'Andrea',
                                    'avatar_template' => '/letter_avatar_proxy/v4/letter/a/c37758/{size}.png',
                                    'title' => NULL,
                                    'trust_level' => 0,
                                ),
                        ),
                    7 =>
                        array (
                            'id' => 16,
                            'likes_received' => 0,
                            'likes_given' => 1,
                            'topics_entered' => 1,
                            'topic_count' => 0,
                            'post_count' => 0,
                            'posts_read' => 8,
                            'days_visited' => 1,
                            'user' =>
                                array (
                                    'id' => 16,
                                    'username' => 'nuriabentoldra',
                                    'name' => 'Núria Bentoldrà Boladeres',
                                    'avatar_template' => '/letter_avatar_proxy/v4/letter/n/bc79bd/{size}.png',
                                    'title' => NULL,
                                    'trust_level' => 0,
                                ),
                        ),
                    8 =>
                        array (
                            'id' => 18,
                            'likes_received' => 0,
                            'likes_given' => 0,
                            'topics_entered' => 0,
                            'topic_count' => 0,
                            'post_count' => 0,
                            'posts_read' => 0,
                            'days_visited' => 2,
                            'user' =>
                                array (
                                    'id' => 18,
                                    'username' => 'Almarda',
                                    'name' => 'Almarda',
                                    'avatar_template' => '/letter_avatar_proxy/v4/letter/a/9dc877/{size}.png',
                                    'title' => NULL,
                                    'trust_level' => 1,
                                ),
                        ),
                    9 =>
                        array (
                            'id' => 12,
                            'likes_received' => 2,
                            'likes_given' => 0,
                            'topics_entered' => 9,
                            'topic_count' => 0,
                            'post_count' => 1,
                            'posts_read' => 30,
                            'days_visited' => 3,
                            'user' =>
                                array (
                                    'id' => 12,
                                    'username' => 'markku',
                                    'name' => 'Markku Nousiainen',
                                    'avatar_template' => '/letter_avatar_proxy/v4/letter/m/bc79bd/{size}.png',
                                    'title' => NULL,
                                    'trust_level' => 0,
                                ),
                        ),
                    10 =>
                        array (
                            'id' => 15,
                            'likes_received' => 0,
                            'likes_given' => 0,
                            'topics_entered' => 0,
                            'topic_count' => 0,
                            'post_count' => 0,
                            'posts_read' => 0,
                            'days_visited' => 3,
                            'user' =>
                                array (
                                    'id' => 15,
                                    'username' => 'rossifumi4646',
                                    'name' => 'Valentino Rossi',
                                    'avatar_template' => '/letter_avatar_proxy/v4/letter/r/7feea3/{size}.png',
                                    'title' => NULL,
                                    'trust_level' => 0,
                                ),
                        ),
                    11 =>
                        array (
                            'id' => 9,
                            'likes_received' => 0,
                            'likes_given' => 5,
                            'topics_entered' => 7,
                            'topic_count' => 0,
                            'post_count' => 0,
                            'posts_read' => 31,
                            'days_visited' => 6,
                            'user' =>
                                array (
                                    'id' => 9,
                                    'username' => 'sergi',
                                    'name' => 'sergi',
                                    'avatar_template' => '/letter_avatar_proxy/v4/letter/s/9fc29f/{size}.png',
                                    'title' => NULL,
                                    'trust_level' => 0,
                                ),
                        ),
                    12 =>
                        array (
                            'id' => 10,
                            'likes_received' => 2,
                            'likes_given' => 0,
                            'topics_entered' => 2,
                            'topic_count' => 1,
                            'post_count' => 1,
                            'posts_read' => 9,
                            'days_visited' => 8,
                            'user' =>
                                array (
                                    'id' => 10,
                                    'username' => 'qqmato',
                                    'name' => 'Martin Moravek',
                                    'avatar_template' => '/letter_avatar_proxy/v4/letter/q/f17d59/{size}.png',
                                    'title' => NULL,
                                    'trust_level' => 0,
                                ),
                        ),
                    13 =>
                        array (
                            'id' => 3,
                            'likes_received' => 0,
                            'likes_given' => 4,
                            'topics_entered' => 10,
                            'topic_count' => 0,
                            'post_count' => 0,
                            'posts_read' => 28,
                            'days_visited' => 9,
                            'user' =>
                                array (
                                    'id' => 3,
                                    'username' => 'atarca',
                                    'name' => NULL,
                                    'avatar_template' => '/letter_avatar_proxy/v4/letter/a/7ba0ec/{size}.png',
                                    'title' => NULL,
                                    'admin' => true,
                                    'trust_level' => 1,
                                ),
                        ),
                    14 =>
                        array (
                            'id' => 8,
                            'likes_received' => 7,
                            'likes_given' => 3,
                            'topics_entered' => 14,
                            'topic_count' => 0,
                            'post_count' => 7,
                            'posts_read' => 40,
                            'days_visited' => 10,
                            'user' =>
                                array (
                                    'id' => 8,
                                    'username' => 'Sofia',
                                    'name' => 'Sofia',
                                    'avatar_template' => '/letter_avatar_proxy/v4/letter/s/35a633/{size}.png',
                                    'title' => NULL,
                                    'trust_level' => 1,
                                ),
                        ),
                    15 =>
                        array (
                            'id' => 7,
                            'likes_received' => 8,
                            'likes_given' => 22,
                            'topics_entered' => 17,
                            'topic_count' => 2,
                            'post_count' => 12,
                            'posts_read' => 34,
                            'days_visited' => 12,
                            'user' =>
                                array (
                                    'id' => 7,
                                    'username' => 'Julia',
                                    'name' => 'Julia',
                                    'avatar_template' => '/letter_avatar_proxy/v4/letter/j/2bfe46/{size}.png',
                                    'title' => NULL,
                                    'trust_level' => 1,
                                ),
                        ),
                    16 =>
                        array (
                            'id' => 6,
                            'likes_received' => 4,
                            'likes_given' => 4,
                            'topics_entered' => 21,
                            'topic_count' => 2,
                            'post_count' => 4,
                            'posts_read' => 38,
                            'days_visited' => 17,
                            'user' =>
                                array (
                                    'id' => 6,
                                    'username' => 'keff_normal',
                                    'name' => 'Manolo Prasat Edge Tejero',
                                    'avatar_template' => '/user_avatar/community.stage.atarca.es/keff_normal/{size}/6_2.png',
                                    'title' => NULL,
                                    'admin' => true,
                                    'trust_level' => 1,
                                ),
                        ),
                    17 =>
                        array (
                            'id' => 5,
                            'likes_received' => 12,
                            'likes_given' => 3,
                            'topics_entered' => 19,
                            'topic_count' => 1,
                            'post_count' => 5,
                            'posts_read' => 45,
                            'days_visited' => 20,
                            'user' =>
                                array (
                                    'id' => 5,
                                    'username' => 'diegomtz',
                                    'name' => 'Diego',
                                    'avatar_template' => '/user_avatar/community.stage.atarca.es/diegomtz/{size}/25_2.png',
                                    'title' => NULL,
                                    'admin' => true,
                                    'trust_level' => 1,
                                ),
                        ),
                    18 =>
                        array (
                            'id' => 4,
                            'likes_received' => 11,
                            'likes_given' => 8,
                            'topics_entered' => 27,
                            'topic_count' => 4,
                            'post_count' => 4,
                            'posts_read' => 45,
                            'days_visited' => 37,
                            'user' =>
                                array (
                                    'id' => 4,
                                    'username' => 'Pere',
                                    'name' => 'Pere',
                                    'avatar_template' => '/user_avatar/community.stage.atarca.es/pere/{size}/31_2.png',
                                    'title' => NULL,
                                    'admin' => true,
                                    'trust_level' => 1,
                                ),
                        ),
                ),
            'meta' =>
                array (
                    'last_updated_at' => '2022-03-06T14:59:20.000Z',
                    'total_rows_directory_items' => 19,
                    'load_more_directory_items' => '/directory_items.json?asc=true&order=days_visited&page=1&period=yearly',
                ),
        );
    }

    private function getCreateTopicMockResponse(){
        return array (
            'id' => 147,
            'name' => 'Almarda',
            'username' => 'Almarda',
            'avatar_template' => '/letter_avatar_proxy/v4/letter/a/9dc877/{size}.png',
            'created_at' => '2022-03-07T12:53:15.669Z',
            'cooked' => '<p>nisi Lorem in lksjhclkasjdchklsajdh</p>',
            'post_number' => 1,
            'post_type' => 1,
            'updated_at' => '2022-03-07T12:53:15.669Z',
            'reply_count' => 0,
            'reply_to_post_number' => NULL,
            'quote_count' => 0,
            'incoming_link_count' => 0,
            'reads' => 0,
            'readers_count' => 0,
            'score' => 0,
            'yours' => true,
            'topic_id' => 87,
            'topic_slug' => 'veniam-exercitation-ut',
            'display_username' => 'Almarda',
            'primary_group_name' => NULL,
            'primary_group_flair_url' => NULL,
            'primary_group_flair_bg_color' => NULL,
            'primary_group_flair_color' => NULL,
            'version' => 1,
            'can_edit' => true,
            'can_delete' => false,
            'can_recover' => false,
            'can_wiki' => false,
            'user_title' => NULL,
            'bookmarked' => false,
            'actions_summary' =>
                array (
                    0 =>
                        array (
                            'id' => 3,
                            'can_act' => true,
                        ),
                    1 =>
                        array (
                            'id' => 4,
                            'can_act' => true,
                        ),
                    2 =>
                        array (
                            'id' => 8,
                            'can_act' => true,
                        ),
                    3 =>
                        array (
                            'id' => 7,
                            'can_act' => true,
                        ),
                ),
            'moderator' => false,
            'admin' => false,
            'staff' => false,
            'user_id' => 18,
            'draft_sequence' => 0,
            'hidden' => false,
            'trust_level' => 1,
            'deleted_at' => NULL,
            'user_deleted' => false,
            'edit_reason' => NULL,
            'can_view_edit_history' => true,
            'wiki' => false,
        );
    }

    private function getUploadMockResponse(){
        return array (
            'id' => 47,
            'url' => 'https://community.stage.atarca.es/uploads/default/original/1X/811b5fd7e9ea14559e62bd023da1182889ad1851.jpeg',
            'original_filename' => 'fondo53-11.jpg',
            'filesize' => 108933,
            'width' => 1280,
            'height' => 720,
            'thumbnail_width' => 1280,
            'thumbnail_height' => 720,
            'extension' => 'jpeg',
            'short_url' => 'upload://iq8f4gikhlQ4DleWQGcU4ZeQe65.jpeg',
            'short_path' => '/uploads/short-url/iq8f4gikhlQ4DleWQGcU4ZeQe65.jpeg',
            'retain_hours' => NULL,
            'human_filesize' => '106 KB',
        );
    }

    private function getLikeMockResponse(){
        return array (
            'id' => 109,
            'name' => 'Sofia',
            'username' => 'Sofia',
            'avatar_template' => '/letter_avatar_proxy/v4/letter/s/35a633/{size}.png',
            'created_at' => '2022-02-08T09:27:19.687Z',
            'cooked' => '<aside class="quote no-group" data-username="Julia" data-post="4" data-topic="50">
<div class="title">
<div class="quote-controls"></div>
<img alt="" width="20" height="20" src="https://community.stage.atarca.es/letter_avatar_proxy/v4/letter/j/2bfe46/40.png" class="avatar"> Julia:</div>
<blockquote>
<p>ME GUSTA MUCHO,</p>
</blockquote>
</aside>
<p>Quiero saber quien ha votado header oscuro!</p>',
            'post_number' => 6,
            'post_type' => 1,
            'updated_at' => '2022-02-08T09:27:19.687Z',
            'reply_count' => 1,
            'reply_to_post_number' => NULL,
            'quote_count' => 1,
            'incoming_link_count' => 0,
            'reads' => 11,
            'readers_count' => 10,
            'score' => 37,
            'yours' => false,
            'topic_id' => 50,
            'topic_slug' => 'propuesta-diseno-plataforma-b2b',
            'display_username' => 'Sofia',
            'primary_group_name' => NULL,
            'primary_group_flair_url' => NULL,
            'primary_group_flair_bg_color' => NULL,
            'primary_group_flair_color' => NULL,
            'version' => 1,
            'can_edit' => false,
            'can_delete' => false,
            'can_recover' => false,
            'can_wiki' => false,
            'user_title' => NULL,
            'bookmarked' => false,
            'actions_summary' =>
                array (
                    0 =>
                        array (
                            'id' => 2,
                            'count' => 4,
                            'can_act' => true,
                        ),
                    1 =>
                        array (
                            'id' => 3,
                            'can_act' => true,
                        ),
                    2 =>
                        array (
                            'id' => 4,
                            'can_act' => true,
                        ),
                    3 =>
                        array (
                            'id' => 8,
                            'can_act' => true,
                        ),
                    4 =>
                        array (
                            'id' => 6,
                            'can_act' => true,
                        ),
                    5 =>
                        array (
                            'id' => 7,
                            'can_act' => true,
                        ),
                ),
            'moderator' => false,
            'admin' => false,
            'staff' => false,
            'user_id' => 8,
            'hidden' => false,
            'trust_level' => 1,
            'deleted_at' => NULL,
            'user_deleted' => false,
            'edit_reason' => NULL,
            'can_view_edit_history' => true,
            'wiki' => false,
        );
    }

    private function getUpdatePostResponse(){
        return array (
            'post' =>
                array (
                    'id' => 282,
                    'name' => 'changedf_namne',
                    'username' => 'rossifumi4646',
                    'avatar_template' => '/user_avatar/community.stage.atarca.es/rossifumi4646/{size}/43_2.png',
                    'created_at' => '2022-04-04T06:57:15.885Z',
                    'cooked' => '<p>ahopra esta updatedao kjlhlh kljhlkj lkjhklj h</p>',
                    'post_number' => 1,
                    'post_type' => 1,
                    'updated_at' => '2022-04-04T11:06:49.838Z',
                    'reply_count' => 0,
                    'reply_to_post_number' => NULL,
                    'quote_count' => 0,
                    'incoming_link_count' => 0,
                    'reads' => 2,
                    'readers_count' => 1,
                    'score' => 0.4,
                    'yours' => true,
                    'topic_id' => 210,
                    'topic_slug' => 'este-es-el-titulo-modificado',
                    'display_username' => 'changedf_namne',
                    'primary_group_name' => NULL,
                    'primary_group_flair_url' => NULL,
                    'primary_group_flair_bg_color' => NULL,
                    'primary_group_flair_color' => NULL,
                    'version' => 4,
                    'can_edit' => true,
                    'can_delete' => false,
                    'can_recover' => false,
                    'can_wiki' => true,
                    'user_title' => NULL,
                    'bookmarked' => false,
                    'actions_summary' =>
                        array (
                            0 =>
                                array (
                                    'id' => 3,
                                    'can_act' => true,
                                ),
                            1 =>
                                array (
                                    'id' => 4,
                                    'can_act' => true,
                                ),
                            2 =>
                                array (
                                    'id' => 8,
                                    'can_act' => true,
                                ),
                            3 =>
                                array (
                                    'id' => 7,
                                    'can_act' => true,
                                ),
                        ),
                    'moderator' => false,
                    'admin' => false,
                    'staff' => false,
                    'user_id' => 15,
                    'draft_sequence' => 7,
                    'hidden' => false,
                    'trust_level' => 4,
                    'deleted_at' => NULL,
                    'user_deleted' => false,
                    'edit_reason' => 'dolore in',
                    'can_view_edit_history' => true,
                    'wiki' => false,
                    'reviewable_id' => NULL,
                    'reviewable_score_count' => 0,
                    'reviewable_score_pending_count' => 0,
                ),
        );
    }

    private function getUpdateTopicResponse(){
        return array (
            'basic_topic' =>
                array (
                    'id' => 210,
                    'title' => 'Este es el titulo modificado',
                    'fancy_title' => 'Este es el titulo modificado',
                    'slug' => 'este-es-el-titulo-modificado',
                    'posts_count' => 1,
                ),
        );
    }

}