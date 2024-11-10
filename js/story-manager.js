(function($) {
    var $storyManagerRoot;

    $(document).ready(function() {
        $storyManagerRoot = $('#story-manager-root');

        $('#storyline-selector').on('change', function() {
            var storylineId = $(this).val();
            if (storylineId) {
                loadStories(storylineId);
            } else {
                $storyManagerRoot.empty();
            }
        });
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
        var $list = $('<ol id="story-list" class="sortable"></ol>');
        stories.forEach(function(story) {
            $list.append(renderStoryItem(story));
        });
        $storyManagerRoot.html($list);
        initNestedSortable();
    }

    function renderStoryItem(story) {
        var $item = $('<li id="story_' + story.id + '"></li>');
        $item.append('<div>' + story.title + '</div>');
        if (story.children && story.children.length > 0) {
            var $childList = $('<ol></ol>');
            story.children.forEach(function(child) {
                $childList.append(renderStoryItem(child));
            });
            $item.append($childList);
        }
        return $item;
    }

    function initNestedSortable() {
        $('.sortable').nestedSortable({
            handle: 'div',
            items: 'li',
            toleranceElement: '> div',
            maxLevels: 3,
            isTree: true,
            expandOnHover: 700,
            startCollapsed: false,
            update: function(event, ui) {
                var serialized = $(this).nestedSortable('serialize');
                updateStoryOrder(serialized);
            }
        });
    }

    function updateStoryOrder(serialized) {
        $.ajax({
            url: storyManagerData.ajaxurl,
            type: 'POST',
            data: {
                action: 'update_story_order',
                nonce: storyManagerData.nonce,
                order: serialized
            },
            success: function(response) {
                if (response.success) {
                    console.log('Story order updated successfully');
                } else {
                    console.error('Error updating story order:', response.data);
                }
            }
        });
    }

})(jQuery);