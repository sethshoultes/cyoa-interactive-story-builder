<?php
// templates/single-iasb_story.php
get_header();

if (have_posts()) :
    while (have_posts()) : the_post();

        // Get the current post ID and user ID
        $post_id = get_the_ID();
        $user_id = get_current_user_id();

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
                <?php the_content(); ?>
            </div>

            <?php

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
                }?>
            </div>

        </article>

        <?php

    endwhile;
endif;

get_footer();
?>
