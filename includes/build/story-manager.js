(function() {
    var wp = window.wp;
    var useState = wp.element.useState;
    var useEffect = wp.element.useEffect;
    var Button = wp.components.Button;
    var Panel = wp.components.Panel;
    var PanelBody = wp.components.PanelBody;
    var Draggable = wp.components.Draggable;
    var __ = wp.i18n.__;
    var apiFetch = wp.apiFetch;

    function StoryManager() {
        var storiesState = useState([]);
        var stories = storiesState[0];
        var setStories = storiesState[1];
        var loadingState = useState(true);
        var loading = loadingState[0];
        var setLoading = loadingState[1];
        var errorState = useState(null);
        var error = errorState[0];
        var setError = errorState[1];

        useEffect(function() {
            apiFetch({ path: '/wp/v2/story_builder?per_page=100' })
                .then(function(fetchedStories) {
                    setStories(fetchedStories);
                    setLoading(false);
                })
                .catch(function(err) {
                    console.error('Error fetching stories:', err);
                    setError('Failed to fetch stories. Please try again.');
                    setLoading(false);
                });
        }, []);

        function onDragEnd(result) {
            if (!result.destination) return;

            var newStories = Array.from(stories);
            var reorderedStory = newStories.splice(result.source.index, 1)[0];
            newStories.splice(result.destination.index, 0, reorderedStory);

            setStories(newStories);
            // Here you would also update the order in the database
        }

        if (loading) {
            return wp.element.createElement('p', null, 'Loading stories...');
        }

        if (error) {
            return wp.element.createElement('p', null, error);
        }

        return wp.element.createElement(
            'div',
            null,
            stories.map(function(story, index) {
                return wp.element.createElement(
                    Draggable,
                    { key: story.id, onDragEnd: onDragEnd },
                    function(props) {
                        return wp.element.createElement(
                            Panel,
                            null,
                            wp.element.createElement(
                                PanelBody,
                                {
                                    title: story.title && story.title.rendered ? story.title.rendered : 'Untitled',
                                    initialOpen: false,
                                    onDragStart: props.onDraggableStart,
                                    onDragEnd: props.onDraggableEnd
                                },
                                wp.element.createElement('p', { dangerouslySetInnerHTML: { __html: story.excerpt && story.excerpt.rendered ? story.excerpt.rendered : '' } }),
                                wp.element.createElement(
                                    Button,
                                    {
                                        isPrimary: true,
                                        href: '/wp-admin/post.php?post=' + story.id + '&action=edit'
                                    },
                                    __('Edit Story', 'story-builder')
                                )
                            )
                        );
                    }
                );
            })
        );
    }

    wp.domReady(function() {
        var root = document.getElementById('story-manager-root');
        if (root) {
            wp.element.render(wp.element.createElement(StoryManager), root);
        }
    });
})();

jQuery(document).ready(function($) {
    var $storyManagerRoot = $('#story-manager-root');

    $('#storyline-selector').on('change', function() {
        var storylineId = $(this).val();
        if (storylineId) {
            loadStories(storylineId);
        } else {
            $storyManagerRoot.empty();
        }
    });

    function loadStories(storylineId) {
        $.ajax({
            url: storyManagerData.ajaxurl,
            type: 'POST',
            data: {
                action: 'get_stories_by_storyline',
                nonce: storyManagerData.nonce,
                storyline_id: storylineId
            },
            success: function(response) {
                if (response.success) {
                    renderStories(response.data);
                } else {
                    $storyManagerRoot.html('<p>Error loading stories.</p>');
                }
            }
        });
    }

    function renderStories(stories) {
        var $list = $('<ul id="story-list"></ul>');
        stories.forEach(function(story) {
            $list.append(renderStoryItem(story));
        });
        $storyManagerRoot.html($list);
        initSortable();
    }

    function renderStoryItem(story) {
        var $item = $('<li class="story-item" data-id="' + story.id + '"></li>');
        $item.append('<span>' + story.title + '</span>');
        if (story.children.length > 0) {
            var $childList = $('<ul></ul>');
            story.children.forEach(function(child) {
                $childList.append(renderStoryItem(child));
            });
            $item.append($childList);
        }
        return $item;
    }

    function initSortable() {
        $('#story-list, #story-list ul').sortable({
            connectWith: '#story-list, #story-list ul',
            update: function(event, ui) {
                var newOrder = $(this).sortable('toArray', {attribute: 'data-id'});
                updateStoryOrder(newOrder, ui.item.parent().parent().data('id'));
            }
        });
    }

    function updateStoryOrder(newOrder, parentId) {
        $.ajax({
            url: storyManagerData.ajaxurl,
            type: 'POST',
            data: {
                action: 'update_story_order',
                nonce: storyManagerData.nonce,
                order: newOrder,
                parent_id: parentId || 0
            },
            success: function(response) {
                if (!response.success) {
                    alert('Error updating story order.');
                }
            }
        });
    }
});