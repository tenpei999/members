jQuery(document).ready(function($) {
    $('.movie-thumbnail').hover(
        function() {
            var iframe = $(this).find('iframe').get(0);
            if (iframe) {
                var player = new Vimeo.Player(iframe);
                player.play();
                setTimeout(function() {
                    player.pause();
                    player.setCurrentTime(0);
                }, 5000);
            }
        },
        function() {
            var iframe = $(this).find('iframe').get(0);
            if (iframe) {
                var player = new Vimeo.Player(iframe);
                player.pause();
                player.setCurrentTime(0);
            }
        }
    );
});

