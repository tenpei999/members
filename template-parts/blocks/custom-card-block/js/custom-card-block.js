( function( blocks, editor, element, components ) {
    var el = element.createElement;
    var InnerBlocks = editor.InnerBlocks;
    var MediaUpload = editor.MediaUpload;
    var Button = components.Button;

    blocks.registerBlockType( 'custom/card-block', {
        title: 'Custom Card Block',
        icon: 'index-card',
        category: 'common',
        attributes: {
            content: {
                type: 'array',
                source: 'query',
                selector: '.card',
                query: {
                    title: {
                        type: 'string',
                        source: 'text',
                        selector: 'h3'
                    },
                    text: {
                        type: 'string',
                        source: 'text',
                        selector: 'p'
                    },
                    link: {
                        type: 'string',
                        source: 'attribute',
                        attribute: 'href',
                        selector: 'a'
                    },
                    icon: {
                        type: 'string',
                        source: 'attribute',
                        attribute: 'src',
                        selector: 'img'
                    }
                },
                default: []
            }
        },
        edit: function( props ) {
            var content = props.attributes.content;

            function addCard() {
                const newContent = content.slice();
                newContent.push( { title: '', text: '', link: '', icon: '' } );
                props.setAttributes( { content: newContent } );
            }

            function updateCard( index, field, value ) {
                const newContent = content.slice();
                newContent[index][field] = value;
                props.setAttributes( { content: newContent } );
            }

            function onSelectImage(index) {
                return function(media) {
                    updateCard(index, 'icon', media.url);
                };
            }

            return el(
                'div',
                { className: 'custom-card-block-editor' },
                el(
                    'div',
                    { className: 'custom-card-block-controls' },
                    el(
                        'button',
                        { onClick: addCard },
                        'Add Card'
                    )
                ),
                content.map( ( card, index ) => {
                    return el(
                        'div',
                        { className: 'card', key: index },
                        el(
                            'input',
                            {
                                type: 'text',
                                placeholder: 'Title',
                                value: card.title,
                                onChange: function( event ) {
                                    updateCard( index, 'title', event.target.value );
                                }
                            }
                        ),
                        el(
                            'textarea',
                            {
                                placeholder: 'Text',
                                value: card.text,
                                onChange: function( event ) {
                                    updateCard( index, 'text', event.target.value );
                                }
                            }
                        ),
                        el(
                            'input',
                            {
                                type: 'text',
                                placeholder: 'Link',
                                value: card.link,
                                onChange: function( event ) {
                                    updateCard( index, 'link', event.target.value );
                                }
                            }
                        ),
                        el(
                            MediaUpload,
                            {
                                onSelect: onSelectImage(index),
                                allowedTypes: 'image',
                                render: function(obj) {
                                    return el(
                                        Button,
                                        {
                                            onClick: obj.open,
                                            isDefault: true,
                                            isLarge: true
                                        },
                                        ! card.icon ? 'Upload Image' : 'Change Image'
                                    );
                                }
                            }
                        ),
                        card.icon && el( 'img', { src: card.icon, style: { maxWidth: '100%' } } )
                    );
                } )
            );
        },
        save: function( props ) {
            var content = props.attributes.content;

            return el(
                'div',
                { className: 'custom-card-block' },
                content.map( ( card, index ) => {
                    return el(
                        'div',
                        { className: 'card', key: index },
                        el(
                            'a',
                            { href: card.link },
                            card.icon && el( 'img', { src: card.icon } ),
                            el( 'h3', null, card.title ),
                            el( 'p', null, card.text )
                        )
                    );
                } )
            );
        }
    } );
} )( window.wp.blocks, window.wp.editor, window.wp.element, window.wp.components );

