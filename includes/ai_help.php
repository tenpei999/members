<?php
function get_site_articles() {
    $args = array(
        'numberposts' => -1,
        'post_status' => 'publish',
        'post_type' => array('post', 'page', 'custom_post_type') // 必要に応じてカスタム投稿タイプを追加
    );

    $posts = get_posts($args);
    $articles = array();

    foreach ($posts as $post) {
        $articles[] = array(
            'title' => $post->post_title,
            'content' => $post->post_content
        );
    }

    return $articles;
}

function ai_help($atts) {
    ob_start();
    $articles = get_site_articles();
    $article_texts = '';

    foreach ($articles as $article) {
        $article_texts .= $article['title'] . ": " . $article['content'] . "\n\n";
    }
    ?>
    <div id="custom-help-container">
        <h2>サイトヘルプ</h2>
        <textarea id="custom-help-input" rows="4" placeholder="質問を入力してください..."></textarea>
        <button id="custom-help-submit">送信</button>
        <div id="custom-help-response"></div>
    </div>
    <script>
        jQuery(document).ready(function ($) {
            $('#custom-help-submit').on('click', function (e) {
                e.preventDefault();
                var userMessage = $('#custom-help-input').val();
                var apiKey = '<?php echo get_option('ai_engine_api_key'); ?>';
                var siteArticles = <?php echo json_encode($article_texts); ?>;

                $.ajax({
                    url: 'https://api.openai.com/v1/engines/davinci-codex/completions',
                    method: 'POST',
                    headers: {
                        'Authorization': 'Bearer ' + apiKey,
                        'Content-Type': 'application/json'
                    },
                    data: JSON.stringify({
                        prompt: siteArticles + "\n\nQ: " + userMessage + "\nA:",
                        max_tokens: 150
                    }),
                    success: function (response) {
                        $('#custom-help-response').text(response.choices[0].text);
                    },
                    error: function (error) {
                        console.log(error);
                    }
                });
            });
        });
    </script>
    <style>
        #custom-help-container {
            width: 100%;
            max-width: 600px;
            margin: 0 auto;
            padding: 20px;
            border: 1px solid #ddd;
            border-radius: 5px;
            background: #f9f9f9;
        }

        #custom-help-input {
            width: 100%;
            padding: 10px;
            margin-bottom: 10px;
        }

        #custom-help-submit {
            padding: 10px 20px;
            background: #0073aa;
            color: #fff;
            border: none;
            cursor: pointer;
        }

        #custom-help-response {
            margin-top: 20px;
            padding: 10px;
            background: #eee;
        }
    </style>
    <?php
    return ob_get_clean();
}
add_shortcode('ai_help', 'ai_help_function');
