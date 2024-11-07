// js/blocks.js
(function(blocks, element, components, editor) {
    var el = element.createElement;
    var __ = wp.i18n.__;
    var RichText = editor.RichText;
    var InspectorControls = editor.InspectorControls;
    var SelectControl = components.SelectControl;
    var TextControl = components.TextControl;
    var ToggleControl = components.ToggleControl;

    // Resume Reading block
    blocks.registerBlockType('iasb/resume-reading', {
        title: __('Resume Reading', 'story-builder'),
        icon: 'book-alt',
        category: 'iasb-blocks',
        edit: function() {
            return el('div', {}, __('Resume Reading Button', 'story-builder'));
        },
        save: function() {
            return null; // Render in PHP
        },
    });

    // Conditional Content block
    blocks.registerBlockType('iasb/conditional-content', {
        title: __('Conditional Content', 'story-builder'),
        icon: 'randomize',
        category: 'iasb-blocks',
        attributes: {
            id: {
                type: 'number',
            },
            condition: {
                type: 'string',
            },
            content: {
                type: 'string',
            },
        },
        edit: function(props) {
            return el('div', {className: props.className},
                el(InspectorControls, {},
                    el(TextControl, {
                        label: __('Episode ID', 'story-builder'),
                        value: props.attributes.id,
                        onChange: function(value) {
                            props.setAttributes({id: parseInt(value)});
                        },
                    })
                ),
                el(RichText, {
                    tagName: 'div',
                    multiline: 'p',
                    placeholder: __('Enter conditional content...', 'story-builder'),
                    value: props.attributes.content,
                    onChange: function(value) {
                        props.setAttributes({content: value});
                    },
                }),
                el( 'input', {
                    type: 'text',
                    placeholder: 'Enter condition',
                    value: props.attributes.condition,
                    onChange: function( event ) {
                        props.setAttributes( { condition: event.target.value } );
                    },
                } ),
                
            );
        },
        save: function() {
            return null; // Render in PHP
        },
    });

    // Dynamic Content block
    blocks.registerBlockType('iasb/dynamic-content', {
        title: __('Dynamic Content', 'story-builder'),
        icon: 'admin-page',
        category: 'iasb-blocks',
        attributes: {
            type: {type: 'string', default: 'text'},
            id: {type: 'number'},
            class: {type: 'string'},
            title: {type: 'string'},
            target: {type: 'string', default: '_self'},
        },
        edit: function(props) {
            return el('div', {},
                el(InspectorControls, {},
                    el(SelectControl, {
                        label: __('Content Type', 'story-builder'),
                        value: props.attributes.type,
                        options: [
                            {label: __('Text', 'story-builder'), value: 'text'},
                            {label: __('Image', 'story-builder'), value: 'image'},
                            {label: __('Link', 'story-builder'), value: 'link'},
                            {label: __('Video', 'story-builder'), value: 'video'},
                        ],
                        onChange: function(value) {
                            props.setAttributes({type: value});
                        },
                    }),
                    el(TextControl, {
                        label: __('Content ID', 'story-builder'),
                        value: props.attributes.id,
                        onChange: function(value) {
                            props.setAttributes({id: parseInt(value)});
                        },
                    }),
                    el(TextControl, {
                        label: __('CSS Class', 'story-builder'),
                        value: props.attributes.class,
                        onChange: function(value) {
                            props.setAttributes({class: value});
                        },
                    }),
                    el(TextControl, {
                        label: __('Title', 'story-builder'),
                        value: props.attributes.title,
                        onChange: function(value) {
                            props.setAttributes({title: value});
                        },
                    }),
                    props.attributes.type === 'link' && el(SelectControl, {
                        label: __('Link Target', 'story-builder'),
                        value: props.attributes.target,
                        options: [
                            {label: __('Same Window', 'story-builder'), value: '_self'},
                            {label: __('New Window', 'story-builder'), value: '_blank'},
                        ],
                        onChange: function(value) {
                            props.setAttributes({target: value});
                        },
                    })
                ),
                el('div', {}, __('Dynamic Content: ', 'story-builder') + props.attributes.type)
            );
        },
        save: function() {
            return null; // Render in PHP
        },
    });

    // User Story Name block
    blocks.registerBlockType('iasb/user-story-name', {
        title: __('User Story Name', 'story-builder'),
        icon: 'admin-users',
        category: 'iasb-blocks',
        edit: function() {
            return el('div', {}, __('User Story Name', 'story-builder'));
        },
        save: function() {
            return null; // Render in PHP
        },
    });

    // NPC Character Name block
    blocks.registerBlockType('iasb/npc-character-name', {
        title: __('NPC Character Name', 'story-builder'),
        icon: 'groups',
        category: 'iasb-blocks',
        attributes: {
            id: {type: 'number'},
            slug: {type: 'string'},
            link: {type: 'boolean', default: false},
        },
        edit: function(props) {
            return el('div', {},
                el(InspectorControls, {},
                    el(TextControl, {
                        label: __('Character ID', 'story-builder'),
                        value: props.attributes.id,
                        onChange: function(value) {
                            props.setAttributes({id: parseInt(value)});
                        },
                    }),
                    el(TextControl, {
                        label: __('Character Slug', 'story-builder'),
                        value: props.attributes.slug,
                        onChange: function(value) {
                            props.setAttributes({slug: value});
                        },
                    }),
                    el(ToggleControl, {
                        label: __('Link to Character Profile', 'story-builder'),
                        checked: props.attributes.link,
                        onChange: function(value) {
                            props.setAttributes({link: value});
                        },
                    })
                ),
                el('div', {}, __('NPC Character Name', 'story-builder'))
            );
        },
        save: function() {
            return null; // Render in PHP
        },
    });

}(
    window.wp.blocks,
    window.wp.element,
    window.wp.components,
    window.wp.blockEditor
));