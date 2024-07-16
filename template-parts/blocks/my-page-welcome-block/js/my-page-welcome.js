( function( blocks, i18n, element ) {
    var el = element.createElement;

    blocks.registerBlockType( 'custom/my-page-welcome-block', {
        title: i18n.__( 'My Page Welcome Block' ),
        icon: 'admin-users',
        category: 'widgets',
        edit: function( props ) {
            return el(
                'p',
                null,
                i18n.__( 'This block displays the current user\'s name.' )
            );
        },
        save: function() {
            return null; // Dynamic block; content generated server-side
        }
    } );
} )( window.wp.blocks, window.wp.i18n, window.wp.element );
