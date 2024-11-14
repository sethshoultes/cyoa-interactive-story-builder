<?php
// templates/single-iasb_story.php
get_header();
?>
<script>
var ajaxurl = "<?php echo admin_url('admin-ajax.php'); ?>";
$(document).ready(function() {
    $('.choice-link').on('click', function(e) {
        e.preventDefault();
        var choiceId = $(this).data('story-id');
        var storyId = <?php echo esc_js(get_the_ID()); ?>;
        var characterId = <?php echo esc_js(get_post_meta(get_the_ID(), 'iasb_character_id', true)); ?>;
        var decisionTime = Date.now() - choiceStartTime;

        $.post(iasb_ajax_object.ajax_url, {
            action: 'iasb_record_metric',
            metric_key: 'decision_time',
            metric_value: decisionTime,
            nonce: iasb_ajax_object.nonce
        });
        var choiceStartTime = Date.now();
        
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'iasb_process_choice',
                choice_id: choiceId,
                story_id: storyId
            },
            success: function(response) {
                if (response.success) {
                    window.location.href = e.target.href;
                } else {
                    alert('An error occurred: ' + response.data);
                }
            }
        });
    });
});
</script>
<?php
if (have_posts()) :
    while (have_posts()) : the_post();

        // Get the current post ID and user ID
        $post_id = get_the_ID();
        $user_id = get_current_user_id();
        $character_id = get_post_meta($post_id, 'iasb_character_id', true);
        $state_manager = new IASB_State_Manager($user_id, $post_id, $character_id);


        // Get the universes associated with the current post
        $current_universes = wp_get_post_terms($post_id, 'parallel_universe', array('fields' => 'ids'));
        $current_universe_id = !empty($current_universes) ? $current_universes[0] : 'default_universe';

        // Save user progress if the user is logged in
        if (is_user_logged_in()) {
            iasb_save_user_story_progress($user_id, $post_id);
            do_action('HOOK_ACTION_iasb_story_completed', $post_id);
        }

        // Check if a universe switch occurred
        if (isset($_GET['from_universe'])) {
            $from_universe_id = sanitize_text_field($_GET['from_universe']);
            // Store in cookie
            setcookie('iasb_previous_universe', $from_universe_id, time() + 3600, COOKIEPATH, COOKIE_DOMAIN);
        }
        
        // Display the story content
        ?>
        <article id="post-<?php the_ID(); ?>" <?php post_class('story_builder'); ?>>
            <h1 class="story-story-title"><?php the_title(); ?></h1>
            <div class="story-story-meta">
                <?php // Display breadcrumb navigation
                    do_action('HOOK_ACTION__iasb_breadcrumbs', $post_id);
                ?>
            </div>
            <?php if (has_post_thumbnail()) : ?>
                <div class="single-story-image">
                    <?php the_post_thumbnail('large', array('alt' => get_the_title())); ?>
                </div>
            <?php endif; ?>

            <div class="story-content">
                <?php $content = get_the_content();
                        $processed_content = $state_manager->process_conditional_content($content);
                        echo apply_filters('the_content', $processed_content);
                ?>
            </div>

            <?php
            // Provide a link back to previous universe if available
            do_action('HOOK_ACTION__iasb_return_to_previous_universe', $post_id);
            
            // Story completed message
            do_action('HOOK_ACTION_iasb_story_completed_message');
            
            // Render child episodes if available
            do_action('HOOK_ACTION_iasb_render_child_episodes', $post_id);

            // Display universes
            do_action('HOOK_ACTION__iasb_render_universes', $post_id);

            // Display the user's progress
            do_action('HOOK_ACTION__iasb_display_user_progress', $user_id);
            ?>
            <div class="story-story-meta">
                <?php // Display breadcrumb navigation
                    do_action('HOOK_ACTION__iasb_breadcrumbs', $post_id);
                ?>
            </div>
            <div class="story-entities">
                <?php
                // Display Characters
                do_action('HOOK_ACTION__iasb_display_story_characters', $post_id);

                // Display Locations
                do_action('HOOK_ACTION__iasb_display_story_locations', $post_id);

                // Display Vehicles
                do_action('HOOK_ACTION__iasb_display_story_vehicles', $post_id);

                // Display Weapons
                do_action('HOOK_ACTION__iasb_display_story_weapons', $post_id);

                // Display Items
                do_action('HOOK_ACTION__iasb_display_story_items', $post_id);

                // Display Lore
                do_action('HOOK_ACTION__iasb_display_story_lore', $post_id);

                // Display Organizations
                do_action('HOOK_ACTION__iasb_display_story_organizations', $post_id);

                // Display Technology
                do_action('HOOK_ACTION__iasb_display_story_technology', $post_id);

                // Display Laws
                do_action('HOOK_ACTION__iasb_display_story_laws', $post_id);
                ?>
            </div>

        </article>

        <?php

    endwhile;
endif;

get_footer();