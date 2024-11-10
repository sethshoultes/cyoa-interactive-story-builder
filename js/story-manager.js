(function($) {
    var $storyManagerRoot; // Declare this variable at the top level of the IIFE

    $(document).ready(function() {
        console.log('Document ready');
        console.log('storyManagerData:', storyManagerData);

        $storyManagerRoot = $('#story-manager-root'); // Initialize it here

        $('#storyline-selector').on('change', function() {
            console.log('Storyline selector changed');
            var storylineId = $(this).val();
            if (storylineId) {
                console.log('Loading stories for storyline:', storylineId);
                loadStories(storylineId);
            } else {
                $storyManagerRoot.empty();
            }
        });
    });

    function loadStories(storylineId) {
        if (typeof storyManagerData === 'undefined' || !storyManagerData.ajaxurl) {
            console.error('storyManagerData is not properly defined');
            return;
        }

        $.ajax({
            url: storyManagerData.ajaxurl,
            type: 'POST',
            data: {
                action: 'get_stories_by_storyline',
                nonce: storyManagerData.nonce,
                storyline_id: storylineId
            },
            dataType: 'json',
            success: function(response) {
                if (response && response.success) {
                    renderStories(response.data);
                } else {
                    console.error('Error in AJAX response:', response);
                    $storyManagerRoot.html('<p>Error loading stories. Please try again.</p>');
                }
            },
            error: function(jqXHR, textStatus, errorThrown) {
                console.error('AJAX request failed:', textStatus, errorThrown);
                $storyManagerRoot.html('<p>Failed to load stories. Please check your connection and try again.</p>');
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
        if (story.children && story.children.length > 0) {
            var $childList = $('<ul></ul>');
            story.children.forEach(function(child) {
                $childList.append(renderStoryItem(child));
            });
            $item.append($childList);
        }
        return $item;
    }

    function initSortable() {
        $('#story-list').sortable({
            update: function(event, ui) {
                var newOrder = $(this).sortable('toArray', {attribute: 'data-id'});
                updateStoryOrder(newOrder);
            }
        });
    }

    function updateStoryOrder(newOrder) {
        $.ajax({
            url: storyManagerData.ajaxurl,
            type: 'POST',
            data: {
                action: 'update_story_order',
                nonce: storyManagerData.nonce,
                order: JSON.stringify(newOrder)
            },
            success: function(response) {
                if (response.success) {
                    console.log('Story order updated successfully');
                } else {
                    console.error('Error updating story order:', response.data);
                }
            },
            error: function(xhr, status, error) {
                console.error('AJAX error:', status, error);
            }
        });
    }

})(jQuery);