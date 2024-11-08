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
                        label: __('Condition', 'story-builder'),
                        value: props.attributes.condition,
                        onChange: function(value) {
                            props.setAttributes({condition: value});
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
                el('div', {className: 'condition-preview'},
                    __('Condition: ', 'story-builder') + props.attributes.condition
                )
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
            return el('div', {className: props.className},
                el(InspectorControls, {},
                    el(SelectControl, {
                        label: __('Content Type', 'story-builder'),
                        value: props.attributes.type,
                        options: [
                            { label: 'Text', value: 'text' },
                            { label: 'Image', value: 'image' },
                            { label: 'Link', value: 'link' },
                        ],
                        onChange: function(value) {
                            props.setAttributes({type: value});
                        },
                    }),
                    el(TextControl, {
                        label: __('ID', 'story-builder'),
                        value: props.attributes.id,
                        onChange: function(value) {
                            props.setAttributes({id: parseInt(value)});
                        },
                    })
                ),
                el('div', {}, __('Dynamic Content Placeholder', 'story-builder'))
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
        edit: function(props) {
            const [storyName, setStoryName] = wp.element.useState('');
        
            wp.element.useEffect(() => {
                wp.apiFetch({
                    path: '/iasb/v1/story-name',
                }).then(result => {
                    setStoryName(result.name);
                });
            }, []);
        
            return el('div', {className: props.className},
                el(TextControl, {
                    label: __('User Story Name', 'story-builder'),
                    value: storyName,
                    onChange: function(value) {
                        setStoryName(value);
                        wp.apiFetch({
                            path: '/iasb/v1/story-name',
                            method: 'POST',
                            data: { name: value },
                        });
                    },
                })
            );
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