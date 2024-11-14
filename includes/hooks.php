<?php
/**
 * Filter to modify the message displayed when the user reaches the end of a storyline.
 *
 * @param string $message The default message.
 * @return string The modified message.
 */
function iasb_end_of_storyline_message($message) {
    return $message;
}
add_filter('HOOK_FILTER__iasb_end_of_storyline_message', 'iasb_end_of_storyline_message');


// Function to display breadcrumb navigation
function iasb_display_breadcrumbs($post_id) {
    $storylines = wp_get_post_terms($post_id, 'storyline');
    $universes = wp_get_post_terms($post_id, 'parallel_universe');
    $season = get_post_meta($post_id, '_iasb_story_builder_season', true);
    $episode = get_post_meta($post_id, '_iasb_story_builder_episode', true);

    echo '<nav class="fc-breadcrumbs">';
    echo 'Current location: <a href="' . get_post_type_archive_link('story_builder') . '">' . __('Stories', 'story-builder') . '</a> &raquo; ';
    if (!empty($storylines)) {
        $storyline = $storylines[0];
        echo '<a href="' . get_term_link($storyline) . '">' . esc_html($storyline->name) . '</a> &raquo; ';
    }
    if (!empty($universes)) {
        $universe = $universes[0];
        echo esc_html($universe->name) . ' &raquo; ';
    }
    echo sprintf(__('Season %s', 'story-builder'), '<a href="' . esc_url(add_query_arg('season', $season, get_post_type_archive_link('story_builder'))) . '">' . esc_html($season) . '</a>') . ' &raquo; ';
    echo sprintf(__('Episode %s', 'story-builder'),  esc_html($episode) );
    echo '</nav>';
}
add_action('HOOK_ACTION__iasb_breadcrumbs', 'iasb_display_breadcrumbs', 10, 1);

// Function to display a link to return to the previous universe
function iasb_return_to_previous_universe($post_id) {
    $current_universe_id = wp_get_post_terms($post_id, 'parallel_universe')[0]->term_id;
    $user_id = get_current_user_id();

    // Provide a link back to previous universe if available
    if (isset($_COOKIE['iasb_previous_universe'])) {
        $previous_universe_id = sanitize_text_field($_COOKIE['iasb_previous_universe']);
        if ($previous_universe_id !== $current_universe_id) {
            $progress = get_user_meta($user_id, 'story_builder_progress', true) ?: [];
            if ($progress && isset($progress[$previous_universe_id])) {
                $previous_story_id = $progress[$previous_universe_id]['story_id'];
                $universe_name = ($previous_universe_id === 'default_universe') ? __('Default Universe', 'story-builder') : get_term($previous_universe_id, 'parallel_universe')->name;
                echo '<div class="return-to-previous-universe">';
                echo '<a href="' . get_permalink($previous_story_id) . '">' . sprintf(__('Return to your place in %s', 'story-builder'), esc_html($universe_name)) . '</a>';
                echo '</div>';
            }
        }
    }
}
add_action('HOOK_ACTION__iasb_return_to_previous_universe', 'iasb_return_to_previous_universe', 10, 1);

// Function to display the user's progress per universe
function iasb_display_user_progress($user_id) {
    $progress = get_user_meta($user_id, 'story_builder_progress', true);
    if ($progress && is_array($progress)) {
        echo '<div class="user-progress">';
        echo '<p>' . __('Your progress:', 'story-builder') . '</p>';
        echo '<ul>';
        foreach ($progress as $universe_id => $data) {
            $universe_name = ($universe_id === 'default_universe') ? __('Default Universe', 'story-builder') : (get_term($universe_id, 'parallel_universe') ? get_term($universe_id, 'parallel_universe')->name : __('Unknown Universe', 'story-builder'));
            if (is_array($data) && isset($data['story_id'])) {
                $story_id = $data['story_id'];
                $parent_episode_ids = get_post_meta($story_id, '_iasb_parent_episode', false);
                $parent_episode = !empty($parent_episode_ids) ? get_post($parent_episode_ids[0]) : null;
                
                echo '<li>' . esc_html($universe_name) . ': <a href="' . get_permalink($story_id) . '">' . get_the_title($story_id) . '</a>';
                //echo '<li><a href="' . get_permalink($story_id) . '">' . get_the_title($story_id) . '</a>';
                
                if ($parent_episode) {
                    echo ' (Parent: <a href="' . get_permalink($parent_episode->ID) . '">' . get_the_title($parent_episode->ID) . '</a>)';
                }
                echo '</li>';
            }
        }
        echo '</ul>';
        echo '</div>';
    }
}
add_action('HOOK_ACTION__iasb_display_user_progress', 'iasb_display_user_progress', 10, 2);


// Function to display the "Next Episode" link considering seasons and next season
function iasb_render_next_episode_link($post_id) {
    // Get the current episode number, season, and storyline
    $current_episode = get_post_meta($post_id, '_iasb_story_builder_episode', true);
    $current_season = get_post_meta($post_id, '_iasb_story_builder_season', true);
    $current_storyline_terms = wp_get_post_terms($post_id, 'storyline', array('fields' => 'ids'));
    $current_storyline_id = !empty($current_storyline_terms) ? $current_storyline_terms[0] : null;

    // Ensure the current episode number and storyline are set
    if (!$current_episode || !$current_storyline_id) {
        return;
    }

    // Build the meta query to find the next episode in the same storyline and season
    $meta_query = array(
        array(
            'key'     => '_iasb_story_builder_episode',
            'value'   => $current_episode,
            'compare' => '>',
            'type'    => 'NUMERIC',
        ),
    );

    // Build the tax query to filter by storyline
    $tax_query = array(
        array(
            'taxonomy' => 'storyline',
            'field'    => 'term_id',
            'terms'    => $current_storyline_id,
        ),
    );

    // If season is set, include it in the meta query
    if ($current_season) {
        $meta_query[] = array(
            'key'     => '_iasb_story_builder_season',
            'value'   => $current_season,
            'compare' => '=',
            'type'    => 'NUMERIC',
        );
    }

    // Arguments to find the next episode
    $args = array(
        'post_type'      => 'story_builder',
        'posts_per_page' => 1,
        'post__not_in'   => array($post_id),
        'meta_query'     => $meta_query,
        'tax_query'      => $tax_query,
        'meta_key'       => '_iasb_story_builder_episode',
        'orderby'        => 'meta_value_num',
        'order'          => 'ASC',
        'post_status'    => 'publish',
    );

    $query = new WP_Query($args);

    if ($query->have_posts()) {
        // Next episode found
        $next_post = $query->posts[0];
        echo '<div class="next-episode-link">';
        echo '<a href="' . get_permalink($next_post->ID) . '">' . __('Next Episode: ', 'story-builder') . esc_html($next_post->post_title) . '</a>';
        echo '</div>';
    } else {
        // No next episode, check if there's a next season
        if ($current_season) {
            // Find Episode 1 of the next season within the same storyline
            $next_season = iasb_get_next_season_number($current_season, $current_storyline_id);

            if ($next_season) {
                // Find Episode 1 of the next season
                $args = array(
                    'post_type'      => 'story_builder',
                    'posts_per_page' => 1,
                    'meta_query'     => array(
                        array(
                            'key'     => '_iasb_story_builder_season',
                            'value'   => $next_season,
                            'compare' => '=',
                            'type'    => 'NUMERIC',
                        ),
                        array(
                            'key'     => '_iasb_story_builder_episode',
                            'value'   => 1,
                            'compare' => '=',
                            'type'    => 'NUMERIC',
                        ),
                    ),
                    'tax_query'      => $tax_query,
                    'post_status'    => 'publish',
                );

                $next_season_query = new WP_Query($args);

                if ($next_season_query->have_posts()) {
                    // Episode 1 of the next season found
                    $next_post = $next_season_query->posts[0];
                    echo '<div class="next-episode-link">';
                    echo '<a href="' . get_permalink($next_post->ID) . '">' . sprintf(__('Next Season - Episode 1: %s', 'story-builder'), esc_html($next_post->post_title)) . '</a>';
                    echo '</div>';
                } else {
                    // No Episode 1 found in the next season
                    echo '<div class="no-more-episodes">';
                    echo '<p>' . apply_filters('HOOK_FILTER__iasb_end_of_storyline_message', __('You have reached the end of this season.', 'story-builder')) . '</p>';                    echo '</div>';
                }
                wp_reset_postdata();
            } else {
                // No next season found
                echo '<div class="no-more-episodes">';
                echo '<p>' . apply_filters('HOOK_FILTER__iasb_end_of_storyline_message', __('You have reached the end of this storyline.', 'story-builder')) . '</p>';
                echo '</div>';
            }
        } else {
            // No seasons used, end of storyline
            echo '<div class="no-more-episodes">';
            echo '<p>' . apply_filters('HOOK_FILTER__iasb_end_of_storyline_message',  __('You have reached the end of this path.', 'story-builder') . '</p>');
            echo '</div>';
        }
    }

    wp_reset_postdata();
}
add_action('HOOK_ACTION_iasb_render_next_episode_link', 'iasb_render_next_episode_link', 10, 1);

// Function to render the Child Episode Buttons on the front end
function iasb_render_child_episodes($post_id) {
    $user_id = get_current_user_id();
    $character_id = get_user_meta($user_id, 'iasb_character_profile_id', true);
    $state_manager = new IASB_State_Manager($user_id, $post_id, $character_id);

    // Query for episodes where '_iasb_parent_episode' meta field equals the current post ID
    $child_episodes = new WP_Query(array(
        'post_type'      => 'story_builder',
        'posts_per_page' => -1,
        'post_status'    => 'publish',
        'meta_key'       => '_iasb_story_builder_episode',
        'meta_query'     => array(
            array(
                'key'     => '_iasb_parent_episode',
                'value'   => $post_id,
                'compare' => '=',
                'type'    => 'NUMERIC',
            ),
        ),
        'orderby'        => 'meta_value_num',
        'order'          => 'ASC',
    ));

    if ($child_episodes->have_posts()) {
        echo '<div class="story-choices">';
        echo '<h3 class="choices-heading">' . __('What do you do next?', 'story-builder') . '</h3>';
        echo '<ul class="choices-list">';
        while ($child_episodes->have_posts()) {
            $child_episodes->the_post();
            $episode_id = get_the_ID();
            $episode_title = get_the_title();
            
            // Check if this path is available based on the current state
            if ($state_manager->check_path_availability($episode_id)) {
                echo '<li class="choice-item"><a href="' . get_permalink() . '" data-story-id="' . esc_attr($episode_id) . '" class="choice-link">' . esc_html($episode_title) . '</a></li>';
            }
        }
        echo '</ul>';
        echo '</div>';
    } else {
       do_action('HOOK_ACTION_iasb_render_next_episode_link', $post_id);
    }
    wp_reset_postdata();
}
add_action('HOOK_ACTION_iasb_render_child_episodes', 'iasb_render_child_episodes', 10, 1);



// Function to display universes and allow switching if available front end
function iasb_render_universes($post_id) {
    // Get all universes
    $all_universes = get_terms('parallel_universe', array('hide_empty' => false));

    // Get universes associated with the current post
    $current_universes = wp_get_post_terms($post_id, 'parallel_universe');
    // error_log("Current universes: " . print_r($current_universes, true));
    
    $current_universe_id = !empty($current_universes) ? $current_universes[0]->term_id : 'default_universe';

    // Ensure there are universes to work with
    if ($all_universes && !is_wp_error($all_universes)) {
        // Get current season, episode, and storyline
        $current_season = get_post_meta($post_id, '_iasb_story_builder_season', true);
        $current_episode = get_post_meta($post_id, '_iasb_story_builder_episode', true);
        $current_storyline_terms = wp_get_post_terms($post_id, 'storyline', array('fields' => 'ids'));
        $current_storyline_id = !empty($current_storyline_terms) ? $current_storyline_terms[0] : null;
        
        // error_log("Current storyline: $current_storyline_id, Season: $current_season, Episode: $current_episode");

        // Proceed only if storyline, season, and episode are set
        if ($current_storyline_id && $current_season && $current_episode) {
            // Get IDs of current universes
            $current_universe_ids = wp_list_pluck($current_universes, 'term_id');

            $alternate_universes = array();

            foreach ($all_universes as $universe) {
                // Skip the current universes because we only want to find alternate universes
                if (in_array($universe->term_id, $current_universe_ids)) {
                    continue;
                }

                // Build the query to find the alternate post
                $args = array(
                    'post_type'      => 'story_builder',
                    'posts_per_page' => 1,
                    'post_status'    => 'publish',
                    'tax_query'      => array(
                        'relation' => 'AND',
                        array(
                            'taxonomy' => 'parallel_universe',
                            'field'    => 'term_id',
                            'terms'    => $universe->term_id,
                        ),
                        array(
                            'taxonomy' => 'storyline',
                            'field'    => 'term_id',
                            'terms'    => $current_storyline_id,
                        ),
                    ),
                    'meta_query'     => array(
                        array(
                            'key'     => '_iasb_story_builder_season',
                            'value'   => $current_season,
                            'compare' => '=',
                            'type'    => 'NUMERIC',
                        ),
                        array(
                            'key'     => '_iasb_story_builder_episode',
                            'value'   => $current_episode,
                            'compare' => '=',
                            'type'    => 'NUMERIC',
                        ),
                    ),
                );

                $query = new WP_Query($args);
                if ($query->have_posts()) {
                    $alternate_post = $query->posts[0];
                    $alternate_universes[] = array(
                        'name' => $universe->name,
                        'link' => get_permalink($alternate_post->ID),
                        'description' => $universe->description, // Description of the universe
                    );
                }
                wp_reset_postdata();
            }
            // Only display the section if alternate universes are found
            if (!empty($alternate_universes)) {
                echo '<div class="parallel-universes">';
                echo '<h3>' . __('Switch Universe:', 'story-builder') . '</h3>';
                echo '<p>' . __('Explore alternate versions of this episode in different universes.', 'story-builder') . '</p>';
                echo '<ul>';

                foreach ($alternate_universes as $alternate) {
                    $alternate_link = add_query_arg('from_universe', $current_universe_id, $alternate['link']);
                    $universe_description = !empty($alternate['description']) ? esc_attr($alternate['description']) : '';
                    $tooltip = !empty($alternate['description']) ? esc_attr($alternate['description']) : '';
                    echo '<li><a href="' . esc_url($alternate_link) . '" title="' . $tooltip . '">' . esc_html($alternate['name']) . '</a></li>';
                }

                echo '</ul>';
                echo '</div>';
            }
        }
    }
}
add_action('HOOK_ACTION__iasb_render_universes', 'iasb_render_universes', 10, 2);

// Entities Hooks
// Function to display the characters in a story
function iasb_display_story_characters($post_id) {
    $characters = get_post_meta($post_id, 'iasb_story_characters', true);
    if (!empty($characters)) {
        echo '<h3>' . __('Characters in this story:', 'story-builder') . '</h3>';
        echo '<ul>';
        foreach ($characters as $character_id) {
            echo '<li><a href="' . get_permalink($character_id) . '">' . get_the_title($character_id) . '</a></li>';
        }
        echo '</ul>';
    }
}
add_action('HOOK_ACTION__iasb_display_story_characters', 'iasb_display_story_characters', 10, 1);

// Function to display the locations in a story
function iasb_display_story_locations($post_id) {
    $locations = get_post_meta($post_id, 'iasb_story_locations', true);
    if (!empty($locations)) {
        echo '<h3>' . __('Locations in this story:', 'story-builder') . '</h3>';
        echo '<ul>';
        foreach ($locations as $location_id) {
            echo '<li><a href="' . get_permalink($location_id) . '">' . get_the_title($location_id) . '</a></li>';
        }
        echo '</ul>';
    }
}
add_action('HOOK_ACTION__iasb_display_story_locations', 'iasb_display_story_locations', 10, 1);

// Function to display the vehicles in a story
function iasb_display_story_vehicles($post_id) {
    $vehicles = get_post_meta($post_id, 'iasb_story_vehicles', true);
    if (!empty($vehicles)) {
        echo '<h3>' . __('Vehicles in this story:', 'story-builder') . '</h3>';
        echo '<ul>';
        foreach ($vehicles as $vehicle_id) {
            echo '<li><a href="' . get_permalink($vehicle_id) . '">' . get_the_title($vehicle_id) . '</a></li>';
        }
        echo '</ul>';
    }
}
add_action('HOOK_ACTION__iasb_display_story_vehicles', 'iasb_display_story_vehicles', 10, 1);

// Function to display the weapons in a story
function iasb_display_story_weapons($post_id) {
    $weapons = get_post_meta($post_id, 'iasb_story_weapons', true);
    if (!empty($weapons)) {
        echo '<h3>' . __('Weapons in this story:', 'story-builder') . '</h3>';
        echo '<ul>';
        foreach ($weapons as $weapon_id) {
            echo '<li><a href="' . get_permalink($weapon_id) . '">' . get_the_title($weapon_id) . '</a></li>';
        }
        echo '</ul>';
    }
}
add_action('HOOK_ACTION__iasb_display_story_weapons', 'iasb_display_story_weapons', 10, 1);

// Function to display the items in a story
function iasb_display_story_items($post_id) {
    $items = get_post_meta($post_id, 'iasb_story_items', true);
    if (!empty($items)) {
        echo '<h3>' . __('Items in this story:', 'story-builder') . '</h3>';
        echo '<ul>';
        foreach ($items as $item_id) {
            echo '<li><a href="' . get_permalink($item_id) . '">' . get_the_title($item_id) . '</a></li>';
        }
        echo '</ul>';
    }
}
add_action('HOOK_ACTION__iasb_display_story_items', 'iasb_display_story_items', 10, 1);

// Function to display the organizations in a story
function iasb_display_story_organizations($post_id) {
    $organizations = get_post_meta($post_id, 'iasb_story_organizations', true);
    if (!empty($organizations)) {
        echo '<h3>' . __('Organizations in this story:', 'story-builder') . '</h3>';
        echo '<ul>';
        foreach ($organizations as $organization_id) {
            echo '<li><a href="' . get_permalink($organization_id) . '">' . get_the_title($organization_id) . '</a></li>';
        }
        echo '</ul>';
    }
}
add_action('HOOK_ACTION__iasb_display_story_organizations', 'iasb_display_story_organizations', 10, 1);

// Function to display the technology in a story
function iasb_display_story_technology($post_id) {
    $technology = get_post_meta($post_id, 'iasb_story_technology', true);
    if (!empty($technology)) {
        echo '<h3>' . __('Technology in this story:', 'story-builder') . '</h3>';
        echo '<ul>';
        foreach ($technology as $technology_id) {
            echo '<li><a href="' . get_permalink($technology_id) . '">' . get_the_title($technology_id) . '</a></li>';
        }
        echo '</ul>';
    }
}
add_action('HOOK_ACTION__iasb_display_story_technology', 'iasb_display_story_technology', 10, 1);

// Function to display the laws in a story
function iasb_display_story_laws($post_id) {
    $laws = get_post_meta($post_id, 'iasb_story_laws', true);
    if (!empty($laws)) {
        echo '<h3>' . __('Laws in this story:', 'story-builder') . '</h3>';
        echo '<ul>';
        foreach ($laws as $law_id) {
            echo '<li><a href="' . get_permalink($law_id) . '">' . get_the_title($law_id) . '</a></li>';
        }
        echo '</ul>';
    }
}
add_action('HOOK_ACTION__iasb_display_story_laws', 'iasb_display_story_laws', 10, 1);

// Function to display the lore in a story
function iasb_display_story_lore($post_id) {
    $lore = get_post_meta($post_id, 'iasb_story_lore', true);
    if (!empty($lore)) {
        echo '<h3>' . __('Lore in this story:', 'story-builder') . '</h3>';
        echo '<ul>';
        foreach ($lore as $lore_id) {
            echo '<li><a href="' . get_permalink($lore_id) . '">' . get_the_title($lore_id) . '</a></li>';
        }
        echo '</ul>';
    }
}
add_action('HOOK_ACTION__iasb_display_story_lore', 'iasb_display_story_lore', 10, 1);
