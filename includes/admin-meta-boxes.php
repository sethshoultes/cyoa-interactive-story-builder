<?php

// Add Meta Boxes for Linking Entities
function iasb_add_story_entity_meta_boxes() {
    add_meta_box(
        'iasb_story_characters',
        __('Characters', 'story-builder'),
        'iasb_render_story_characters_meta_box',
        'story_builder',
        'normal',
        'default'
    );
    add_meta_box(
        'iasb_story_locations',
        __('Locations', 'story-builder'),
        'iasb_render_story_locations_meta_box',
        'story_builder',
        'normal',
        'default'
    );
    add_meta_box(
        'iasb_story_vehicles',
        __('Vehicles', 'story-builder'),
        'iasb_render_story_vehicles_meta_box',
        'story_builder',
        'normal',
        'default'
    );
    add_meta_box(
        'iasb_story_weapons',
        __('Weapons', 'story-builder'),
        'iasb_render_story_weapons_meta_box',
        'story_builder',
        'normal',
        'default'
    );
    add_meta_box(
        'iasb_story_items',
        __('Items', 'story-builder'),
        'iasb_render_story_items_meta_box',
        'story_builder',
        'normal',
        'default'
    );
    add_meta_box(
        'iasb_story_lore',
        __('Lore', 'story-builder'),
        'iasb_render_story_lore_meta_box',
        'story_builder',
        'normal',
        'default'
    );
    add_meta_box(
        'iasb_story_organizations',
        __('Organizations', 'story-builder'),
        'iasb_render_story_organizations_meta_box',
        'story_builder',
        'normal',
        'default'
    );
    add_meta_box(
        'iasb_story_technology',
        __('Technology', 'story-builder'),
        'iasb_render_story_technology_meta_box',
        'story_builder',
        'normal',
        'default'
    );
    add_meta_box(
        'iasb_story_laws',
        __('Laws', 'story-builder'),
        'iasb_render_story_laws_meta_box',
        'story_builder',
        'normal',
        'default'
    );
}
add_action('add_meta_boxes', 'iasb_add_story_entity_meta_boxes');

// Add Meta Box to select child episodes
function iasb_add_child_episodes_meta_box() {
    add_meta_box(
        'iasb_child_episodes', // ID
        __('Child Episodes', 'story-builder'), // Title
        'iasb_render_child_episodes_meta_box', // Callback function
        'story_builder', // Post type
        'side', // Context
        'default' // Priority
    );
}
add_action('add_meta_boxes', 'iasb_add_child_episodes_meta_box');
// Render the Child Episodes Meta Box
function iasb_render_child_episodes_meta_box($post) {
    // Retrieve existing child episodes
    $child_episode_ids = get_post_meta($post->ID, '_iasb_child_episode', false); // $single = false
    if (!is_array($child_episode_ids)) {
        $child_episode_ids = array();
    }

    // Get all story_builder posts except the current one
    $episodes = get_posts(array(
        'post_type'      => 'story_builder',
        'posts_per_page' => -1,
        'post__not_in'   => array($post->ID),
        'post_status'    => 'publish',
        'orderby'        => 'title',
        'order'          => 'ASC',
    ));

    // Organize episodes by storyline (Optional for better usability)
    $episodes_by_storyline = array();

    foreach ($episodes as $episode) {
        $storylines = wp_get_post_terms($episode->ID, 'storyline');
        $storyline_name = (!empty($storylines) && !is_wp_error($storylines)) ? $storylines[0]->name : __('Uncategorized', 'story-builder');

        if (!isset($episodes_by_storyline[$storyline_name])) {
            $episodes_by_storyline[$storyline_name] = array();
        }
        $episodes_by_storyline[$storyline_name][] = $episode;
    }

    // Sort the storylines alphabetically
    ksort($episodes_by_storyline);

    // Render the select field with optgroups
    echo '<select name="iasb_child_episodes[]" multiple style="width:100%; height:150px;">';
    foreach ($episodes_by_storyline as $storyline_name => $storyline_episodes) {
        echo '<optgroup label="' . esc_attr($storyline_name) . '">';
        foreach ($storyline_episodes as $episode) {
            $selected = in_array($episode->ID, $child_episode_ids) ? 'selected' : '';
            $episode_number = get_post_meta($episode->ID, '_iasb_story_builder_episode', true);
            $episode_title = 'Episode ' . $episode_number . ': ' . $episode->post_title;
            echo '<option value="' . esc_attr($episode->ID) . '" ' . $selected . '>' . esc_html($episode_title) . '</option>';
        }
        echo '</optgroup>';
    }
    echo '</select>';
}

// Save the Child Episodes Meta Data
function iasb_save_child_episodes_meta($post_id) {
    // Check for autosave and permissions
    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) return;
    if (!current_user_can('edit_post', $post_id)) return;

    // Get the previously saved child episodes
    $old_child_episode_ids = get_post_meta($post_id, '_iasb_child_episode', false);

    // Get the newly selected child episodes
    $new_child_episode_ids = isset($_POST['iasb_child_episodes']) ? array_map('intval', $_POST['iasb_child_episodes']) : array();

    // Delete existing child episodes meta
    delete_post_meta($post_id, '_iasb_child_episode');

    // Add new child episodes meta
    foreach ($new_child_episode_ids as $child_id) {
        add_post_meta($post_id, '_iasb_child_episode', $child_id);
    }

    // Determine which child episodes were added and which were removed
    $added_child_episodes = array_diff($new_child_episode_ids, $old_child_episode_ids);
    $removed_child_episodes = array_diff($old_child_episode_ids, $new_child_episode_ids);

    // Update the '_iasb_parent_episode' meta on the child posts
    foreach ($added_child_episodes as $child_id) {
        // Add the current post ID as a parent to the child post
        add_post_meta($child_id, '_iasb_parent_episode', $post_id);
    }

    foreach ($removed_child_episodes as $child_id) {
        // Remove the current post ID from the '_iasb_parent_episode' meta of the child post
        // Get all parent IDs of the child post
        $parent_ids = get_post_meta($child_id, '_iasb_parent_episode', false);
        if (($key = array_search($post_id, $parent_ids)) !== false) {
            // Delete the specific meta entry
            delete_post_meta($child_id, '_iasb_parent_episode', $post_id);
        }
    }
}
add_action('save_post', 'iasb_save_child_episodes_meta');

// Render Meta Boxes
function iasb_render_story_characters_meta_box($post) {
    iasb_render_entity_meta_box($post, 'iasb_character', 'iasb_story_characters');
}

function iasb_render_story_locations_meta_box($post) {
    iasb_render_entity_meta_box($post, 'iasb_location', 'iasb_story_locations');
}

function iasb_render_story_vehicles_meta_box($post) {
    iasb_render_entity_meta_box($post, 'iasb_vehicle', 'iasb_story_vehicles');
}

function iasb_render_story_weapons_meta_box($post) {
    iasb_render_entity_meta_box($post, 'iasb_weapon', 'iasb_story_weapons');
}

function iasb_render_story_items_meta_box($post) {
    iasb_render_entity_meta_box($post, 'iasb_item', 'iasb_story_items');
}

function iasb_render_story_lore_meta_box($post) {
    iasb_render_entity_meta_box($post, 'iasb_lore', 'iasb_story_lore');
}

function iasb_render_story_organizations_meta_box($post) {
    iasb_render_entity_meta_box($post, 'iasb_organization', 'iasb_story_organizations');
}

function iasb_render_story_technology_meta_box($post) {
    iasb_render_entity_meta_box($post, 'iasb_technology', 'iasb_story_technology');
}

function iasb_render_story_laws_meta_box($post) {
    iasb_render_entity_meta_box($post, 'iasb_law', 'iasb_story_laws');
}

function iasb_render_entity_meta_box($post, $entity_post_type, $meta_key) {
    // Retrieve existing values
    $selected_entities = get_post_meta($post->ID, $meta_key, true);
    if (!is_array($selected_entities)) {
        $selected_entities = array();
    }

    // Get all entities
    $entities = get_posts(array(
        'post_type'      => $entity_post_type,
        'posts_per_page' => -1,
        'post_status'    => 'publish',
    ));

    // Render checkboxes
    echo '<div class="fc-entity-list">';
    foreach ($entities as $entity) {
        $checked = in_array($entity->ID, $selected_entities) ? 'checked' : '';
        echo '<label>';
        echo '<input type="checkbox" name="' . $meta_key . '[]" value="' . $entity->ID . '" ' . $checked . '> ';
        echo esc_html($entity->post_title);
        echo '</label><br>';
    }
    echo '</div>';
}

// Save Meta Box Data
function iasb_save_story_entities_meta($post_id) {
    // Check autosave, nonce, permissions
    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) return;
    if (!current_user_can('edit_post', $post_id)) return;

    // Save Characters
    if (isset($_POST['iasb_story_characters'])) {
        $characters = array_map('intval', $_POST['iasb_story_characters']);
        update_post_meta($post_id, 'iasb_story_characters', $characters);
    } else {
        delete_post_meta($post_id, 'iasb_story_characters');
    }

    // Save Locations
    if (isset($_POST['iasb_story_locations'])) {
        $locations = array_map('intval', $_POST['iasb_story_locations']);
        update_post_meta($post_id, 'iasb_story_locations', $locations);
    } else {
        delete_post_meta($post_id, 'iasb_story_locations');
    }

    // Save Vehicles
    if (isset($_POST['iasb_story_vehicles'])) {
        $vehicles = array_map('intval', $_POST['iasb_story_vehicles']);
        update_post_meta($post_id, 'iasb_story_vehicles', $vehicles);
    } else {
        delete_post_meta($post_id, 'iasb_story_vehicles');
    }

    // Save Weapons
    if (isset($_POST['iasb_story_weapons'])) {
        $weapons = array_map('intval', $_POST['iasb_story_weapons']);
        update_post_meta($post_id, 'iasb_story_weapons', $weapons);
    } else {
        delete_post_meta($post_id, 'iasb_story_weapons');
    }

    // Save Items
    if (isset($_POST['iasb_story_items'])) {
        $items = array_map('intval', $_POST['iasb_story_items']);
        update_post_meta($post_id, 'iasb_story_items', $items);
    } else {
        delete_post_meta($post_id, 'iasb_story_items');
    }

    // Save Lore
    if (isset($_POST['iasb_story_lore'])) {
        $lore = array_map('intval', $_POST['iasb_story_lore']);
        update_post_meta($post_id, 'iasb_story_lore', $lore);
    } else {
        delete_post_meta($post_id, 'iasb_story_lore');
    }

    // Save Organizations
    if (isset($_POST['iasb_story_organizations'])) {
        $organizations = array_map('intval', $_POST['iasb_story_organizations']);
        update_post_meta($post_id, 'iasb_story_organizations', $organizations);
    } else {
        delete_post_meta($post_id, 'iasb_story_organizations');
    }

    // Save Technology
    if (isset($_POST['iasb_story_technology'])) {
        $technology = array_map('intval', $_POST['iasb_story_technology']);
        update_post_meta($post_id, 'iasb_story_technology', $technology);
    } else {
        delete_post_meta($post_id, 'iasb_story_technology');
    }

    // Save Laws
    if (isset($_POST['iasb_story_laws'])) {
        $laws = array_map('intval', $_POST['iasb_story_laws']);
        update_post_meta($post_id, 'iasb_story_laws', $laws);
    } else {
        delete_post_meta($post_id, 'iasb_story_laws');
    }
}
add_action('save_post', 'iasb_save_story_entities_meta');

// Populate Custom Columns with Taxonomy Terms
// Add Columns
function iasb_add_character_columns($columns) {
    unset($columns['date']);
    $columns['title'] = __('Character Name', 'story-builder');
    $columns['character_type'] = __('Type', 'story-builder');
    $columns['date'] = __('Date', 'story-builder');
    return $columns;
}
add_filter('manage_iasb_character_posts_columns', 'iasb_add_character_columns');

// Populate Columns
function iasb_display_character_columns($column, $post_id) {
    if ($column === 'character_type') {
        $types = wp_get_post_terms($post_id, 'character_type', array('fields' => 'names'));
        echo !empty($types) ? esc_html(implode(', ', $types)) : '—';
    }
}
add_action('manage_iasb_character_posts_custom_column', 'iasb_display_character_columns', 10, 2);

// Add Columns for Locations
function iasb_add_location_columns($columns) {
    unset($columns['date']);
    $columns['title'] = __('Location Name', 'story-builder');
    $columns['location_type'] = __('Type', 'story-builder');
    $columns['date'] = __('Date', 'story-builder');
    return $columns;
}
add_filter('manage_iasb_location_posts_columns', 'iasb_add_location_columns');


// Render the Parent Episodes Meta Box
function iasb_render_parent_episodes_meta_box($post) {
    // Retrieve existing parent episodes
    $parent_episode_ids = get_post_meta($post->ID, '_iasb_parent_episode', false);
    if (!is_array($parent_episode_ids)) {
        $parent_episode_ids = array();
    }

    $episodes = get_posts(array(
        'post_type'      => 'story_builder',
        'posts_per_page' => -1,
        'post__not_in'   => array($post->ID),
        'post_status'    => 'publish',
        'meta_key'       => '_iasb_story_builder_episode',
    ));
    
    // Organize and sort episodes by storyline and episode number
    $episodes_by_storyline = array();
    
    foreach ($episodes as $episode) {
        $storylines = wp_get_post_terms($episode->ID, 'storyline');
        $storyline_name = (!empty($storylines) && !is_wp_error($storylines)) ? $storylines[0]->name : __('Uncategorized', 'story-builder');
    
        $episode_number = get_post_meta($episode->ID, '_iasb_story_builder_episode', true);
    
        if (!isset($episodes_by_storyline[$storyline_name])) {
            $episodes_by_storyline[$storyline_name] = array();
        }
    
        $episodes_by_storyline[$storyline_name][] = array(
            'episode'        => $episode,
            'episode_number' => $episode_number,
        );
    }
    
    // Sort the storylines alphabetically
    ksort($episodes_by_storyline);
    
    // Sort episodes within each storyline
    foreach ($episodes_by_storyline as &$storyline_episodes) {
        usort($storyline_episodes, function($a, $b) {
            $a_num = intval($a['episode_number']);
            $b_num = intval($b['episode_number']);
            if ($a_num == $b_num) {
                return strcmp($a['episode']->post_title, $b['episode']->post_title);
            }
            return ($a_num < $b_num) ? -1 : 1;
        });
    }
    unset($storyline_episodes);
    
    // Render the select field with optgroups
    echo '<select name="iasb_parent_episodes[]" multiple style="width:100%; height:150px;">';
    foreach ($episodes_by_storyline as $storyline_name => $storyline_episodes) {
        echo '<optgroup label="' . esc_attr($storyline_name) . '">';
        foreach ($storyline_episodes as $item) {
            $episode = $item['episode'];
            $episode_number = $item['episode_number'];
            $selected = in_array($episode->ID, $parent_episode_ids) ? 'selected' : '';
            $episode_title = 'Episode ' . $episode_number . ': ' . $episode->post_title;
            echo '<option value="' . esc_attr($episode->ID) . '" ' . $selected . '>' . esc_html($episode_title) . '</option>';
        }
        echo '</optgroup>';
    }
    echo '</select>';

}
// Save the Parent Episodes Meta Data
function iasb_save_parent_episodes_meta($post_id) {
    // Check for autosave and permissions
    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) return;
    if (!current_user_can('edit_post', $post_id)) return;

    // Delete existing parent episodes
    delete_post_meta($post_id, '_iasb_parent_episode');

    // Save the selected parent episodes as separate meta entries
    if (isset($_POST['iasb_parent_episodes'])) {
        $parent_episode_ids = array_map('intval', $_POST['iasb_parent_episodes']);
        foreach ($parent_episode_ids as $parent_id) {
            add_post_meta($post_id, '_iasb_parent_episode', $parent_id);
        }
    }
}
add_action('save_post', 'iasb_save_parent_episodes_meta');

// Customize Universe Meta Box
function iasb_customize_universe_metabox() {
    remove_meta_box('parallel_universediv', 'story_builder', 'side');
    add_meta_box('parallel_universediv', 'Select Parallel Universe', 'iasb_render_universe_metabox', 'story_builder', 'normal', 'high');
}
add_action('add_meta_boxes', 'iasb_customize_universe_metabox');

// Render Universe Meta Box
function iasb_render_universe_metabox($post) {
    wp_nonce_field('iasb_save_universe_data', 'iasb_universe_nonce');
    $terms = wp_get_post_terms($post->ID, 'parallel_universe');
    $all_terms = get_terms('parallel_universe', array('hide_empty' => false));

    echo '<select name="parallel_universe" id="parallel_universe">';
    echo '<option value="">Select a Universe</option>';
    foreach ($all_terms as $term) {
        $selected = ($terms && $term->term_id == $terms[0]->term_id) ? 'selected' : '';
        echo '<option value="' . esc_attr($term->term_id) . '" ' . $selected . '>' . esc_html($term->name) . '</option>';
    }
    echo '</select>';
}

function iasb_save_universe_data($post_id) {
    // Check if our nonce is set and verify it
    if (!isset($_POST['iasb_universe_nonce']) || !wp_verify_nonce($_POST['iasb_universe_nonce'], 'iasb_save_universe_data')) {
        return;
    }

    // If this is an autosave, our form has not been submitted, so we don't want to do anything
    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
        return;
    }

    // Check the user's permissions
    if (!current_user_can('edit_post', $post_id)) {
        return;
    }

    // Make sure that the parallel_universe field is set
    if (!isset($_POST['parallel_universe'])) {
        return;
    }

    // Sanitize the user input
    $universe = sanitize_text_field($_POST['parallel_universe']);

    // Update the parallel universe
    wp_set_object_terms($post_id, intval($universe), 'parallel_universe');
}
add_action('save_post_story_builder', 'iasb_save_universe_data');

// Add Season and Episode Meta Boxes
function iasb_add_story_builder_meta_boxes() {
    add_meta_box(
        'iasb_story_builder_season', // ID
        'Season', // Title
        'iasb_render_story_builder_season_meta_box', // Callback
        'story_builder', // Post type
        'side', // Context
        'core' // Priority
    );

    add_meta_box(
        'iasb_story_builder_episode', // ID
        'Episode', // Title
        'iasb_render_story_builder_episode_meta_box', // Callback
        'story_builder', // Post type
        'side', // Context
        'core' // Priority
    );
}
add_action('add_meta_boxes', 'iasb_add_story_builder_meta_boxes');


// Render the Season meta box
function iasb_render_story_builder_season_meta_box($post) {
    $season = get_post_meta($post->ID, '_iasb_story_builder_season', true);
    ?>
    <input type="number" id="iasb_story_builder_season" name="iasb_story_builder_season" value="<?php echo esc_attr($season); ?>" min="1" />
    <?php
}

// Render the Episode meta box
function iasb_render_story_builder_episode_meta_box($post) {
    $episode = get_post_meta($post->ID, '_iasb_story_builder_episode', true);
    ?>
    <input type="number" id="iasb_story_builder_episode" name="iasb_story_builder_episode" value="<?php echo esc_attr($episode); ?>" min="1" />
    <?php
}

// Save Season and Episode Meta
function iasb_save_story_builder_meta($post_id) {
    // Check if this is an autosave or the nonce is not verified.
    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) return;
    if (!isset($_POST['iasb_story_builder_season']) || !isset($_POST['iasb_story_builder_episode'])) return;

    // Sanitize and save season
    $season = sanitize_text_field($_POST['iasb_story_builder_season']);
    update_post_meta($post_id, '_iasb_story_builder_season', $season);

    // Sanitize and save episode
    $episode = sanitize_text_field($_POST['iasb_story_builder_episode']);
    update_post_meta($post_id, '_iasb_story_builder_episode', $episode);
}
add_action('save_post', 'iasb_save_story_builder_meta');


// Add Season and Episode Columns to the Story List
function iasb_add_season_episode_columns($columns) {
    // Remove unwanted columns
    unset($columns['comments']); // Remove the comments column if desired
    unset($columns['date']); // Remove the date column to reposition it

    // Define new columns in desired order
    $new_columns = array(
        'cb'             => $columns['cb'],
        'title'          => $columns['title'],
        'season_number'  => __('Season', 'story-builder'),
        'episode_number' => __('Episode', 'story-builder'),
        'child_episodes' => __('Child Episodes', 'story-builder'),// Add Child Episodes Column
        'author'         => $columns['author'],
        'date'           => $columns['date'], // Re-add date column
    );

    return $new_columns;
}
add_filter('manage_story_builder_posts_columns', 'iasb_add_season_episode_columns');

// Populate Season and Episode Columns with Meta Data
function iasb_display_season_episode_columns($column, $post_id) {
    if ($column === 'season_number') {
        $season = get_post_meta($post_id, '_iasb_story_builder_season', true);
        echo esc_html($season);
    }
    if ($column === 'episode_number') {
        $episode = get_post_meta($post_id, '_iasb_story_builder_episode', true);
        echo esc_html($episode);
    }
    if ($column === 'child_episodes') {
        $child_episode_ids = get_post_meta($post_id, '_iasb_child_episode', false);
        if (!empty($child_episode_ids)) {
            $child_episodes = get_posts(array(
                'post_type'      => 'story_builder',
                'post__in'       => $child_episode_ids,
                'posts_per_page' => -1,
                'post_status'    => 'publish',
            ));
            if ($child_episodes) {
                $titles = wp_list_pluck($child_episodes, 'post_title');
                echo esc_html(implode(', ', $titles));
            } else {
                echo '—';
            }
        } else {
            echo '—';
        }
    }

}
add_action('manage_story_builder_posts_custom_column', 'iasb_display_season_episode_columns', 10, 2);

// Add Storyline Column to the Story List
function iasb_add_storyline_column($columns) {
    $columns['storyline'] = __('Storyline', 'story-builder');
    return $columns;
}
add_filter('manage_story_builder_posts_columns', 'iasb_add_storyline_column');

// Populate Storyline Column with Data
function iasb_display_storyline_column($column, $post_id) {
    if ($column === 'storyline') {
        $storylines = wp_get_post_terms($post_id, 'storyline', array('fields' => 'names'));
        echo !empty($storylines) ? esc_html(implode(', ', $storylines)) : '—';
    }
}
add_action('manage_story_builder_posts_custom_column', 'iasb_display_storyline_column', 10, 2);

// Add Parallel Universe Column to the Story List
function iasb_add_parallel_universe_column($columns) {
    $columns['parallel_universe'] = __('Parallel Universe', 'story-builder');
    return $columns;
}
add_filter('manage_story_builder_posts_columns', 'iasb_add_parallel_universe_column');

// Populate Parallel Universe Column with Data
function iasb_display_parallel_universe_column($column, $post_id) {
    if ($column === 'parallel_universe') {
        $universes = wp_get_post_terms($post_id, 'parallel_universe', array('fields' => 'names'));
        echo !empty($universes) ? esc_html(implode(', ', $universes)) : '—';
    }
}
add_action('manage_story_builder_posts_custom_column', 'iasb_display_parallel_universe_column', 10, 2);

// Make Season and Episode Columns Sortable
function iasb_make_season_episode_columns_sortable($columns) {
    $columns['season_number'] = 'season_number';
    $columns['episode_number'] = 'episode_number';
    $columns['parallel_universe'] = 'parallel_universe';
    $columns['storyline'] = 'storyline';
    return $columns;
}
add_filter('manage_edit-story_builder_sortable_columns', 'iasb_make_season_episode_columns_sortable');

// Set Sorting Parameters
function iasb_season_episode_column_orderby($query) {
    if (!is_admin()) {
        return;
    }

    $orderby = $query->get('orderby');

    if ('season_number' === $orderby) {
        $query->set('meta_key', '_iasb_story_builder_season');
        $query->set('orderby', 'meta_value_num');
    }

    if ('episode_number' === $orderby) {
        $query->set('meta_key', '_iasb_story_builder_episode');
        $query->set('orderby', 'meta_value_num');
    }
}
add_action('pre_get_posts', 'iasb_season_episode_column_orderby');

// Add Filter Dropdowns for Storyline and Parallel Universe
function iasb_add_storyline_universe_filters() {
    global $typenow;
    if ($typenow === 'story_builder') {
        $storyline = isset($_GET['storyline']) ? $_GET['storyline'] : '';
        $universe = isset($_GET['parallel_universe']) ? $_GET['parallel_universe'] : '';
        $storyline_taxonomy = get_taxonomy('storyline');
        $universe_taxonomy = get_taxonomy('parallel_universe');
        wp_dropdown_categories(array(
            'show_option_all' => $storyline_taxonomy->label,
            'taxonomy'        => 'storyline',
            'name'            => 'storyline',
            'orderby'         => 'name',
            'selected'        => $storyline,
            'show_count'      => true,
            'hide_empty'      => true,
        ));
        wp_dropdown_categories(array(
            'show_option_all' => $universe_taxonomy->label,
            'taxonomy'        => 'parallel_universe',
            'name'            => 'parallel_universe',
            'orderby'         => 'name',
            'selected'        => $universe,
            'show_count'      => true,
            'hide_empty'      => true,
        ));
    }
}
add_action('restrict_manage_posts', 'iasb_add_storyline_universe_filters');

// Filter the Query by Storyline and Parallel Universe
function iasb_filter_storyline_universe_query($query) {
    global $pagenow;
    $type = 'story_builder';
    if ($pagenow === 'edit.php' && isset($_GET['post_type']) && $_GET['post_type'] === $type) {
        $storyline = isset($_GET['storyline']) ? $_GET['storyline'] : '';
        $universe = isset($_GET['parallel_universe']) ? $_GET['parallel_universe'] : '';
        if (!empty($storyline)) {
            $query->set('tax_query', array(
                array(
                    'taxonomy' => 'storyline',
                    'field'    => 'id',
                    'terms'    => $storyline,
                ),
            ));
        }
        if (!empty($universe)) {
            $query->set('tax_query', array(
                array(
                    'taxonomy' => 'parallel_universe',
                    'field'    => 'id',
                    'terms'    => $universe,
                ),
            ));
        }
    }
}
add_action('pre_get_posts', 'iasb_filter_storyline_universe_query');

// Add Filter Dropdowns for Character Types
function iasb_add_character_type_filter() {
    global $typenow;
    if ($typenow === 'iasb_character') {
        $character_type = isset($_GET['character_type']) ? $_GET['character_type'] : '';
        $character_type_taxonomy = get_taxonomy('character_type');
        wp_dropdown_categories(array(
            'show_option_all' => $character_type_taxonomy->label,
            'taxonomy'        => 'character_type',
            'name'            => 'character_type',
            'orderby'         => 'name',
            'selected'        => $character_type,
            'show_count'      => true,
            'hide_empty'      => true,
        ));
    }
}
add_action('restrict_manage_posts', 'iasb_add_character_type_filter');

// Filter the Query by Character Type
function iasb_filter_character_type_query($query) {
    global $pagenow;
    $type = 'iasb_character';
    if ($pagenow === 'edit.php' && isset($_GET['post_type']) && $_GET['post_type'] === $type) {
        $character_type = isset($_GET['character_type']) ? $_GET['character_type'] : '';
        if (!empty($character_type)) {
            $query->set('tax_query', array(
                array(
                    'taxonomy' => 'character_type',
                    'field'    => 'id',
                    'terms'    => $character_type,
                ),
            ));
        }
    }
}
add_action('pre_get_posts', 'iasb_filter_character_type_query');

// Add Filter Dropdowns for Location Types
function iasb_add_location_type_filter() {
    global $typenow;
    if ($typenow === 'iasb_location') {
        $location_type = isset($_GET['location_type']) ? $_GET['location_type'] : '';
        $location_type_taxonomy = get_taxonomy('location_type');
        wp_dropdown_categories(array(
            'show_option_all' => $location_type_taxonomy->label,
            'taxonomy'        => 'location_type',
            'name'            => 'location_type',
            'orderby'         => 'name',
            'selected'        => $location_type,
            'show_count'      => true,
            'hide_empty'      => true,
        ));
    }
}
add_action('restrict_manage_posts', 'iasb_add_location_type_filter');


/* Not working yet */


/**
 * Get the parent episodes of a given post
 *
 * @param int $post_id
 * @return array
 */
// Populate Columns for Locations
function iasb_display_location_columns($column, $post_id) {
    if ($column === 'location_type') {
        $types = wp_get_post_terms($post_id, 'location_type', array('fields' => 'names'));
        if (is_wp_error($types)) {
            echo '—';
        } else {
            echo !empty($types) ? esc_html(implode(', ', $types)) : '—';
        }
    }
    return array();
}
add_action('manage_iasb_location_posts_custom_column', 'iasb_display_location_columns', 10, 2);

// Add Meta Box to select multiple parent episodes
function iasb_add_parent_episodes_meta_box() {
    add_meta_box(
        'iasb_parent_episodes', // ID
        __('Parent Episodes', 'story-builder'), // Title
        'iasb_render_parent_episodes_meta_box', // Callback function
        'story_builder', // Post type
        'side', // Context
        'default' // Priority
    );
}
add_action('add_meta_boxes', 'iasb_add_parent_episodes_meta_box');

function iasb_add_state_variables_meta_box() {
   // error_log('iasb_add_state_variables_meta_box function called');
    add_meta_box(
        'iasb_state_variables',
        'State Variables',
        'iasb_render_state_variables_meta_box',
        'story_builder',
        'side',
        'default'
    );
}
add_action('add_meta_boxes', 'iasb_add_state_variables_meta_box');

function iasb_render_state_variables_meta_box($post) {
    wp_nonce_field('iasb_save_state_variables', 'iasb_state_variables_nonce');

    $state_variables = get_post_meta($post->ID, '_iasb_state_variables', true);
    if (!is_array($state_variables)) {
        $state_variables = array();
    }

    echo '<div id="iasb-state-variables">';
    foreach ($state_variables as $index => $variable) {
        echo '<p>';
        echo '<input type="text" name="iasb_state_variable_name[]" value="' . esc_attr($variable['name']) . '" placeholder="Variable Name">';
        echo '<input type="text" name="iasb_state_variable_default[]" value="' . esc_attr($variable['default']) . '" placeholder="Default Value">';
        echo '<button type="button" class="button iasb-remove-variable">Remove</button>';
        echo '</p>';
    }
    echo '</div>';
    echo '<button type="button" id="iasb-add-variable" class="button">Add Variable</button>';

    // Add JavaScript to handle dynamic addition/removal of variables
    ?>
    <script>
    jQuery(document).ready(function($) {
        $('#iasb-add-variable').on('click', function() {
            var html = '<p>' +
                '<input type="text" name="iasb_state_variable_name[]" placeholder="Variable Name">' +
                '<input type="text" name="iasb_state_variable_default[]" placeholder="Default Value">' +
                '<button type="button" class="button iasb-remove-variable">Remove</button>' +
                '</p>';
            $('#iasb-state-variables').append(html);
        });

        $(document).on('click', '.iasb-remove-variable', function() {
            $(this).parent().remove();
        });
    });
    </script>
    <?php
}

function iasb_save_state_variables($post_id) {
    if (!isset($_POST['iasb_state_variables_nonce']) || !wp_verify_nonce($_POST['iasb_state_variables_nonce'], 'iasb_save_state_variables')) {
        return;
    }

    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
        return;
    }

    if (!current_user_can('edit_post', $post_id)) {
        return;
    }

    $state_variables = array();
    if (isset($_POST['iasb_state_variable_name']) && isset($_POST['iasb_state_variable_default'])) {
        $names = $_POST['iasb_state_variable_name'];
        $defaults = $_POST['iasb_state_variable_default'];
        for ($i = 0; $i < count($names); $i++) {
            if (!empty($names[$i])) {
                $state_variables[] = array(
                    'name' => sanitize_text_field($names[$i]),
                    'default' => sanitize_text_field($defaults[$i])
                );
            }
        }
    }

    update_post_meta($post_id, '_iasb_state_variables', $state_variables);
}
add_action('save_post', 'iasb_save_state_variables');