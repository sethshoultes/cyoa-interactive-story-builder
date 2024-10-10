<?php
/**
 * Plugin Name: CYOA Interactive Adventure Story Builder
 * Description: A storytelling platform for choose your own adventure style stories. Users can choose their own path through the story. Shortcodes are available to embed stories in posts and pages: [user_story_name].
 * Version: 1.0
 * Author: Seth Shoultes
 * License: GPL2
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Enqueue plugin styles and scripts
function iasb_enqueue_admin_assets($hook) {
    if ('post.php' != $hook && 'post-new.php' != $hook) {
        return;
    }
    wp_enqueue_style('select2-css', 'https://cdn.jsdelivr.net/npm/select2@4.1.0-beta.1/dist/css/select2.min.css');
    wp_enqueue_script('select2-js', 'https://cdn.jsdelivr.net/npm/select2@4.1.0-beta.1/dist/js/select2.min.js', array('jquery'), '4.1.0', true);
    wp_enqueue_script('iasb-admin-js', plugin_dir_url(__FILE__) . 'js/iasb-admin.js', array('jquery', 'select2-js'), null, true);
}
add_action('admin_enqueue_scripts', 'iasb_enqueue_admin_assets');

function iasb_enqueue_d3_js_library($hook) {
    // Check if we're on the Story Manager page
    if ($hook !== 'story_builder_page_story-story-manager') {
        return;
    }

    // Enqueue D3.js for the flowchart
    wp_enqueue_script('d3-js', 'https://d3js.org/d3.v7.min.js', array(), null, true);

    // Enqueue our custom script to handle the D3.js flowchart logic
    wp_enqueue_script('story-story-manager-js', plugin_dir_url(__FILE__) . 'js/story-story-manager.js', array('d3-js'), null, true);

    // Localize script to pass AJAX URL and nonce
    wp_localize_script('story-story-manager-js', 'iasb_ajax_object', array(
        'ajaxurl' => admin_url('admin-ajax.php'),
        'nonce'   => wp_create_nonce('iasb_story_builder_manager_nonce')
    ));

    // Enqueue CSS for styling the tree
    wp_enqueue_style('story-story-manager-css', plugin_dir_url(__FILE__) . 'css/story-story-manager.css');
}
add_action('admin_enqueue_scripts', 'iasb_enqueue_d3_js_library');


// Register Custom Post Type for Stories
function iasb_register_story_stories_cpt() {
    $labels = array(
        'name'               => __('Stories', 'story-builder'),
        'singular_name'      => __('Story', 'story-builder'),
        'menu_name'          => __('Stories', 'story-builder'),
        'name_admin_bar'     => __('Story', 'story-builder'),
        'add_new'            => __('Add New', 'story-builder'),
        'add_new_item'       => __('Add New Story', 'story-builder'),
        'edit_item'          => __('Edit Story', 'story-builder'),
        'new_item'           => __('New Story', 'story-builder'),
        'view_item'          => __('View Story', 'story-builder'),
        'all_items'          => __('All Stories', 'story-builder'),
        'search_items'       => __('Search Stories', 'story-builder'),
        'not_found'          => __('No story stories found.', 'story-builder'),
    );

    $args = array(
        'labels'             => $labels,
        'public'             => true,
        'has_archive'        => true,
        'rewrite'            => array('slug' => 'story-stories'),
        'supports'           => array('title', 'editor', 'author', 'thumbnail', 'comments'),
        'hierarchical'       => false, // disable parent-child relationships
        'capability_type'    => 'post',
        'menu_position'      => 26,
        'menu_icon'          => 'dashicons-book-alt',
        'taxonomies'         => array('story_builder_genre', 'story_builder_mood', 'story_builder_season', 'story_builder_episode'),
        'show_in_rest'       => true, // For Gutenberg compatibility,
        'show_ui'            => true,
        'show_in_menu'       => true,
    );
    
    register_post_type('story_builder', $args);
}
add_action('init', 'iasb_register_story_stories_cpt');

function iasb_register_story_builder_manager_page() {
    add_submenu_page(
        'edit.php?post_type=story_builder',  // Parent slug (under Stories menu)
        'Story Manager',                  // Page title
        'Story Manager',                  // Menu title
        'manage_options',                 // Capability
        'story-story-manager',             // Menu slug
        'iasb_render_story_manager_page'    // Callback function to render the page
    );
}
add_action('admin_menu', 'iasb_register_story_builder_manager_page');

function iasb_render_story_manager_page() {
    ?>
    <div class="wrap">
        <h1><?php _e('Story Manager', 'story-builder'); ?></h1>
        <div id="story-manager-tree"></div> <!-- This is where the D3.js tree will be rendered -->
    </div>
    <?php
}

// Register taxonomies for Stories
function iasb_register_story_builder_taxonomies() {

    // Parallel Universe taxonomy
    register_taxonomy('parallel_universe', 'story_builder', array(
        'hierarchical' => true,
        'labels' => array(
            'name' => 'Parallel Universes',
            'singular_name' => 'Parallel Universe',
            'add_new_item' => 'Add New Universe',
            'edit_item' => 'Edit Universe',
        ),
        'show_ui' => true,
        'show_admin_column' => true,
        'query_var' => true,
        'rewrite' => array('slug' => 'parallel-universe'),
        'hide_empty' => true,
    ));

    // Register the 'Storyline' taxonomy
    register_taxonomy('storyline', 'story_builder', array(
        'hierarchical' => true,
        'labels' => array(
            'name'              => __('Storylines', 'story-builder'),
            'singular_name'     => __('Storyline', 'story-builder'),
            'search_items'      => __('Search Storylines', 'story-builder'),
            'all_items'         => __('All Storylines', 'story-builder'),
            'parent_item'       => __('Parent Storyline', 'story-builder'),
            'parent_item_colon' => __('Parent Storyline:', 'story-builder'),
            'edit_item'         => __('Edit Storyline', 'story-builder'),
            'update_item'       => __('Update Storyline', 'story-builder'),
            'add_new_item'      => __('Add New Storyline', 'story-builder'),
            'new_item_name'     => __('New Storyline Name', 'story-builder'),
            'menu_name'         => __('Storylines', 'story-builder'),
        ),
        'show_ui'           => true,
        'show_admin_column' => true,
        'rewrite'           => array('slug' => 'storyline'),
        'show_in_rest'      => true,
        'hide_empty' => true,
    ));

   
}
add_action('init', 'iasb_register_story_builder_taxonomies');

// Register Custom Post Types for Characters, Locations, Vehicles, and Weapons
function iasb_register_persona_cpts() {
    // Character CPT
    $labels = array(
        'name'               => __('Characters', 'story-builder'),
        'singular_name'      => __('Character', 'story-builder'),
        'menu_name'          => __('Characters', 'story-builder'),
        'add_new_item'       => __('Add New Character', 'story-builder'),
        'edit_item'          => __('Edit Character', 'story-builder'),
        'new_item'           => __('New Character', 'story-builder'),
        'view_item'          => __('View Character', 'story-builder'),
        'all_items'          => __('All Characters', 'story-builder'),
    );

    $args = array(
        'labels'             => $labels,
        'public'             => true,
        'has_archive'        => false,
        'rewrite'            => array('slug' => 'characters'),
        'supports'           => array('title', 'editor', 'thumbnail', 'custom-fields'),
        'menu_position'      => 27,
        'menu_icon'          => 'dashicons-groups',
        'show_in_rest'       => true,
    );
    register_post_type('iasb_character', $args);

    // Repeat similar blocks for Locations, Vehicles, and Weapons
    // Locations CPT
    $labels = array(
        'name'               => __('Locations', 'story-builder'),
        'singular_name'      => __('Location', 'story-builder'),
        'menu_name'          => __('Locations', 'story-builder'),
        'add_new_item'       => __('Add New Location', 'story-builder'),
        'edit_item'          => __('Edit Location', 'story-builder'),
        'new_item'           => __('New Location', 'story-builder'),
        'view_item'          => __('View Location', 'story-builder'),
        'all_items'          => __('All Locations', 'story-builder'),
    );

    $args = array(
        'labels'             => $labels,
        'public'             => true,
        'has_archive'        => false,
        'rewrite'            => array('slug' => 'locations'),
        'supports'           => array('title', 'editor', 'thumbnail', 'custom-fields'),
        'menu_position'      => 28,
        'menu_icon'          => 'dashicons-location',
        'show_in_rest'       => true,
    );
    register_post_type('iasb_location', $args);

    // Vehicles CPT
    $labels = array(
        'name'               => __('Vehicles', 'story-builder'),
        'singular_name'      => __('Vehicle', 'story-builder'),
        'menu_name'          => __('Vehicles', 'story-builder'),
        'add_new_item'       => __('Add New Vehicle', 'story-builder'),
        'edit_item'          => __('Edit Vehicle', 'story-builder'),
        'new_item'           => __('New Vehicle', 'story-builder'),
        'view_item'          => __('View Vehicle', 'story-builder'),
        'all_items'          => __('All Vehicles', 'story-builder'),
    );

    $args = array(
        'labels'             => $labels,
        'public'             => true,
        'has_archive'        => false,
        'rewrite'            => array('slug' => 'vehicles'),
        'supports'           => array('title', 'editor', 'thumbnail', 'custom-fields'),
        'menu_position'      => 29,
        'menu_icon'          => 'dashicons-car',
        'show_in_rest'       => true,
    );
    register_post_type('iasb_vehicle', $args);

    // Weapons CPT
    $labels = array(
        'name'               => __('Weapons', 'story-builder'),
        'singular_name'      => __('Weapon', 'story-builder'),
        'menu_name'          => __('Weapons', 'story-builder'),
        'add_new_item'       => __('Add New Weapon', 'story-builder'),
        'edit_item'          => __('Edit Weapon', 'story-builder'),
        'new_item'           => __('New Weapon', 'story-builder'),
        'view_item'          => __('View Weapon', 'story-builder'),
        'all_items'          => __('All Weapons', 'story-builder'),
    );

    $args = array(
        'labels'             => $labels,
        'public'             => true,
        'has_archive'        => false,
        'rewrite'            => array('slug' => 'weapons'),
        'supports'           => array('title', 'editor', 'thumbnail', 'custom-fields'),
        'menu_position'      => 30,
        'menu_icon'          => 'dashicons-hammer',
        'show_in_rest'       => true,
    );
    register_post_type('iasb_weapon', $args);
}
add_action('init', 'iasb_register_persona_cpts');

// Register Taxonomies for Character Types
function iasb_register_persona_taxonomies() {
    // Character Type Taxonomy
    register_taxonomy('character_type', 'iasb_character', array(
        'hierarchical' => true,
        'labels' => array(
            'name'              => __('Character Types', 'story-builder'),
            'singular_name'     => __('Character Type', 'story-builder'),
            'search_items'      => __('Search Character Types', 'story-builder'),
            'all_items'         => __('All Character Types', 'story-builder'),
            'parent_item'       => __('Parent Character Type', 'story-builder'),
            'parent_item_colon' => __('Parent Character Type:', 'story-builder'),
            'edit_item'         => __('Edit Character Type', 'story-builder'),
            'update_item'       => __('Update Character Type', 'story-builder'),
            'add_new_item'      => __('Add New Character Type', 'story-builder'),
            'new_item_name'     => __('New Character Type Name', 'story-builder'),
            'menu_name'         => __('Character Types', 'story-builder'),
        ),
        'show_ui'           => true,
        'show_admin_column' => true,
        'rewrite'           => array('slug' => 'character-type'),
        'show_in_rest'      => true,
    ));

    // Location Type Taxonomy
    register_taxonomy('location_type', 'iasb_location', array(
        'hierarchical' => true,
        'labels' => array(
            'name'              => __('Location Types', 'story-builder'),
            'singular_name'     => __('Location Type', 'story-builder'),
            'search_items'      => __('Search Location Types', 'story-builder'),
            'all_items'         => __('All Location Types', 'story-builder'),
            'parent_item'       => __('Parent Location Type', 'story-builder'),
            'parent_item_colon' => __('Parent Location Type:', 'story-builder'),
            'edit_item'         => __('Edit Location Type', 'story-builder'),
            'update_item'       => __('Update Location Type', 'story-builder'),
            'add_new_item'      => __('Add New Location Type', 'story-builder'),
            'new_item_name'     => __('New Location Type Name', 'story-builder'),
            'menu_name'         => __('Location Types', 'story-builder'),
        ),
        'show_ui'           => true,
        'show_admin_column' => true,
        'rewrite'           => array('slug' => 'location-type'),
        'show_in_rest'      => true,
    ));

    // Vehicle Type Taxonomy
    register_taxonomy('vehicle_type', 'iasb_vehicle', array(
        'hierarchical' => true,
        'labels' => array(
            'name'              => __('Vehicle Types', 'story-builder'),
            'singular_name'     => __('Vehicle Type', 'story-builder'),
            'search_items'      => __('Search Vehicle Types', 'story-builder'),
            'all_items'         => __('All Vehicle Types', 'story-builder'),
            'parent_item'       => __('Parent Vehicle Type', 'story-builder'),
            'parent_item_colon' => __('Parent Vehicle Type:', 'story-builder'),
            'edit_item'         => __('Edit Vehicle Type', 'story-builder'),
            'update_item'       => __('Update Vehicle Type', 'story-builder'),
            'add_new_item'      => __('Add New Vehicle Type', 'story-builder'),
            'new_item_name'     => __('New Vehicle Type Name', 'story-builder'),
            'menu_name'         => __('Vehicle Types', 'story-builder'),
        ),
        'show_ui'           => true,
        'show_admin_column' => true,
        'rewrite'           => array('slug' => 'vehicle-type'),
        'show_in_rest'      => true,
    ));

    // Weapon Type Taxonomy
    register_taxonomy('weapon_type', 'iasb_weapon', array(
        'hierarchical' => true,
        'labels' => array(
            'name'              => __('Weapon Types', 'story-builder'),
            'singular_name'     => __('Weapon Type', 'story-builder'),
            'search_items'      => __('Search Weapon Types', 'story-builder'),
            'all_items'         => __('All Weapon Types', 'story-builder'),
            'parent_item'       => __('Parent Weapon Type', 'story-builder'),
            'parent_item_colon' => __('Parent Weapon Type:', 'story-builder'),
            'edit_item'         => __('Edit Weapon Type', 'story-builder'),
            'update_item'       => __('Update Weapon Type', 'story-builder'),
            'add_new_item'      => __('Add New Weapon Type', 'story-builder'),
            'new_item_name'     => __('New Weapon Type Name', 'story-builder'),
            'menu_name'         => __('Weapon Types', 'story-builder'),
        ),
        'show_ui'           => true,
        'show_admin_column' => true,
        'rewrite'           => array('slug' => 'weapon-type'),
        'show_in_rest'      => true,
    ));
}
add_action('init', 'iasb_register_persona_taxonomies');

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

// Add Child Episodes Column
function iasb_add_child_episodes_column($columns) {
    $columns['child_episodes'] = __('Child Episodes', 'story-builder');
    return $columns;
}
add_filter('manage_story_builder_posts_columns', 'iasb_add_child_episodes_column');

// Populate Child Episodes Column
function iasb_display_child_episodes_column($column, $post_id) {
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
add_action('manage_story_builder_posts_custom_column', 'iasb_display_child_episodes_column', 10, 2);


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
// Add Parent Episodes Column
function iasb_add_parent_episodes_column($columns) {
    $columns['parent_episodes'] = __('Parent Episodes', 'story-builder');
    return $columns;
}
add_filter('manage_story_builder_posts_columns', 'iasb_add_parent_episodes_column');
// Populate Parent Episodes Column
function iasb_display_parent_episodes_column($column, $post_id) {
    if ($column === 'parent_episodes') {
        $parent_episodes = iasb_get_parent_episodes($post_id);
        if ($parent_episodes) {
            $titles = wp_list_pluck($parent_episodes, 'post_title');
            echo esc_html(implode(', ', $titles));
        } else {
            echo '—';
        }
    }
}
add_action('manage_story_builder_posts_custom_column', 'iasb_display_parent_episodes_column', 10, 2);

// Function to get parent episodes of a given episode
function iasb_get_parent_episodes($post_id) {
    $parent_episode_ids = get_post_meta($post_id, '_iasb_parent_episode', false); // Note: false to get all values
    if (!is_array($parent_episode_ids)) {
        $parent_episode_ids = array();
    }

    // Get the parent episodes
    $parent_episodes = get_posts(array(
        'post_type'      => 'story_builder',
        'post__in'       => $parent_episode_ids,
        'posts_per_page' => -1,
        'post_status'    => 'publish',
    ));

    return $parent_episodes;
}
// Render Story Branches Meta Box
function iasb_render_story_branch_metabox($post) {
    $terms = get_terms(array('taxonomy' => 'story_branch', 'hide_empty' => false));
    $selected_terms = wp_get_post_terms($post->ID, 'story_branch', array('fields' => 'ids'));
    echo '<select name="tax_input[story_branch][]" multiple style="width:100%;">';
    foreach ($terms as $term) {
        $selected = in_array($term->term_id, $selected_terms) ? 'selected' : '';
        echo '<option value="' . esc_attr($term->term_id) . '" ' . $selected . '>' . esc_html($term->name) . '</option>';
    }
    echo '</select>';
}

// Render Alternate Endings Meta Box
function iasb_render_alternate_ending_metabox($post) {
    $terms = get_terms(array('taxonomy' => 'alternate_ending', 'hide_empty' => false));
    $selected_terms = wp_get_post_terms($post->ID, 'alternate_ending', array('fields' => 'ids'));
    echo '<select name="tax_input[alternate_ending][]" multiple style="width:100%;">';
    foreach ($terms as $term) {
        $selected = in_array($term->term_id, $selected_terms) ? 'selected' : '';
        echo '<option value="' . esc_attr($term->term_id) . '" ' . $selected . '>' . esc_html($term->name) . '</option>';
    }
    echo '</select>';
}

// Customize Universe Meta Box
function iasb_customize_universe_metabox() {
    remove_meta_box('parallel_universediv', 'story_builder', 'side');
    add_meta_box('parallel_universediv', 'Select Parallel Universe', 'iasb_render_universe_metabox', 'story_builder', 'normal', 'high');
}
add_action('add_meta_boxes', 'iasb_customize_universe_metabox');

// Render Universe Meta Box
function iasb_render_universe_metabox($post) {
    wp_nonce_field(basename(__FILE__), 'iasb_story_builder_nonce');
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

// Save Taxonomies
function iasb_save_story_builder_taxonomies($post_id) {
    // Check if nonce is valid
    if (!isset($_POST['iasb_story_builder_nonce']) || !wp_verify_nonce($_POST['iasb_story_builder_nonce'], basename(__FILE__))) {
        return $post_id;
    }

    // Save selected parallel universe (custom dropdown)
    if (isset($_POST['parallel_universe'])) {
        $universe = intval($_POST['parallel_universe']);
        wp_set_post_terms($post_id, array($universe), 'parallel_universe');
    }

}
add_action('save_post', 'iasb_save_story_builder_taxonomies');


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

// Add Filter Dropdown
function iasb_restrict_character_by_type($post_type, $which) {
    if ('iasb_character' !== $post_type)
        return;

    $taxonomy = 'character_type';
    wp_dropdown_categories(array(
        'show_option_all' => __('Show All Types', 'story-builder'),
        'taxonomy'        => $taxonomy,
        'name'            => $taxonomy,
        'orderby'         => 'name',
        'selected'        => isset($_GET[$taxonomy]) ? $_GET[$taxonomy] : '',
        'hierarchical'    => true,
        'show_count'      => true,
        'hide_empty'      => true,
    ));
}
add_action('restrict_manage_posts', 'iasb_restrict_character_by_type', 10, 2);

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

// Enqueue styles and scripts
function iasb_story_stories_enqueue_assets() {
    wp_enqueue_style('sb-stories-css', plugin_dir_url(__FILE__) . 'css/sb-stories.css', array(), '1.0.0', 'all');
    wp_enqueue_script('sb-stories-js', plugin_dir_url(__FILE__) . 'js/sb-stories.js', array('jquery'), null, true);
    wp_localize_script('sb-stories-js', 'iasb_story_stories', array(
        'ajax_url' => admin_url('admin-ajax.php'),
        'nonce'    => wp_create_nonce('iasb_update_progress_nonce'),
    ));
}
add_action('wp_enqueue_scripts', 'iasb_story_stories_enqueue_assets');

function iasb_render_child_episodes($post_id) {
    // Get the current season
    $current_season = get_post_meta($post_id, '_iasb_story_builder_season', true);

    // Get child episodes assigned via parent
    $child_episode_ids = get_post_meta($post_id, '_iasb_child_episode', false);
    if (!is_array($child_episode_ids)) {
        $child_episode_ids = array();
    }

    // Query for episodes where '_iasb_parent_episode' meta field equals the current post ID
    $query_args = array(
        'post_type'      => 'story_builder',
        'posts_per_page' => -1,
        'post_status'    => 'publish',
        'orderby'        => array(
            'meta_value_num' => 'ASC', // Order by episode number
            'ID'             => 'ASC', // Secondary order by post ID
        ),
        'meta_key'       => '_iasb_story_builder_episode',
        'meta_query'     => array(
            'relation' => 'OR',
            array(
                'key'     => '_iasb_parent_episode',
                'value'   => $post_id,
                'compare' => '=',
                'type'    => 'NUMERIC',
            ),
            array(
                'key'     => '_iasb_child_episode',
                'value'   => $post_id,
                'compare' => '=',
                'type'    => 'NUMERIC',
            ),
        ),
    );

    if (!empty($child_episode_ids)) {
        // Include child episodes assigned via parent post
        $query_args['post__in'] = $child_episode_ids;
    }

    $child_episodes = new WP_Query($query_args);

    if ($child_episodes->have_posts()) {
        echo '<div class="story-choices">';
        echo '<h3 class="choices-heading">' . __('What do you do next?', 'story-builder') . '</h3>';
        echo '<ul class="choices-list">';
        while ($child_episodes->have_posts()) {
            $child_episodes->the_post();
            //$episode_number = get_post_meta(get_the_ID(), '_iasb_story_builder_episode', true);
            //$episode_title = 'Episode ' . $episode_number . ': ' . get_the_title();
            $episode_title = get_the_title();
            echo '<li class="choice-item"><a href="' . get_permalink() . '" data-story-id="' . get_the_ID() . '" class="choice-link">' . esc_html($episode_title) . '</a></li>';
        }
        echo '</ul>';
        echo '</div>';
    } else {
        echo '<div class="story-end">';
        echo '<p>' . __('The End.', 'story-builder') . '</p>';
        echo '</div>';
    }
    wp_reset_postdata();
}

// Helper function to get the next story in a branch within the same season
function iasb_get_next_story_in_branch($branch_id, $current_post_id) {
    $current_episode = get_post_meta($current_post_id, '_iasb_story_builder_episode', true);
    $current_season = get_post_meta($current_post_id, '_iasb_story_builder_season', true);

    $meta_query = array(
        'relation' => 'AND',
        array(
            'key'     => '_iasb_story_builder_season',
            'value'   => $current_season,
            'compare' => '=',
            'type'    => 'NUMERIC',
        ),
    );

    if ($current_episode) {
        $meta_query[] = array(
            'key'     => '_iasb_story_builder_episode',
            'value'   => $current_episode,
            'compare' => '>',
            'type'    => 'NUMERIC',
        );
    } else {
        // If current episode is not set, get any story in the branch
        $meta_query[] = array(
            'key'     => '_iasb_story_builder_episode',
            'compare' => 'EXISTS',
        );
    }

    $args = array(
        'post_type'      => 'story_builder',
        'posts_per_page' => 1,
        'post__not_in'   => array($current_post_id),
        'tax_query'      => array(
            array(
                'taxonomy' => 'story_branch',
                'field'    => 'term_id',
                'terms'    => $branch_id,
            ),
        ),
        'meta_query'     => $meta_query,
        'meta_key'       => '_iasb_story_builder_episode',
        'orderby'        => 'meta_value_num',
        'order'          => 'ASC',
    );

    $query = new WP_Query($args);

    return $query->have_posts() ? $query->posts[0] : null;
}

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
                    echo '<p>' . __('You have reached the end of this storyline.', 'story-builder') . '</p>';
                    echo '</div>';
                }

                wp_reset_postdata();
            } else {
                // No next season found
                echo '<div class="no-more-episodes">';
                echo '<p>' . __('You have reached the end of this storyline.', 'story-builder') . '</p>';
                echo '</div>';
            }
        } else {
            // No seasons used, end of storyline
            echo '<div class="no-more-episodes">';
            echo '<p>' . __('You have reached the end of this storyline.', 'story-builder') . '</p>';
            echo '</div>';
        }
    }

    wp_reset_postdata();
}

// Helper function to get the next season number within the same storyline
function iasb_get_next_season_number($current_season, $current_storyline_id) {
    global $wpdb;

    // Prepare the tax query to ensure we're within the same storyline
    $tax_query_sql = "
        INNER JOIN $wpdb->term_relationships tr ON pm.post_id = tr.object_id
        INNER JOIN $wpdb->term_taxonomy tt ON tr.term_taxonomy_id = tt.term_taxonomy_id
        WHERE tt.taxonomy = 'storyline' AND tt.term_id = %d
    ";

    // Query to find the next season number within the same storyline
    $next_season = $wpdb->get_var($wpdb->prepare(
        "
        SELECT DISTINCT pm.meta_value FROM $wpdb->postmeta pm
        INNER JOIN $wpdb->posts p ON pm.post_id = p.ID
        $tax_query_sql
        AND pm.meta_key = '_iasb_story_builder_season'
        AND pm.meta_value > %d
        AND p.post_status = 'publish'
        ORDER BY pm.meta_value+0 ASC
        LIMIT 1
        ",
        $current_storyline_id,
        $current_season
    ));

    return $next_season ? $next_season : false;
}

// Add Season and Episode Columns to the Fart Story List
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

}
add_action('manage_story_builder_posts_custom_column', 'iasb_display_season_episode_columns', 10, 2);

// Add Storyline Column to the Fart Story List
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

// Add Parallel Universe Column to the Fart Story List
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


function iasb_get_story_structure() {
    // Verify nonce for security
    check_ajax_referer('iasb_story_builder_manager_nonce', 'nonce');

    // Fetch all story stories
    $story_stories = get_posts(array(
        'post_type' => 'story_builder',
        'posts_per_page' => -1,
        'post_status' => 'publish'
    ));

    $story_tree = array();

    // Build the story tree structure
    foreach ($story_stories as $story) {
        $universes = wp_get_post_terms($story->ID, 'parallel_universe', array('fields' => 'names'));
        $branches = wp_get_post_terms($story->ID, 'story_branch', array('fields' => 'names'));
        $endings = wp_get_post_terms($story->ID, 'alternate_ending', array('fields' => 'names'));

        $story_node = array(
            'name' => $story->post_title,
            'universes' => $universes,
            'branches' => $branches,
            'endings' => $endings,
        );

        // Add to the tree
        $story_tree[] = $story_node;
    }

    // Return the tree data in a format that D3.js can use
    wp_send_json_success($story_tree);
}

add_action('wp_ajax_iasb_get_story_structure', 'iasb_get_story_structure');

// Helper function to get the alternate ending story
function iasb_get_alternate_ending_story($ending_id, $current_post_id) {
    $args = array(
        'post_type'      => 'story_builder',
        'posts_per_page' => 1,
        'post__not_in'   => array($current_post_id),
        'tax_query'      => array(
            array(
                'taxonomy' => 'alternate_ending',
                'field'    => 'term_id',
                'terms'    => $ending_id,
            ),
        ),
    );
    $query = new WP_Query($args);
    return $query->have_posts() ? $query->posts[0] : null;
}

// Function to display universes and allow switching if available
function iasb_render_universes($post_id, $user_id) {
    // Get all universes
    $all_universes = get_terms('parallel_universe', array('hide_empty' => false));

    // Get universes associated with the current post
    $current_universes = wp_get_post_terms($post_id, 'parallel_universe');
    $current_universe_id = !empty($current_universes) ? $current_universes[0]->term_id : 'default_universe';

    // Ensure there are universes to work with
    if ($all_universes && !is_wp_error($all_universes)) {
        // Get current season, episode, and storyline
        $current_season = get_post_meta($post_id, '_iasb_story_builder_season', true);
        $current_episode = get_post_meta($post_id, '_iasb_story_builder_episode', true);
        $current_storyline_terms = wp_get_post_terms($post_id, 'storyline', array('fields' => 'ids'));
        $current_storyline_id = !empty($current_storyline_terms) ? $current_storyline_terms[0] : null;

        // Proceed only if storyline, season, and episode are set
        if ($current_storyline_id && $current_season && $current_episode) {
            // Get IDs of current universes
            $current_universe_ids = wp_list_pluck($current_universes, 'term_id');

            $alternate_universes = array();

            foreach ($all_universes as $universe) {
                // Skip the current universes
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
                        'description' => $universe->description, // Assuming you have descriptions for universes
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
                    $tooltip = !empty($alternate['description']) ? esc_attr($alternate['description']) : '';
                    echo '<li><a href="' . esc_url($alternate_link) . '" data-title="' . $tooltip . '">' . esc_html($alternate['name']) . '</a></li>';
                }

                echo '</ul>';
                echo '</div>';
            }
        }
    }
}


//Helper Function to Get Storyline Names:
function iasb_get_storyline_names($post_id) {
    $storylines = wp_get_post_terms($post_id, 'storyline', array('fields' => 'names'));
    return !empty($storylines) ? esc_html(implode(', ', $storylines)) : __('None', 'story-builder');
}


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
}

// Function to display the user's progress per universe
function iasb_display_user_progress($user_id, $current_universe_id = null) {
    $progress = get_user_meta($user_id, 'story_builder_progress', true);
    if ($progress && is_array($progress)) {
        echo '<div class="user-progress">';
        if ($current_universe_id !== null && isset($progress[$current_universe_id])) {
            $story_id = $progress[$current_universe_id]['story_id'];
            echo '<p>' . __('Your progress in this universe:', 'story-builder') . ' <a href="' . get_permalink($story_id) . '">' . get_the_title($story_id) . '</a></p>';
        } else {
            echo '<p>' . __('Your progress:', 'story-builder') . '</p>';
            echo '<ul>';
            foreach ($progress as $universe_id => $data) {
                $universe_name = ($universe_id === 'default_universe') ? __('Default Universe', 'story-builder') : (get_term($universe_id, 'parallel_universe') ? get_term($universe_id, 'parallel_universe')->name : __('Unknown Universe', 'story-builder'));
                if (is_array($data) && isset($data['story_id'])) {
                    $story_id = $data['story_id'];
                    echo '<li>' . esc_html($universe_name) . ': <a href="' . get_permalink($story_id) . '">' . get_the_title($story_id) . '</a></li>';
                }
            }
            echo '</ul>';
        }
        echo '</div>';
    }
}


//Helper Function to get User Progress:
function iasb_get_user_story_progress($user_id) {
    return get_user_meta($user_id, 'story_builder_progress', true);
}


// AJAX handler to update user progress
function iasb_update_user_progress() {
    check_ajax_referer('iasb_update_progress_nonce', 'nonce');

    if (!is_user_logged_in()) {
        wp_send_json_error(__('You must be logged in to save your progress', 'story-builder'));
    }

    $user_id = get_current_user_id();
    $story_id = isset($_POST['story_id']) ? intval($_POST['story_id']) : 0;

    if ($story_id) {
        iasb_save_user_story_progress($user_id, $story_id);
        wp_send_json_success(__('Progress saved', 'story-builder'));
    } else {
        wp_send_json_error(__('Invalid story ID', 'story-builder'));
    }
}
add_action('wp_ajax_iasb_update_user_progress', 'iasb_update_user_progress');

// Template Redirect
function iasb_story_builder_template($template) {
    if (is_singular('story_builder')) {
        $plugin_template = plugin_dir_path(__FILE__) . 'templates/single-sb_story.php';
        if (file_exists($plugin_template)) {
            return $plugin_template;
        }
    }
    return $template;
}
add_filter('single_template', 'iasb_story_builder_template');

// Shortcode to display "Resume Reading" button
function iasb_resume_reading_shortcode() {
    if (is_user_logged_in()) {
        $user_id = get_current_user_id();
        $progress = get_user_meta($user_id, 'story_builder_progress', true);
        if ($progress && is_array($progress)) {
            // Assume the last universe the user was in
            end($progress);
            $last_universe_id = key($progress);
            $last_story_id = $progress[$last_universe_id]['story_id'];
            $universe_name = ($last_universe_id === 'default_universe') ? __('Default Universe', 'story-builder') : get_term($last_universe_id, 'parallel_universe')->name;

            $output = '<div class="resume-reading-button">';
            $output .= '<a href="' . get_permalink($last_story_id) . '" class="button">' . sprintf(__('Resume Reading in %s', 'story-builder'), esc_html($universe_name)) . '</a>';
            $output .= '</div>';

            return $output;
        }
    }
    return '';
}
add_shortcode('iasb_resume_reading', 'iasb_resume_reading_shortcode');


// Function to display breadcrumb navigation
function iasb_display_breadcrumbs($post_id) {
    $storylines = wp_get_post_terms($post_id, 'storyline');
    $universes = wp_get_post_terms($post_id, 'parallel_universe');
    $season = get_post_meta($post_id, '_iasb_story_builder_season', true);
    $episode = get_post_meta($post_id, '_iasb_story_builder_episode', true);

    echo '<nav class="fc-breadcrumbs">';
    echo 'Current location: <a href="' . get_post_type_archive_link('story_builder') . '">' . __('Fart Stories', 'story-builder') . '</a> &raquo; ';
    if (!empty($storylines)) {
        $storyline = $storylines[0];
        echo '<a href="' . get_term_link($storyline) . '">' . esc_html($storyline->name) . '</a> &raquo; ';
    }
    if (!empty($universes)) {
        $universe = $universes[0];
        echo esc_html($universe->name) . ' &raquo; ';
    }
    echo sprintf(__('Season %s', 'story-builder'), '<a href="' . esc_url(add_query_arg('season', $season, get_post_type_archive_link('story_builder'))) . '">' . esc_html($season) . '</a>') . ' &raquo; ';
    echo sprintf(__('Episode %s', 'story-builder'), '<a href="' . esc_url(add_query_arg(array('season' => $season, 'episode' => $episode), get_post_type_archive_link('story_builder'))) . '">' . esc_html($episode) . '</a>');
    echo '</nav>';
}

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

// Shortcode to display the user's story name
function iasb_user_story_name_shortcode($atts) {
    // Get the current user ID
    $user_id = get_current_user_id();

    // Initialize the output variable
    $output = '';

    // If the user is logged in
    if ($user_id) {
        // Get the user's story name
        $story_name = get_user_meta($user_id, 'iasb_story_name', true);

        // If the story name is set, use it; otherwise, use the display name
        if (!empty($story_name)) {
            $output = esc_html($story_name);
        } else {
            $user_info = get_userdata($user_id);
            $output = esc_html($user_info->display_name);
        }
    } else {
        // If the user is not logged in, return a default value or prompt
        $output = __('Adventurer', 'story-builder');
    }

    // Apply a filter to allow other plugins to modify the output
    $output = apply_filters('iasb_user_story_name', $output, $atts);

    return "<strong class='user_story_name'>{$output}</strong>";
}
add_shortcode('user_story_name', 'iasb_user_story_name_shortcode');

// Shortcode to display NPC character name
function iasb_npc_character_name_shortcode($atts) {
    // Shortcode attributes with default values
    $atts = shortcode_atts(
        array(
            'id'    => 0,      // The ID of the character
            'slug'  => '',     // The slug of the character
            'link'  => 'false' // Whether to link to the character's profile page ('true' or 'false')
        ),
        $atts,
        'npc_character_name'
    );

    // Get the character post
    $character_post = null;

    if (!empty($atts['slug'])) {
        // Get the character by slug
        $character_post = get_page_by_path($atts['slug'], OBJECT, 'iasb_character');
    } elseif (!empty($atts['id'])) {
        // Get the character by ID
        $character_post = get_post(intval($atts['id']));
    }

    $output = '';


    if ($character_post && $character_post->post_type === 'iasb_character' && $character_post->post_status === 'publish') {
        // Get the character's name
        $character_name = $character_post->post_title;

        // Check if we should link to the character's profile page
        if (filter_var($atts['link'], FILTER_VALIDATE_BOOLEAN)) {
            $character_link = get_permalink($character_post->ID);
            $output = '<a href="' . esc_url($character_link) . '" target="_blank">' . esc_html($character_name) . '</a>';
            // Apply a filter to allow modification of the output
            $output = apply_filters('iasb_npc_character_name', $output, $atts, $character_post);
            return $output;

        } else {
            
            $output = esc_html($character_name);
            return $output;
        }
    }

    // If character not found, return an empty string or a placeholder
    return __('Unknown Character', 'story-builder');
}
add_shortcode('npc_character_name', 'iasb_npc_character_name_shortcode');


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

// Shortcode to display content based on user's current story progress
function iasb_conditional_content_shortcode($atts, $content = null) {
    $atts = shortcode_atts(
        array(
            'episode' => 0,
        ),
        $atts,
        'conditional_content'
    );

    $user_id = get_current_user_id();
    if (!$user_id) {
        return ''; // Not logged in
    }

    $progress = get_user_meta($user_id, 'story_builder_progress', true);
    if (!$progress || !is_array($progress)) {
        return ''; // No progress found
    }

    $target_episode = intval($atts['episode']);
    foreach ($progress as $universe => $data) {
        if (isset($data['story_id'])) {
            $current_episode = get_post_meta($data['story_id'], '_iasb_story_builder_episode', true);
            if (intval($current_episode) >= $target_episode) {
                return do_shortcode($content);
            }
        }
    }

    return ''; // Episode not reached
}
add_shortcode('conditional_content', 'iasb_conditional_content_shortcode');