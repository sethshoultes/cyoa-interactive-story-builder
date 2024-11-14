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
                    iasb_display_breadcrumbs($post_id);
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
            $is_ending = get_post_meta( get_the_ID(), '_iasb_is_ending', true );
            if ( $is_ending == '1' ) {
                echo '<div class="ending-message">';
                _e( 'You have reached the end of this path. Thank you for playing!', 'story-builder' );
                echo '</div>';
            }

            // Provide a link back to previous universe if available
            if (isset($_COOKIE['iasb_previous_universe'])) {
                $previous_universe_id = sanitize_text_field($_COOKIE['iasb_previous_universe']);
                if ($previous_universe_id !== $current_universe_id) {
                    $progress = get_user_meta($user_id, 'story_builder_progress', true);
                    if ($progress && isset($progress[$previous_universe_id])) {
                        $previous_story_id = $progress[$previous_universe_id]['story_id'];
                        $universe_name = ($previous_universe_id === 'default_universe') ? __('Default Universe', 'story-builder') : get_term($previous_universe_id, 'parallel_universe')->name;
                        echo '<div class="return-to-previous-universe">';
                        echo '<a href="' . get_permalink($previous_story_id) . '">' . sprintf(__('Return to your place in %s', 'story-builder'), esc_html($universe_name)) . '</a>';
                        echo '</div>';
                    }
                }
            }

            // Render child episodes directly without using get_children()
            iasb_render_child_episodes($post_id);

            // Display universes
            iasb_render_universes($post_id, $user_id);

            // Display the user's progress
            iasb_display_user_progress($user_id);

             // Display Next Episode Link
             //iasb_render_next_episode_link(get_the_ID());

            ?>
            <div class="story-story-meta">
                <?php // Display breadcrumb navigation
                    iasb_display_breadcrumbs($post_id);
                ?>
            </div>
            <div class="story-entities">
                <?php
                // Display Characters
                $characters = get_post_meta($post_id, 'iasb_story_characters', true);
                if (!empty($characters)) {
                    echo '<h3>' . __('Characters in this story:', 'story-builder') . '</h3>';
                    echo '<ul>';
                    foreach ($characters as $character_id) {
                        echo '<li><a href="' . get_permalink($character_id) . '">' . get_the_title($character_id) . '</a></li>';
                    }
                    echo '</ul>';
                }

                // Display Locations
                $locations = get_post_meta($post_id, 'iasb_story_locations', true);
                if (!empty($locations)) {
                    echo '<h3>' . __('Locations in this story:', 'story-builder') . '</h3>';
                    echo '<ul>';
                    foreach ($locations as $location_id) {
                        echo '<li><a href="' . get_permalink($location_id) . '">' . get_the_title($location_id) . '</a></li>';
                    }
                    echo '</ul>';
                }

                // Display Vehicles
                $vehicles = get_post_meta($post_id, 'iasb_story_vehicles', true);
                if (!empty($vehicles)) {
                    echo '<h3>' . __('Vehicles in this story:', 'story-builder') . '</h3>';
                    echo '<ul>';
                    foreach ($vehicles as $vehicle_id) {
                        echo '<li><a href="' . get_permalink($vehicle_id) . '">' . get_the_title($vehicle_id) . '</a></li>';
                    }
                    echo '</ul>';
                }

                // Display Weapons
                $weapons = get_post_meta($post_id, 'iasb_story_weapons', true);
                if (!empty($weapons)) {
                    echo '<h3>' . __('Weapons in this story:', 'story-builder') . '</h3>';
                    echo '<ul>';
                    foreach ($weapons as $weapon_id) {
                        echo '<li><a href="' . get_permalink($weapon_id) . '">' . get_the_title($weapon_id) . '</a></li>';
                    }
                    echo '</ul>';
                }

                // Display Items
                $items = get_post_meta($post_id, 'iasb_story_items', true);
                if (!empty($items)) {
                    echo '<h3>' . __('Items in this story:', 'story-builder') . '</h3>';
                    echo '<ul>';
                    foreach ($items as $item_id) {
                        echo '<li><a href="' . get_permalink($item_id) . '">' . get_the_title($item_id) . '</a></li>';
                    }
                    echo '</ul>';
                }

                // Display Lore
                $lore = get_post_meta($post_id, 'iasb_story_lore', true);
                if (!empty($lore)) {
                    echo '<h3>' . __('Lore in this story:', 'story-builder') . '</h3>';
                    echo '<ul>';
                    foreach ($lore as $lore_id) {
                        echo '<li><a href="' . get_permalink($lore_id) . '">' . get_the_title($lore_id) . '</a></li>';
                    }
                    echo '</ul>';
                }

                // Display Organizations
                $organizations = get_post_meta($post_id, 'iasb_story_organizations', true);
                if (!empty($organizations)) {
                    echo '<h3>' . __('Organizations in this story:', 'story-builder') . '</h3>';
                    echo '<ul>';
                    foreach ($organizations as $organization_id) {
                        echo '<li><a href="' . get_permalink($organization_id) . '">' . get_the_title($organization_id) . '</a></li>';
                    }
                    echo '</ul>';
                }

                // Display Technology
                $technology = get_post_meta($post_id, 'iasb_story_technology', true);
                if (!empty($technology)) {
                    echo '<h3>' . __('Technology in this story:', 'story-builder') . '</h3>';
                    echo '<ul>';
                    foreach ($technology as $technology_id) {
                        echo '<li><a href="' . get_permalink($technology_id) . '">' . get_the_title($technology_id) . '</a></li>';
                    }
                    echo '</ul>';
                }

                // Display Laws
                $laws = get_post_meta($post_id, 'iasb_story_laws', true);
                if (!empty($laws)) {
                    echo '<h3>' . __('Laws in this story:', 'story-builder') . '</h3>';
                    echo '<ul>';
                    foreach ($laws as $law_id) {
                        echo '<li><a href="' . get_permalink($law_id) . '">' . get_the_title($law_id) . '</a></li>';
                    }
                    echo '</ul>';
                }
                ?>
            </div>

        </article>

        <?php

    endwhile;
endif;

get_footer();