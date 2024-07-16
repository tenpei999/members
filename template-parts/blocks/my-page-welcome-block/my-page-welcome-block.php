<?php
function render_my_page_welcome_block() {
    if ( is_user_logged_in() ) {
        $current_user = wp_get_current_user();
        $user_name = esc_html( $current_user->display_name );
        return '<h2>' . $user_name . 'さんのマイページ</h2>';
    } else {
        return '<p>ログインしてください。</p>';
    }
}
