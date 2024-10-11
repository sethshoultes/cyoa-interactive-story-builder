<?php
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