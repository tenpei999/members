jQuery(document).ready(function($) {
    $('#fetch-api-data').on('click', function() {
        $.ajax({
            url: custom_ajax_object.ajax_url,
            type: 'POST',
            data: {
                action: 'fetch_api_data',
                nonce: custom_ajax_object.nonce
            },
            success: function(response) {
                if (response.success) {
                    $('#api-response').html('<pre>' + JSON.stringify(response.data, null, 2) + '</pre>');
                } else {
                    $('#api-response').html('<p>' + response.data + '</p>');
                }
            },
            error: function() {
                $('#api-response').html('<p>リクエストに失敗しました。</p>');
            }
        });
    });
});
