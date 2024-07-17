<?php

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
}

// CSVフォーマットページのコールバック関数
function vendor_csv_format_page_callback() {
    $last_csv_file = get_option('last_csv_file', 'なし');
    global $wpdb;
    $table_name = $wpdb->prefix . 'custom_product_data';
    $results = $wpdb->get_results("SELECT * FROM $table_name");

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
        <h2><?php _e('フォーマットされた商品データ', 'wc-vendors'); ?></h2>
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
    if (isset($_POST['upload_csv']) && !empty($_FILES['csv_file']['tmp_name'])) {
        // nonceを確認
        if (!isset($_POST['csv_upload_nonce']) || !wp_verify_nonce($_POST['csv_upload_nonce'], 'csv_upload_action')) {
            wp_die('Nonce verification failed.');
        }
        update_option('last_csv_file', $_FILES['csv_file']['name']);
        process_csv_data($_FILES['csv_file']['tmp_name']);
    }
}

// CSVファイルの処理関数
function process_csv_data($file) {
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

        // 日付を適切な形式に変換
        if (isset($data['注文日時'])) {
            $data['注文日時'] = date('Y-m-d H:i:s', strtotime($data['注文日時']));
        }

        // ヘッダーをProductDataにマッピング
        $mapped_data = [];
        foreach ($mapped_header as $csv_key => $property) {
            $mapped_data[$property] = $data[$csv_key] ?? '';
        }

        $new_data[$index] = new ProductData($mapped_data);
        error_log("ProductData オブジェクト (行 {$index}): " . print_r($new_data[$index], true)); // ProductData オブジェクトのデバッグログ
    }

    // 保存前のデータをログに出力
    error_log("保存前のデータ: " . print_r($new_data, true));
    save_formatted_product_data($new_data);

    echo '<div class="notice notice-success"><p>CSVデータのインポートが成功しました！</p></div>';
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
