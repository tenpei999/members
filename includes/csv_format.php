<?php

global $current_user;
$current_user = wp_get_current_user();

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

// WooCommerceの商品IDと注文日時を用いて一意の値を判断する関数
function is_duplicate_product($product_item_id, $order_date) {
    global $wpdb, $current_user;
    $table_name = $wpdb->prefix . 'custom_product_data';
    $vendor_id = $current_user->ID;

    error_log(print_r($vendor_id, true));

    // 日付の形式が適切であることを確認する
    $formatted_date = DateTime::createFromFormat('Y-m-d H:i', $order_date);
    $order_date_formatted = $formatted_date ? $formatted_date->format('Y-m-d H:i:s') : '';

    // ベンダーIDでフィルタリング
    $query = $wpdb->prepare("SELECT COUNT(*) FROM $table_name WHERE product_id = %d AND post_date = %s AND vendor_id = %d", $product_item_id, $order_date, $vendor_id);
    $count = $wpdb->get_var($query);
    return $count > 0;
}

// CSVファイルの処理関数
function process_csv_data($file) {
    global $current_user;
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
            $date = DateTime::createFromFormat('Y年m月d日 H:i', $data['注文日時']);
            $data['order_date'] = $date ? $date->format('Y-m-d H:i') : '1970-01-01 00:00';
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

    foreach ($new_data as $product_data) {
        // ベンダーIDを追加
        $product_data->vendor_id = $current_user->ID;
        // データを保存
        save_formatted_product_data($product_data);
    }

    return $new_data;
}

// ProductData を保存する関数
function save_formatted_product_data($product_data) {
    global $wpdb;
    $table_name = $wpdb->prefix . 'custom_product_data';

    // データの重複確認
    if (is_duplicate_product($product_data->product_item_id, $product_data->order_date)) {
        error_log("重複したデータ: " . print_r($product_data, true));
        return;
    }

    // データをテーブルに保存
    $wpdb->insert($table_name, array(
        'vendor_id' => $product_data->vendor_id,
        'product_id' => $product_data->product_item_id,
        'post_date' => $product_data->order_date,
        'order_number' => $product_data->order_number,
        'order_date' => $product_data->order_date,
        'supplier_id' => $product_data->supplier_id,
        'shop_name' => $product_data->shop_name,
        'product_branch_code' => $product_data->product_branch_code,
        'order_product_management_id' => $product_data->order_product_management_id,
        'delivery_address' => $product_data->delivery_address,
        'delivery_corporate_name' => $product_data->delivery_corporate_name,
        'department_in_charge' => $product_data->department_in_charge,
        'delivery_name' => $product_data->delivery_name,
        'delivery_phone_number' => $product_data->delivery_phone_number,
        'order_product_title' => $product_data->order_product_title,
        'order_details' => $product_data->order_details,
        'quantity_per_set' => $product_data->quantity_per_set,
        'order_set_quantity' => $product_data->order_set_quantity,
        'total_quantity' => $product_data->total_quantity,
        'set_unit_price_incl_tax' => $product_data->set_unit_price_incl_tax,
        'set_unit_price_excl_tax' => $product_data->set_unit_price_excl_tax,
        'set_unit_price_tax' => $product_data->set_unit_price_tax,
        'selling_price_incl_tax' => $product_data->selling_price_incl_tax,
        'selling_price_excl_tax' => $product_data->selling_price_excl_tax,
        'selling_price_tax' => $product_data->selling_price_tax,
        'tax_rate' => $product_data->tax_rate,
        'jan_code' => $product_data->jan_code,
        'manufacturer_part_number' => $product_data->manufacturer_part_number,
    ));
}
