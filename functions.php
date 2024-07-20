<?php
function zakra_child_enqueue_scripts() {
    // main.js を子テーマのディレクトリから読み込む
    wp_enqueue_script( 'zakra-child-script', get_stylesheet_directory_uri() . '/main.js', array('jquery'), wp_get_theme()->get('Version'), true );

    if (is_page('movies')) {
        wp_enqueue_script('stop-video-script', get_template_directory_uri() . '/js/stop-video.js', array('jquery'), null, true);
    }
}
add_action( 'wp_enqueue_scripts', 'zakra_child_enqueue_scripts' );

function register_custom_menus() {
    register_nav_menus(
        array(
            'menu-login' => __( 'Login Menu', 'zakra-child' ),
        )
    );
}
add_action( 'init', 'register_custom_menus' );

if ( ! function_exists( 'zakra_primary_menu' ) ) :
    /**
     * Primary menu.
     */
    function zakra_primary_menu() {
        // カスタマイザーでメニューが無効化されている場合は終了します
        if ( empty( get_theme_mod( 'zakra_enable_primary_menu', true ) ) ) {
            return;
        }

        // メニューのスタイルを継承しつつ、ログイン状態に応じて異なるメニューを表示
        $menu_class = 'zak-primary-menu';
        $menu_id = 'zak-primary-menu';
        $theme_location = 'menu-primary';

        if ( is_user_logged_in() ) {
            $menu_class = 'zak-login-menu';
            $menu_id = 'zak-login-menu';
            $theme_location = 'menu-login';
        }

        // クラスを適切に組み立てる
        $nav_classes = 'zak-main-nav main-navigation zak-primary-nav zak-layout-1 zak-layout-1-style-1';

        echo '<nav id="zak-primary-nav" class="' . esc_attr($nav_classes) . '">';
        wp_nav_menu(
            array(
                'theme_location' => $theme_location,
                'menu_id'        => esc_attr($menu_id),
                'menu_class'     => 'zak-primary-menu ' . esc_attr($menu_class),
                'container'      => '',
                'fallback_cb'    => 'zakra_menu_fallback',
            )
        );
        echo '</nav><!-- #zak-primary-nav -->';
    }
endif;

// ショートコードの登録
if (!function_exists('movie_archive_shortcode')) {
    function movie_archive_shortcode() {
        ob_start();
        $paged = (get_query_var('paged')) ? get_query_var('paged') : 1;
        $args = array(
            'post_type' => 'movie',
            'posts_per_page' => 10,
            'paged' => $paged
        );
        $movie_query = new WP_Query($args);
        if ($movie_query->have_posts()) :
            ?>
            <article class="movie-container">
            <?php
            while ($movie_query->have_posts()) : $movie_query->the_post();
                ?>
                <div class="movie-item">
                    <a class="movie-item-anchor" href="<?php the_permalink(); ?>">
                        <h3 class="movie-item-title"><?php the_title(); ?></h3>
                        <div class="movie-thumbnail">
                            <?php
                            if (has_post_thumbnail()) {
                                the_post_thumbnail('medium');
                            } else {
                                echo '<p>No thumbnail available</p>';
                            }
                            ?>
                        </div>
                        <div class="movie-content">
                            <?php
                            $vimeo_excerpt = get_field('vimeo_excerpt');
                            if ($vimeo_excerpt) {
                                echo wp_kses_post($vimeo_excerpt);
                            } else {
                                echo '<p>No vimeo_excerpt field found</p>';
                            }
                            ?>
                        </div>
                    </a>
                </div>
                <?php
            endwhile;
            ?>
            </article>
            <div class="pagination">
                <?php
                echo paginate_links(array(
                    'total' => $movie_query->max_num_pages,
                    'current' => $paged,
                    'format' => '?paged=%#%',
                    'show_all' => false,
                    'type' => 'plain',
                    'end_size' => 2,
                    'mid_size' => 1,
                    'prev_next' => true,
                    'prev_text' => __('« Previous'),
                    'next_text' => __('Next »'),
                    'add_args' => false,
                    'add_fragment' => ''
                ));
                ?>
            </div>
            <?php
            wp_reset_postdata();
        else :
            echo '<p>No movies found</p>';
        endif;
        return ob_get_clean();
    }
}
add_shortcode('movie_archive', 'movie_archive_shortcode');

// ユーザーがログインしている場合に .site-description を非表示にし、.site-description の下に .login-notice を追加する
function custom_header_top_left_content() {
    if (is_user_logged_in()) {
        $current_user = wp_get_current_user();
        $username = $current_user->display_name;
        ?>
        <script>
            document.addEventListener('DOMContentLoaded', function() {
                var siteDescription = document.querySelector('.site-description');
                if (siteDescription) {
                    siteDescription.style.display = 'none';
                    var loginNotice = document.createElement('div');
                    loginNotice.className = 'login-notice';
                    
                    // 日本時間に合わせて挨拶を決定
                    var greeting;
                    var now = new Date();
                    var hours = now.getHours();
                    if (hours >= 5 && hours < 12) {
                        greeting = 'おはようございます';
                    } else if (hours >= 12 && hours < 18) {
                        greeting = 'こんにちは';
                    } else {
                        greeting = 'こんばんは';
                    }
                    
                    loginNotice.textContent = 'ログインに成功しました。' + greeting + '、<?php echo esc_js($username); ?>さん';
                    siteDescription.insertAdjacentElement('afterend', loginNotice);
                }
            });
        </script>
        <style>
            .login-notice {
                margin-top: 5px;
                padding: 5px;
            }
        </style>
        <?php
    }
}
add_action('zakra_action_header_main', 'custom_header_top_left_content');

// ACFカスタムブロックの登録
function register_acf_custom_blocks() {
    if (function_exists('acf_register_block_type')) {
        acf_register_block_type(array(
            'name'              => 'custom-card-block',
            'title'             => __('Custom Card Block'),
            'description'       => __('A custom card block for my page.'),
            'render_template'   => 'template-parts/blocks/custom-card-block.php',
            'category'          => 'formatting',
            'icon'              => 'index-card',
            'keywords'          => array('card', 'custom'),
            'enqueue_style'     => get_template_directory_uri() . '/template-parts/blocks/custom-card-block.css',
        ));
    }
}
add_action('acf/init', 'register_acf_custom_blocks');

// カスタムブロックのエディタ用スクリプトとスタイルを登録
function custom_card_block_editor_assets() {
    wp_register_script(
        'custom-card-block-editor',
        get_stylesheet_directory_uri() . '/template-parts/blocks/custom-card-block/js/custom-card-block.js',
        array( 'wp-blocks', 'wp-element', 'wp-editor' ),
        filemtime( get_stylesheet_directory() . '/template-parts/blocks/custom-card-block/js/custom-card-block.js' )
    );

    wp_register_style(
        'custom-card-block-editor',
        get_stylesheet_directory_uri() . '/template-parts/blocks/custom-card-block/css/custom-card-block.css',
        array(),
        filemtime( get_stylesheet_directory() . '/template-parts/blocks/custom-card-block/css/custom-card-block.css' )
    );

	    register_block_type( 'custom/card-block', array(
        'editor_script' => 'custom-card-block-editor',
        'editor_style'  => 'custom-card-block-editor',
    ) );
}
add_action( 'enqueue_block_editor_assets', 'custom_card_block_editor_assets' );

// カスタムブロックのフロントエンド用スタイルを登録
function custom_card_block_frontend_assets() {
    wp_enqueue_style(
        'custom-card-block',
        get_stylesheet_directory_uri() . '/template-parts/blocks/custom-card-block/css/custom-card-block.css',
        array(),
        filemtime( get_stylesheet_directory() . '/template-parts/blocks/custom-card-block/css/custom-card-block.css' )
    );
}
add_action( 'wp_enqueue_scripts', 'custom_card_block_frontend_assets' );

// my-page-welcome-block用スクリプトとスタイルを登録
function custom_my_page_welcome_block_editor_assets() {
    wp_register_script(
        'my-page-welcome-block',
        get_stylesheet_directory_uri() . '/template-parts/blocks/my-page-welcome-block/js/my-page-welcome.js',
        array( 'wp-blocks', 'wp-i18n', 'wp-element', 'wp-editor' ),
        filemtime( get_stylesheet_directory() . '/template-parts/blocks/my-page-welcome-block/js/my-page-welcome.js' )
    );

    register_block_type( 'custom/my-page-welcome-block', array(
        'editor_script' => 'my-page-welcome-block',
        'render_callback' => 'render_my_page_welcome_block'
    ) );
}
add_action( 'init', 'custom_my_page_welcome_block_editor_assets' );

// カスタムブロックのフロントエンド用スタイルを登録
function custom_my_page_welcome_block_frontend_assets() {
    wp_enqueue_style(
        'my-page-welcome-block',
        get_stylesheet_directory_uri() . '/template-parts/blocks/my-page-welcome-block/css/my-page-welcome.css',
        array(),
        filemtime( get_stylesheet_directory() . '/template-parts/blocks/my-page-welcome-block/css/my-page-welcome.css' )
    );
}
add_action( 'wp_enqueue_scripts', 'custom_my_page_welcome_block_frontend_assets' );

// 動的ブロックのレンダーコールバック関数をインクルード
require get_stylesheet_directory() . '/template-parts/blocks/my-page-welcome-block/my-page-welcome-block.php';

// カスタムブロックのエディタ用スクリプトとスタイルを登録
function custom_announcements_block_editor_assets() {
    wp_register_script(
        'announcements-block',
        get_stylesheet_directory_uri() . '/template-parts/blocks/announcements-block/js/announcements-block.js',
        array( 'wp-blocks', 'wp-i18n', 'wp-element', 'wp-editor' ),
        filemtime( get_stylesheet_directory() . '/template-parts/blocks/announcements-block/js/announcements-block.js' )
    );

    register_block_type( 'custom/announcements-block', array(
        'editor_script' => 'announcements-block',
        'render_callback' => 'render_announcements_block'
    ) );
}
add_action( 'init', 'custom_announcements_block_editor_assets' );

// カスタムブロックのフロントエンド用スタイルを登録
function custom_announcements_block_frontend_assets() {
    wp_enqueue_style(
        'announcements-block',
        get_stylesheet_directory_uri() . '/template-parts/blocks/announcements-block/css/announcements-block.css',
        array(),
        filemtime( get_stylesheet_directory() . '/template-parts/blocks/announcements-block/css/announcements-block.css' )
    );
}
add_action( 'wp_enqueue_scripts', 'custom_announcements_block_frontend_assets' );

// 動的ブロックのレンダーコールバック関数をインクルード
require get_stylesheet_directory() . '/template-parts/blocks/announcements-block/announcements-block.php';

require get_stylesheet_directory() . '/includes/csv_format.php';

// フロントエンドでのチャットボットポップアップの出力を無効にする
add_action('wp_enqueue_scripts', 'disable_ai_engine_popup', 1);
function disable_ai_engine_popup() {
    // チャットボットに関連するスクリプトをデキューする
    wp_dequeue_script('mwai_chatbot');
    wp_dequeue_script('mwai_highlight'); // オプションで使用されるシンタックスハイライト用スクリプト

    // チャットボットに関連するテーマスタイルをデキューする
    $themes = ['chatgpt', 'messages', 'timeless']; // 実際のテーマIDのリストを指定します
    foreach ($themes as $themeId) {
        wp_dequeue_style("mwai_chatbot_theme_$themeId");
    }
}

// チャットボットポップアップの注入を無効にする
add_action('wp_footer', 'remove_chatbot_popup_injection', 1);
function remove_chatbot_popup_injection() {
    remove_action('wp_footer', array('Meow_MWAI_Modules_Chatbot', 'inject_chat'));
}

// REST API のチャットボットエンドポイントを無効にする
add_filter('rest_endpoints', 'disable_chatbot_rest_endpoints');
function disable_chatbot_rest_endpoints($endpoints) {
    if (isset($endpoints['/mwai-ui/v1/chats/submit'])) {
        unset($endpoints['/mwai-ui/v1/chats/submit']);
    }
    return $endpoints;
}

// チャットボットのパラメータを空にするフィルター
add_filter('mwai_chatbot_params', function ($params) {
    return []; // フロントエンドでチャットボットを表示しないようにするために空のパラメータを返す
});
