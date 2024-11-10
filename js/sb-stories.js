// js/story-stories.js
//Not sure if this is needed
jQuery(document).ready(function($) {
    $('.branch-link, .ending-link').on('click', function(e) {
        e.preventDefault();
        var storyId = $(this).data('story-id');
        var href = $(this).attr('href');

        $.post(iasb_story_stories.ajax_url, {
            action: 'iasb_update_user_progress',
            story_id: storyId,
            nonce: iasb_story_stories.nonce
        }, function(response) {
            if (response.success) {
                window.location.href = href;
            } else {
                alert(response.data);
            }
        });

    });
});
