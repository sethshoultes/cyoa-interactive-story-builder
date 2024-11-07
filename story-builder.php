<?php
/**
 * Plugin Name: CYOA Interactive Adventure Story Builder
 * Description: A storytelling platform for choose your own adventure style stories. Users can choose their own path through the story. Shortcodes are available to embed stories in posts and pages: [user_story_name].
 * Version: 1.1.4
 * Author: Seth Shoultes
 * License: GPL2
 */
use YahnisElsts\PluginUpdateChecker\v5\PucFactory;
// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Initialize the plugin update checker.
 */
function iasb_story_builder_plugin_auto_update() {
    // Include the library if it's not already included
    if ( ! class_exists( '\\YahnisElsts\\PluginUpdateChecker\\PluginUpdateChecker' ) ) {
        require_once plugin_dir_path( __FILE__ ) . 'includes/plugin-update-checker/plugin-update-checker.php';
    }

    // Replace these variables with your own repository details
    $github_username   = 'sethshoultes';
    $github_repository = 'cyoa-interactive-story-builder';
    $plugin_slug       = 'cyoa-interactive-story-builder'; // This should match the plugin's folder name

    // Initialize the update checker
    $updateChecker = PucFactory::buildUpdateChecker(
        "https://github.com/{$github_username}/{$github_repository}/",
        __FILE__,
        $plugin_slug
    );

    /*
     * Create a new release using the "Releases" feature on GitHub. The tag name and release title don't matter. 
     * The description is optional, but if you do provide one, it will be displayed when the user clicks the 
     * "View version x.y.z details" link on the "Plugins" page. Note that PUC ignores releases marked as 
     * "This is a pre-release".
     *
     * If you want to use release assets, call the enableReleaseAssets() method after creating the update checker instance:
     */
    //$updateChecker->getVcsApi()->enableReleaseAssets();

    // Optional: Set the branch that contains the stable release
    $updateChecker->setBranch('main'); // Change 'main' to the branch you use

    // Optional: If your repository is private, add your access token
    // $updateChecker->setAuthentication('your_github_access_token');
}
add_action( 'init', 'iasb_story_builder_plugin_auto_update' );

require_once plugin_dir_path( __FILE__ ) . 'includes/shortcodes.php';
require_once plugin_dir_path( __FILE__ ) . 'includes/custom-post-types.php';
require_once plugin_dir_path( __FILE__ ) . 'includes/admin-meta-boxes.php';
require_once plugin_dir_path( __FILE__ ) . 'includes/state-manager.php';

// Enqueue block editor assets
function iasb_enqueue_block_editor_assets() {
    wp_enqueue_script(
        'iasb-blocks',
        plugins_url('js/blocks.js', __FILE__),
        array('wp-blocks', 'wp-i18n', 'wp-element', 'wp-editor'),
        filemtime(plugin_dir_path(__FILE__) . 'js/blocks.js')
    );
    wp_enqueue_style(
        'iasb-block-editor-styles',
        plugins_url('css/admin-styles.css', __FILE__),
        array(),
        filemtime(plugin_dir_path(__FILE__) . 'css/admin-styles.css')
    );
}
add_action('enqueue_block_editor_assets', 'iasb_enqueue_block_editor_assets');

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

// Enqueue Font Awesome on the template pages
function iasb_enqueue_font_awesome() {
    if (is_singular('iasb_weapon') || is_post_type_archive('iasb_weapon') ||
        is_singular('iasb_location') || is_post_type_archive('iasb_location') ||
        is_singular('iasb_item') || is_post_type_archive('iasb_item') ||
        is_singular('iasb_lore') || is_post_type_archive('iasb_lore') ||
        is_singular('iasb_organization') || is_post_type_archive('iasb_organization') ||
        is_singular('iasb_technology') || is_post_type_archive('iasb_technology') ||
        is_singular('iasb_law') || is_post_type_archive('iasb_law') ||
        is_singular('iasb_vehicle') || is_post_type_archive('iasb_vehicle') ||
        is_singular('iasb_character') || is_post_type_archive('iasb_character')) {
        wp_enqueue_style('font-awesome', 'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css', array(), '6.0.0-beta3');
    }
}
add_action('wp_enqueue_scripts', 'iasb_enqueue_font_awesome');

// Template Redirects
// Story Template Redirect

function iasb_story_builder_template($template) {
    if (is_singular('story_builder')) {
        // Check if the theme has an override template
        $theme_template = locate_template(array('templates/archive-iasb_story.php', 'templates/single-iasb_story.php')); // Updated file name

        if ($theme_template) {
            return $theme_template;
        } else {
            // Load the default plugin template
            return plugin_dir_path(__FILE__) . 'templates/single-iasb_story.php'; // Updated file name
        }
    }
    return $template;
}
add_filter('single_template', 'iasb_story_builder_template');

// Updated Character Template Redirect
function iasb_story_builder_character_template($template) {
    if (is_singular('iasb_character')) {
        // Check if the theme has an override template
        $theme_template = locate_template(array('templates/archive-iasb_character.php', 'templates/single-iasb_character.php')); // Updated file name

        if ($theme_template) {
            return $theme_template;
        } else {
            // Load the default plugin template
            return plugin_dir_path(__FILE__) . 'templates/single-iasb_character.php'; // Updated file name
        }
    }
    return $template;
}
add_filter('single_template', 'iasb_story_builder_character_template');

// Updated Vehicle Template Redirect
function iasb_story_builder_vehicle_template($template) {
    if (is_singular('iasb_vehicle')) {
        // Check if the theme has an override template
        $theme_template = locate_template(array('templates/archive-iasb_vehicle.php', 'templates/single-iasb_vehicle.php')); // Updated file name

        if ($theme_template) {
            return $theme_template;
        } else {
            // Load the default plugin template
            return plugin_dir_path(__FILE__) . 'templates/single-iasb_vehicle.php'; // Updated file name
        }
    }
    return $template;
}
add_filter('single_template', 'iasb_story_builder_vehicle_template');

// Updated Weapon Template Redirect
function iasb_story_builder_weapon_template($template) {
    if (is_singular('iasb_weapon')) {
        // Check if the theme has an override template
        $theme_template = locate_template(array('templates/archive-iasb_weapon.php', 'templates/single-iasb_weapon.php')); // Updated file name

        if ($theme_template) {
            return $theme_template;
        } else {
            // Load the default plugin template
            return plugin_dir_path(__FILE__) . 'templates/single-iasb_weapon.php'; // Updated file name
        }
    }
    return $template;
}
add_filter('single_template', 'iasb_story_builder_weapon_template');

// Updated Location Template Redirect
function iasb_story_builder_location_template($template) {
    if (is_singular('iasb_location')) {
        // Check if the theme has an override template
        $theme_template = locate_template(array('templates/archive-iasb_location.php', 'templates/single-iasb_location.php')); // Updated file name

        if ($theme_template) {
            return $theme_template;
        } else {
            // Load the default plugin template
            return plugin_dir_path(__FILE__) . 'templates/single-iasb_location.php'; // Updated file name
        }
    }
    return $template;
}
add_filter('single_template', 'iasb_story_builder_location_template');

// Updated Item Template Redirect
function iasb_story_builder_item_template($template) {
    if (is_singular('iasb_item')) {
        // Check if the theme has an override template
        $theme_template = locate_template(array('templates/archive-iasb_item.php', 'templates/single-iasb_item.php')); // Updated file name

        if ($theme_template) {
            return $theme_template;
        } else {
            // Load the default plugin template
            return plugin_dir_path(__FILE__) . 'templates/single-iasb_item.php'; // Updated file name
        }
    }
    return $template;
}
add_filter('single_template', 'iasb_story_builder_item_template');

// Updated Lore Template Redirect
function iasb_story_builder_lore_template($template) {
    if (is_singular('iasb_lore')) {
        // Check if the theme has an override template
        $theme_template = locate_template(array('templates/archive-iasb_lore.php', 'templates/single-iasb_lore.php')); // Updated file name

        if ($theme_template) {
            return $theme_template;
        } else {
            // Load the default plugin template
            return plugin_dir_path(__FILE__) . 'templates/single-iasb_lore.php'; // Updated file name
        }
    }
    return $template;
}
add_filter('single_template', 'iasb_story_builder_lore_template');

// Updated Organization Template Redirect
function iasb_story_builder_organization_template($template) {
    if (is_singular('iasb_organization')) {
        // Check if the theme has an override template
        $theme_template = locate_template(array('templates/archive-iasb_organization.php', 'templates/single-iasb_organization.php')); // Updated file name

        if ($theme_template) {
            return $theme_template;
        } else {
            // Load the default plugin template
            return plugin_dir_path(__FILE__) . 'templates/single-iasb_organization.php'; // Updated file name
        }
    }
    return $template;
}
add_filter('single_template', 'iasb_story_builder_organization_template');

// Updated Technology Template Redirect
function iasb_story_builder_technology_template($template) {
    if (is_singular('iasb_technology')) {
        // Check if the theme has an override template
        $theme_template = locate_template(array('templates/archive-iasb_technology.php', 'templates/single-iasb_technology.php')); // Updated file name

        if ($theme_template) {
            return $theme_template;
        } else {
            // Load the default plugin template
            return plugin_dir_path(__FILE__) . 'templates/single-iasb_technology.php'; // Updated file name
        }
    }
    return $template;
}
add_filter('single_template', 'iasb_story_builder_technology_template');

// Updated Law Template Redirect
function iasb_story_builder_law_template($template) {
    if (is_singular('iasb_law')) {
        // Check if the theme has an override template
        $theme_template = locate_template(array('templates/archive-iasb_law.php', 'templates/single-iasb_law.php')); // Updated file name

        if ($theme_template) {
            return $theme_template;
        } else {
            // Load the default plugin template
            return plugin_dir_path(__FILE__) . 'templates/single-iasb_law.php'; // Updated file name
        }
    }
    return $template;
}
add_filter('single_template', 'iasb_story_builder_law_template');



// Enqueue D3.js library and custom script for the Story Manager page
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
// Function to render the Story Manager page
function iasb_render_story_manager_page() {
    ?>
    <div class="wrap">
        <h1><?php _e('Story Manager', 'story-builder'); ?></h1>
        <div id="story-manager-tree"></div> <!-- This is where the D3.js tree will be rendered -->
    </div>
    <?php
}
// Add the Story Manager page to the admin menu
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


// Function to render the Child Episode Buttons on the front end
function iasb_render_child_episodes($post_id) {
    $user_id = get_current_user_id();
    $state_manager = new IASB_State_Manager($user_id, $post_id);

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
       iasb_render_next_episode_link($post_id);
    }
    wp_reset_postdata();
}
function iasb_process_choice() {
    if (isset($_POST['choice_id']) && isset($_POST['story_id'])) {
        $user_id = get_current_user_id();
        $state_manager = new IASB_State_Manager($user_id, $_POST['story_id']);
        $state_manager->apply_choice_consequences($_POST['choice_id']);
        wp_send_json_success();
    } else {
        wp_send_json_error();
    }
}
add_action('wp_ajax_iasb_process_choice', 'iasb_process_choice');
add_action('wp_ajax_nopriv_iasb_process_choice', 'iasb_process_choice');
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

// Function to display universes and allow switching if available front end
function iasb_render_universes($post_id, $user_id) {

    // error_log("iasb_render_universes called for post_id: $post_id");

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
                
               /*  error_log("Query for universe {$universe->name}: " . $query->request);
                if ($query->have_posts()) {
                    error_log("Found alternate post in universe {$universe->name}");
                } else {
                    error_log("No alternate post found in universe {$universe->name}");
                } */

                if ($query->have_posts()) {
                    $alternate_post = $query->posts[0];
                    $alternate_universes[] = array(
                        'name' => $universe->name,
                        'link' => get_permalink($alternate_post->ID),
                        'description' => $universe->description, // Description of the universe
                    );
                }
                //error_log("Alternate universes found: " . count($alternate_universes));

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
