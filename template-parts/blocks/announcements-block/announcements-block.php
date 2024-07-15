<?php
function render_announcements_block() {
    $query = new WP_Query( array(
        'post_type' => 'announcement',
        'posts_per_page' => 5
    ));

    $output = '<div class="announcement-list">';
    if ( $query->have_posts() ) {
        while ( $query->have_posts() ) {
            $query->the_post();
            $output .= '<div class="announcement-item">';
            $output .= '<span class="announcement-date">' . get_the_date('Y/n/j') . '</span> ';
            $output .= '<span class="announcement-title"><a href="' . get_permalink() . '">' . get_the_title() . '</a></span>';
            $output .= '</div>';
        }
    } else {
        $output .= '<p>お知らせはありません。</p>';
    }
    $output .= '</div>';

    wp_reset_postdata();

    return $output;
}
