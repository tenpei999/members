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


// ProductData クラスの定義
class ProductData {
    public $order_number;
    public $order_date;
    public $supplier_id;
    public $shop_name;
    public $product_branch_code;
    public $product_item_id;
    public $order_product_management_id;
    public $delivery_address;
    public $delivery_corporate_name;
    public $department_in_charge;
    public $delivery_name;
    public $delivery_phone_number;
    public $order_product_title;
    public $order_details;
    public $quantity_per_set;
    public $order_set_quantity;
    public $total_quantity;
    public $set_unit_price_incl_tax;
    public $set_unit_price_excl_tax;
    public $set_unit_price_tax;
    public $selling_price_incl_tax;
    public $selling_price_excl_tax;
    public $selling_price_tax;
    public $tax_rate;
    public $jan_code;
    public $manufacturer_part_number;

    public function __construct($data) {
        $this->order_number = $data['order_number'] ?? '';
        $this->order_date = $data['order_date'] ?? '';
        $this->supplier_id = $data['supplier_id'] ?? '';
        $this->shop_name = $data['shop_name'] ?? '';
        $this->product_branch_code = $data['product_branch_code'] ?? '';
        $this->product_item_id = $data['product_item_id'] ?? '';
        $this->order_product_management_id = $data['order_product_management_id'] ?? '';
        $this->delivery_address = $data['delivery_address'] ?? '';
        $this->delivery_corporate_name = $data['delivery_corporate_name'] ?? '';
        $this->department_in_charge = $data['department_in_charge'] ?? '';
        $this->delivery_name = $data['delivery_name'] ?? '';
        $this->delivery_phone_number = $data['delivery_phone_number'] ?? '';
        $this->order_product_title = $data['order_product_title'] ?? '';
        $this->order_details = $data['order_details'] ?? '';
        $this->quantity_per_set = $data['quantity_per_set'] ?? '';
        $this->order_set_quantity = $data['order_set_quantity'] ?? '';
        $this->total_quantity = $data['total_quantity'] ?? '';
        $this->set_unit_price_incl_tax = $data['set_unit_price_incl_tax'] ?? '';
        $this->set_unit_price_excl_tax = $data['set_unit_price_excl_tax'] ?? '';
        $this->set_unit_price_tax = $data['set_unit_price_tax'] ?? '';
        $this->selling_price_incl_tax = $data['selling_price_incl_tax'] ?? '';
        $this->selling_price_excl_tax = $data['selling_price_excl_tax'] ?? '';
        $this->selling_price_tax = $data['selling_price_tax'] ?? '';
        $this->tax_rate = $data['tax_rate'] ?? '';
        $this->jan_code = $data['jan_code'] ?? '';
        $this->manufacturer_part_number = $data['manufacturer_part_number'] ?? '';
    }
}

// テーブル作成関数
function create_custom_product_table() {
    global $wpdb;
    $table_name = $wpdb->prefix . 'custom_product_data';
    
    $charset_collate = $wpdb->get_charset_collate();

    $sql = "CREATE TABLE IF NOT EXISTS $table_name (
        id mediumint(9) NOT NULL AUTO_INCREMENT,
        product_id bigint(20) NOT NULL,
        sku varchar(100) NOT NULL,
        name varchar(255) NOT NULL,
        price decimal(10,2) NOT NULL,
        stock_quantity int(11) NOT NULL,
        last_updated datetime DEFAULT CURRENT_TIMESTAMP NOT NULL,
        post_date datetime NOT NULL,
        PRIMARY KEY  (id),
        UNIQUE KEY product_id (product_id)
    ) $charset_collate;";

    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    dbDelta($sql);
}
add_action('init', 'create_custom_product_table');

// 管理メニューにサブメニューを追加
add_action('admin_menu', 'extend_vendor_dashboard_pages');
function extend_vendor_dashboard_pages() {
    if (current_user_can('manage_options')) {
        return;
    }

    add_submenu_page(
        'edit.php?post_type=product',
        __( 'CSV フォーマット', 'wc-vendors' ),
        __( 'CSV フォーマット', 'wc-vendors' ),
        'manage_product',
        'wcv-vendor-csv-format',
        'vendor_csv_format_page_callback'
    );

    add_submenu_page(
        'edit.php?post_type=product',
        __( 'CSV インポート結果', 'wc-vendors' ),
        __( 'CSV インポート結果', 'wc-vendors' ),
        'manage_product',
        'wcv-vendor-csv-import-results',
        'vendor_csv_import_results_page_callback'
    );
}

// CSVフォーマットページのコールバック関数
function vendor_csv_format_page_callback() {
    $last_csv_file = get_option('last_csv_file', 'なし');

    ?>
    <div class="wrap">
        <h1><?php _e('CSV フォーマット', 'wc-vendors'); ?></h1>
        <p><?php _e('ここにCSVフォーマットに関する説明や設定を追加できます。', 'wc-vendors'); ?></p>
        <p><?php _e('最後にアップロードされたCSVファイル: ', 'wc-vendors'); ?><?php echo esc_html($last_csv_file); ?></p>
        <form method="post" enctype="multipart/form-data">
            <?php wp_nonce_field('csv_upload_action', 'csv_upload_nonce'); ?>
            <input type="file" name="csv_file" accept=".csv" required>
            <input type="submit" name="upload_csv" value="CSVをアップロード" class="button button-primary">
        </form>
    </div>
    <?php
    if (isset($_POST['upload_csv']) && !empty($_FILES['csv_file']['tmp_name'])) {
        // nonceを確認
        if (!isset($_POST['csv_upload_nonce']) || !wp_verify_nonce($_POST['csv_upload_nonce'], 'csv_upload_action')) {
            wp_die('Nonce verification failed.');
        }
        update_option('last_csv_file', $_FILES['csv_file']['name']);
        csv_to_woocommerce_process_csv($_FILES['csv_file']['tmp_name']);
    }
}

// CSVファイルの処理関数
function csv_to_woocommerce_process_csv($file) {
    // ファイルの内容をUTF-8に変換
    $file_contents = file_get_contents($file);
    $encoding = mb_detect_encoding($file_contents, 'SJIS-win, EUC-JP, JIS, UTF-8, ASCII');
    if ($encoding != 'UTF-8') {
        $file_contents = mb_convert_encoding($file_contents, 'UTF-8', $encoding);
    }

    // ファイル内容を行ごとに分割
    $lines = preg_split('/\r\n|\r|\n/', $file_contents);

    // ヘッダー行を取得してカンマで分割
    $header = str_getcsv(array_shift($lines), ',');

    error_log("CSVヘッダー: " . print_r($header, true)); // ヘッダーをデバッグログに出力

    // ヘッダーをProductDataのプロパティに変換
    $mapped_header = array(
        '注文番号' => 'order_number',
        '注文日時' => 'order_date',
        'サプライヤーID' => 'supplier_id',
        'ショップ名' => 'shop_name',
        '商品管理枝番号' => 'product_branch_code',
        '商品アイテムID' => 'product_item_id',
        '注文時商品管理ID' => 'order_product_management_id',
        '送付先住所' => 'delivery_address',
        '送付先法人名' => 'delivery_corporate_name',
        '担当部署' => 'department_in_charge',
        '送付先氏名' => 'delivery_name',
        '送付先電話番号' => 'delivery_phone_number',
        '注文時商品タイトル' => 'order_product_title',
        '注文時内訳' => 'order_details',
        'セット毎数量' => 'quantity_per_set',
        '注文セット数量' => 'order_set_quantity',
        '合計数量' => 'total_quantity',
        'セット単価（税込）' => 'set_unit_price_incl_tax',
        'セット単価（税抜）' => 'set_unit_price_excl_tax',
        'セット単価（消費税額）' => 'set_unit_price_tax',
        '販売価格（税込）' => 'selling_price_incl_tax',
        '販売価格（税抜）' => 'selling_price_excl_tax',
        '販売価格（消費税額）' => 'selling_price_tax',
        '消費税率' => 'tax_rate',
        'JANコード' => 'jan_code',
        'メーカー品番' => 'manufacturer_part_number',
    );

    // デバッグ: mapped_headerの内容をログに出力
    error_log("マッピングされたヘッダー: " . print_r($mapped_header, true));

    $new_data = array();
    foreach ($lines as $index => $line) {
        if (empty(trim($line))) {
            continue; // 空行をスキップ
        }
        $row = str_getcsv($line, ',');
        if (count($row) !== count($header)) {
            error_log("行の数がヘッダーの数と一致しません: " . print_r($row, true));
            continue; // ヘッダーと行の数が一致しない場合スキップ
        }

        // ヘッダーを使用してデータ行にキーを設定
        $data = array_combine($header, $row);
        if ($data === FALSE) {
            error_log("array_combineに失敗しました: " . print_r($row, true));
            continue; // データの結合に失敗した場合はスキップ
        }
        error_log("読み取ったデータ (行 {$index}): " . print_r($data, true)); // 各行のデータをデバッグログに出力

        // ヘッダーをProductDataにマッピング
        $mapped_data = [];
        foreach ($mapped_header as $csv_key => $property) {
            $mapped_data[$property] = $data[$csv_key] ?? '';
        }

        $new_data[$index] = new ProductData($mapped_data);
        error_log("ProductData オブジェクト (行 {$index}): " . print_r($new_data[$index], true)); // ProductData オブジェクトのデバッグログ

        // process_csv_to_woocommerce_create_productに渡す前にデバッグログを追加
        error_log("process_csv_to_woocommerce_create_productに渡されるデータ (行 {$index}): " . print_r($new_data[$index], true));
        process_csv_to_woocommerce_create_product($new_data[$index]); // オブジェクトを渡して関数を呼び出し
    }

    // 保存前のデータをログに出力
    error_log("保存前のデータ: " . print_r($new_data, true));
    save_formatted_product_data($new_data);

    echo '<div class="notice notice-success"><p>CSVデータのインポートが成功しました！</p></div>';
}

function process_csv_to_woocommerce_create_product($product_data) {
    $product = new WC_Product_Simple();

    // デバッグ: データの内容をログ出力
    error_log("加工前のdata: " . print_r($product_data, true));

    // 各キーの存在をチェックしてデータを設定
    $name = $product_data->order_product_title;
    $sku = $product_data->order_product_management_id;
    $price = $product_data->selling_price_incl_tax;
    $description = $product_data->order_details;
    $stock_quantity = $product_data->total_quantity;
    $post_date = $product_data->order_date;

    // 変数の値をデバッグログに出力
    error_log("CSVデータ - 名前: $name, SKU: $sku, 価格: $price, 説明: $description, 在庫数量: $stock_quantity, 注文日時: $post_date");

    try {
        // WooCommerce商品の設定
        $product->set_name($name);
        $product->set_sku($sku);
        $product->set_regular_price($price);
        $product->set_description("■商品説明\n\nNETSEA仕入れ商品\n\n". $description);
        $product->set_manage_stock(true);
        $product->set_stock_quantity($stock_quantity);
        $product->set_date_created($post_date);
        $product->save();
    } catch (WC_Data_Exception $e) {
        error_log('WooCommerce エラー: ' . $e->getMessage());
    }
}

function save_formatted_product_data($data) {
    global $wpdb;
    $table_name = $wpdb->prefix . 'custom_product_data';

    foreach ($data as $product_data) {
        $product_id = $product_data->product_item_id;
        $sku = $product_data->order_product_management_id;
        $name = $product_data->order_product_title;
        $price = $product_data->selling_price_incl_tax;
        $stock_quantity = $product_data->total_quantity;
        $post_date = $product_data->order_date;

        error_log("保存するデータ - 商品ID: $product_id, SKU: $sku, 名前: $name, 価格: $price, 在庫数量: $stock_quantity, 注文日時: $post_date"); // デバッグ情報の追加

        // カスタムテーブルにデータを保存
        $wpdb->replace(
            $table_name,
            array(
                'product_id' => $product_id,
                'sku' => $sku,
                'name' => $name,
                'price' => $price,
                'stock_quantity' => $stock_quantity,
                'last_updated' => current_time('mysql'),
                'post_date' => $post_date
            ),
            array(
                '%d', '%s', '%s', '%f', '%d', '%s', '%s'
            )
        );
    }
}

// CSVインポート結果ページのコールバック関数
function vendor_csv_import_results_page_callback() {
    global $wpdb;
    $table_name = $wpdb->prefix . 'custom_product_data';
    $results = $wpdb->get_results("SELECT * FROM $table_name");

    ?>
    <div class="wrap">
        <h1><?php _e('CSV インポート結果', 'wc-vendors'); ?></h1>
        <table class="widefat fixed" cellspacing="0">
            <thead>
                <tr>
                    <th><?php _e('ID', 'wc-vendors'); ?></th>
                    <th><?php _e('商品ID', 'wc-vendors'); ?></th>
                    <th><?php _e('SKU', 'wc-vendors'); ?></th>
                    <th><?php _e('名前', 'wc-vendors'); ?></th>
                    <th><?php _e('価格', 'wc-vendors'); ?></th>
                    <th><?php _e('在庫数量', 'wc-vendors'); ?></th>
                    <th><?php _e('最終更新日', 'wc-vendors'); ?></th>
                    <th><?php _e('注文日時', 'wc-vendors'); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($results as $row) : ?>
                <tr>
                    <td><?php echo esc_html($row->id); ?></td>
                    <td><?php echo esc_html($row->product_id); ?></td>
                    <td><?php echo esc_html($row->sku); ?></td>
                    <td><?php echo esc_html($row->name); ?></td>
                    <td><?php echo esc_html($row->price); ?></td>
                    <td><?php echo esc_html($row->stock_quantity); ?></td>
                    <td><?php echo esc_html($row->last_updated); ?></td>
                    <td><?php echo esc_html($row->post_date); ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php
}
