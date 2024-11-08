
<?php
// Function to save user story progress per universe
function iasb_save_user_story_progress($user_id, $story_id) {
    // Get the universes associated with the story
    $universes = wp_get_post_terms($story_id, 'parallel_universe', array('fields' => 'ids'));
    $universe_id = !empty($universes) ? $universes[0] : 'default_universe';

    // Get existing progress
    $progress = get_user_meta($user_id, 'story_builder_progress', true);
    if (!is_array($progress)) {
        $progress = array();
    }

    // Save progress for the universe
    $progress[$universe_id] = array('story_id' => $story_id);

    // Update user meta
    update_user_meta($user_id, 'story_builder_progress', $progress);

    // Save the episode viewing history
    $viewed_episodes = get_user_meta($user_id, '_iasb_viewed_episodes', true);
    if (!is_array($viewed_episodes)) {
        $viewed_episodes = array();
    }
    if (!in_array($story_id, $viewed_episodes)) {
        $viewed_episodes[] = $story_id;
        update_user_meta($user_id, '_iasb_viewed_episodes', $viewed_episodes);
    }
}
// Function to display the user's progress per universe
function iasb_display_user_progress($user_id, $current_universe_id = null) {
    $progress = get_user_meta($user_id, 'story_builder_progress', true);
    if ($progress && is_array($progress)) {
        echo '<div class="user-progress">';
        if ($current_universe_id !== null && isset($progress[$current_universe_id])) {
            $story_id = $progress[$current_universe_id]['story_id'];
            $parent_episode_ids = get_post_meta($story_id, '_iasb_parent_episode', false);
            $parent_episode = !empty($parent_episode_ids) ? get_post($parent_episode_ids[0]) : null;
            
            echo '<p>' . __('Your progress in this universe:', 'story-builder') . ' <a href="' . get_permalink($story_id) . '">' . get_the_title($story_id) . '</a></p>';
            
            if ($parent_episode) {
                echo '<p>' . __('Parent episode:', 'story-builder') . ' <a href="' . get_permalink($parent_episode->ID) . '">' . get_the_title($parent_episode->ID) . '</a></p>';
            }
        } else {
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
        }
        echo '</div>';
    }
}
// Function to save the user's story progress
function iasb_save_story_progress($user_id, $story_id, $season, $episode) {
    $progress = array(
        'story_id' => $story_id,
        'season' => $season,
        'episode' => $episode,
    );
    update_user_meta($user_id, 'story_builder_progress', $progress);
}

// Add the story name field to the user profile pages
function iasb_add_story_name_field($user) {
    ?>
    <h3><?php _e('Story Name', 'story-builder'); ?></h3>
    <table class="form-table">
        <tr>
            <th><label for="iasb_story_name"><?php _e('Story Name', 'story-builder'); ?></label></th>
            <td>
                <input type="text" name="iasb_story_name" id="iasb_story_name" value="<?php echo esc_attr(get_user_meta($user->ID, 'iasb_story_name', true)); ?>" class="regular-text" placeholder="<?php echo esc_attr($user->display_name); ?>" /><br />
                <span class="description"><?php _e('This name will be used in stories where the [user_story_name] shortcode is present.', 'story-builder'); ?></span>
            </td>
        </tr>
    </table>
    <?php
}
add_action('show_user_profile', 'iasb_add_story_name_field');
add_action('edit_user_profile', 'iasb_add_story_name_field');

// Save the story name field
function iasb_save_story_name_field($user_id) {
    // Check if the current user has permission to edit the user
    if (!current_user_can('edit_user', $user_id)) {
        return false;
    }

    // Update the user meta with the new story name
    if (isset($_POST['iasb_story_name'])) {
        update_user_meta($user_id, 'iasb_story_name', sanitize_text_field($_POST['iasb_story_name']));
    }
}
add_action('personal_options_update', 'iasb_save_story_name_field');
add_action('edit_user_profile_update', 'iasb_save_story_name_field');

// Add a field to the user profile to select a character profile
function iasb_add_character_profile_field($user) {
    // Get all characters
    $characters = get_posts(array(
        'post_type'      => 'iasb_character',
        'posts_per_page' => -1,
        'post_status'    => 'publish',
        'orderby'        => 'title',
        'order'          => 'ASC',
    ));

    // Get the user's current character profile ID
    $selected_character_id = get_user_meta($user->ID, 'iasb_character_profile_id', true);

    ?>
    <h3><?php _e('Character Profile', 'story-builder'); ?></h3>
    <table class="form-table">
        <tr>
            <th><label for="iasb_character_profile_id"><?php _e('Select Character', 'story-builder'); ?></label></th>
            <td>
                <select name="iasb_character_profile_id" id="iasb_character_profile_id">
                    <option value=""><?php _e('— No Character —', 'story-builder'); ?></option>
                    <?php foreach ($characters as $character): ?>
                        <option value="<?php echo esc_attr($character->ID); ?>" <?php selected($selected_character_id, $character->ID); ?>>
                            <?php echo esc_html($character->post_title); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <p class="description"><?php _e('Select a character profile to associate with your account.', 'story-builder'); ?></p>
            </td>
        </tr>
    </table>
    <?php
}
add_action('show_user_profile', 'iasb_add_character_profile_field');
add_action('edit_user_profile', 'iasb_add_character_profile_field');

// Save the character profile selection
function iasb_save_character_profile_field($user_id) {
    if (!current_user_can('edit_user', $user_id)) {
        return false;
    }

    if (isset($_POST['iasb_character_profile_id'])) {
        update_user_meta($user_id, 'iasb_character_profile_id', intval($_POST['iasb_character_profile_id']));
    }
}
add_action('personal_options_update', 'iasb_save_character_profile_field');
add_action('edit_user_profile_update', 'iasb_save_character_profile_field');
