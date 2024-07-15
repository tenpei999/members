( function( blocks, i18n, element ) {
    var el = element.createElement;
    var registerBlockType = blocks.registerBlockType;

    registerBlockType( 'custom/announcements-block', {
        title: i18n.__( 'Announcements Block' ),
        icon: 'megaphone',
        category: 'widgets',
        edit: function( props ) {
            return el(
                'p',
                null,
                i18n.__( 'This block displays a list of recent announcements.' )
            );
        },
        save: function() {
            return null; // Dynamic block; content generated server-side
        }
    } );
} )( window.wp.blocks, window.wp.i18n, window.wp.element );
