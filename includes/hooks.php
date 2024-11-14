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