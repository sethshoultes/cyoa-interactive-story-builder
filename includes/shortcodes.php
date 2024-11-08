<?php
/**
 * CYOA Interactive Story Builder Shortcodes
 * 
 * This file contains all the shortcodes used in the CYOA Interactive Story Builder plugin.
 * Below is a list of available shortcodes and their usage:
 *
 * [iasb_resume_reading]
 * Description: Displays a "Resume Reading" button for logged-in users.
 * Usage: [iasb_resume_reading]
 * Parameters: None
 *
 * [conditional_content]
 * Description: Displays content based on user's current story progress.
 * Usage: [conditional_content condition="state_variable('example') > 5"]Content to show[/conditional_content]
 * Parameters:
 *   - condition: The condition to evaluate (required)
 *   - id: The post ID to check against (optional, defaults to current post)
 *
 * [state_variable]
 * Description: Displays the value of a state variable.
 * Usage: [state_variable name="example"]
 * Parameters:
 *   - name: The name of the state variable (required)
 *
 * [character_attribute]
 * Description: Displays the value of a character attribute.
 * Usage: [character_attribute name="strength"]
 * Parameters:
 *   - name: The name of the character attribute (required)
 *
 * [dynamic_content]
 * Description: Injects dynamic content based on the specified type and ID.
 * Usage: [dynamic_content type="text" id="123"]
 * Parameters:
 *   - type: The type of content (text, image, link, video) (required)
 *   - id: The ID of the content (e.g., post ID, attachment ID) (required)
 *   - class: Additional CSS classes (optional)
 *   - title: Title attribute for images (optional)
 *   - target: Link target for links (optional, defaults to "_self")
 *
 * [user_story_name]
 * Description: Displays the user's story name or default name for non-logged-in users.
 * Usage: [user_story_name]
 * Parameters: None
 *
 * [npc_character_name]
 * Description: Displays an NPC character's name, optionally linked to their profile.
 * Usage: [npc_character_name id="123" link="true"]
 * Parameters:
 *   - id: The ID of the character (required if slug not provided)
 *   - slug: The slug of the character (required if id not provided)
 *   - link: Whether to link to the character's profile (optional, defaults to "false")
 *
 * [debug_state]
 * Description: Displays debug information about the current state (for development use).
 * Usage: [debug_state]
 * Parameters: None
 *
 * [test_shortcode]
 * Description: A test shortcode to check if shortcodes are working.
 * Usage: [test_shortcode]
 * Parameters: None
 *
 * [update_quest_progress]
 * Description: Updates the progress of a specified quest.
 * Usage: [update_quest_progress quest="quest_name" status="completed"]
 * Parameters:
 *   - quest: The name of the quest (required)
 *   - status: The new status of the quest (required)
 *
 * [display_quest_progress]
 * Description: Displays the progress of a specified quest.
 * Usage: [display_quest_progress quest="quest_name"]
 * Parameters:
 *   - quest: The name of the quest (required)
 *
 * [quest_progress_condition]
 * Description: Conditionally displays content based on quest progress.
 * Usage: [quest_progress_condition quest="quest_name" status="completed"]Content to show[/quest_progress_condition]
 * Parameters:
 *   - quest: The name of the quest (required)
 *   - status: The status to check against (required)
 */

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

            return true;
        }
    }
    return '';
}
add_shortcode('iasb_resume_reading', 'iasb_resume_reading_shortcode');

/* Conditional Content Based on User Progress */
// Shortcode to display content based on user's current story progress
function iasb_conditional_content_shortcode($atts, $content = null) {
    $atts = shortcode_atts([
        'id' => get_the_ID(),
        'condition' => '',
        'content' => '',
    ], $atts, 'conditional_content');

    if (empty($content) && !empty($atts['content'])) {
        $content = $atts['content'];
    }

    if (empty($content) || empty($atts['condition'])) {
        return '';
    }

    $user_id = get_current_user_id();
    $story_id = get_the_ID();
    $character_id = 'default_character';
    $state_manager = new IASB_State_Manager($user_id, $story_id, $character_id);
    
    $condition = html_entity_decode(str_replace(array('"', '"'), '"', $atts['condition']), ENT_QUOTES);

    if ($state_manager->evaluate_complex_condition($condition)) {
        return do_shortcode($content);
    }

    return '';
}
add_shortcode('conditional_content', 'iasb_conditional_content_shortcode');



/* Dynamic Content Shortcodes */
// Shortcode to inject dynamic content with parameters
function iasb_dynamic_content_shortcode($atts) {
    // Shortcode attributes with default values
    $atts = shortcode_atts(
        array(
            'type'    => 'text',     // Type of content ('text', 'image', 'link', etc.)
            'id'      => 0,          // ID related to the content (e.g., post ID, character ID)
            'class'   => '',         // Additional CSS classes
            'title'   => '',         // Title attribute
            'target'  => '_self',    // Link target
        ),
        $atts,
        'dynamic_content'
    );

    $output = '';

    switch ($atts['type']) {
        case 'text':
            if ($atts['id']) {
                $post = get_post($atts['id']);
                if ($post) {
                    $output = apply_filters('the_content', $post->post_content);
                } else {
                    $output = __('Post not found', 'story-builder');
                }
            } else {
                $output = __('ID not set', 'story-builder');
            }
            break;

        case 'image':
            if ($atts['id']) {
                $image_url = wp_get_attachment_url($atts['id']);
                if ($image_url) {
                    $output = '<img src="' . esc_url($image_url) . '" class="' . esc_attr($atts['class']) . '" alt="' . esc_attr($atts['title']) . '" />';
                } else {
                    $output = __('Image not found', 'story-builder');
                }
            } else {
                $output = __('ID not set', 'story-builder');
            }
            break;

        case 'link':
            if ($atts['id']) {
                $post = get_post($atts['id']);
                if ($post) {
                    $output = '<a href="' . get_permalink($post->ID) . '" class="' . esc_attr($atts['class']) . '" target="' . esc_attr($atts['target']) . '">' . esc_html($post->post_title) . '</a>';
                } else {
                    $output = __('Link not found', 'story-builder');
                }
            } else {
                $output = __('ID not set', 'story-builder');
            }
            break;
        case 'video':
            if ($atts['id']) {
                $video_url = wp_get_attachment_url($atts['id']);
                if ($video_url) {
                    $output = '<video class="' . esc_attr($atts['class']) . '" controls>';
                    $output .= '<source src="' . esc_url($video_url) . '" type="video/mp4">';
                    $output .= __('Your browser does not support the video tag.', 'story-builder');
                    $output .= '</video>';
                } else {
                    $output = __('Video not found', 'story-builder');
                }
            } else {
                $output = __('ID not set', 'story-builder');
            }
        break;

        // Add more cases as needed for different types

        default:
            $output = '';
    }

    // Apply a filter to allow modification
    $output = apply_filters('iasb_dynamic_content', $output, $atts);

    return true;
}
add_shortcode('dynamic_content', 'iasb_dynamic_content_shortcode');

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
            return true;

        } else {
            
            $output = esc_html($character_name);
            return true;
        }
    }

    // If character not found, return an empty string or a placeholder
    return __('Unknown Character', 'story-builder');
}
add_shortcode('npc_character_name', 'iasb_npc_character_name_shortcode');

// Shortcode to test if shortcodes are working
function iasb_test_shortcode($atts) {
   //error_log('Test Shortcode - Function called');
    return 'Test shortcode is working!';
}
add_shortcode('test_shortcode', 'iasb_test_shortcode');

/* Gutenberg Blocks */
// Register Gutenberg blocks
function iasb_register_gutenberg_blocks() {
    // Check if Gutenberg is available
    if (!function_exists('register_block_type')) {
        return;
    }

    // Register Resume Reading block
    register_block_type('iasb/inventory-display', array(
        'editor_script' => 'iasb-inventory-display-editor',
        'editor_style' => 'iasb-inventory-display-editor',
        'render_callback' => 'iasb_render_inventory_block',
    ));
    // Register Dynamic Content block
    register_block_type('iasb/dynamic-content', array(
        'attributes' => array(
            'type' => array('type' => 'string', 'default' => 'text'),
            'id' => array('type' => 'number'),
            'class' => array('type' => 'string'),
            'title' => array('type' => 'string'),
            'target' => array('type' => 'string', 'default' => '_self'),
        ),
        'render_callback' => 'iasb_dynamic_content_shortcode',
    ));

    // Register User Story Name block
    register_block_type('iasb/user-story-name', array(
        'render_callback' => 'iasb_user_story_name_shortcode',
    ));

    // Register NPC Character Name block
    register_block_type('iasb/npc-character-name', array(
        'attributes' => array(
            'id' => array('type' => 'number'),
            'slug' => array('type' => 'string'),
            'link' => array('type' => 'string', 'default' => 'false'),
        ),
        'render_callback' => 'iasb_npc_character_name_shortcode',
    ));

    // Conditional Content block
    register_block_type('iasb/conditional-content', array(
        'attributes' => array(
            //'id' => array('type' => 'number'),
            'condition' => array('type' => 'string'),
            'content' => array('type' => 'string'),
        ),
        'render_callback' => 'iasb_conditional_content_shortcode',
    ));

    // State Variable block
    register_block_type('iasb/state-variable', array(
        'attributes' => array(
            'name' => array('type' => 'string'),
        ),
        'render_callback' => 'iasb_state_variable_shortcode',
    ));

    // Update State block
    register_block_type('iasb/update-state', array(
        'attributes' => array(
            'action' => array('type' => 'string'),
            'value' => array('type' => 'string'),
        ),
        'render_callback' => 'iasb_update_state_shortcode',
    ));

    register_block_type('iasb/add-to-inventory', array(
        'attributes' => array(
            'item' => array('type' => 'string'),
            'quantity' => array('type' => 'number', 'default' => 1),
        ),
        'render_callback' => 'iasb_render_add_to_inventory_block',
    ));

    register_block_type('iasb/inventory-display', array(
        'render_callback' => 'iasb_render_inventory_block',
    ));
}
add_action('init', 'iasb_register_gutenberg_blocks');

// Add block category for IASB blocks
function iasb_block_category($categories, $post) {
    return array_merge(
        $categories,
        array(
            array(
                'slug' => 'iasb-blocks',
                'title' => __('Interactive Story Builder', 'story-builder'),
            ),
        )
    );
}
add_filter('block_categories_all', 'iasb_block_category', 10, 2);

function iasb_register_conditional_content_block() {
    register_block_type('iasb/conditional-content', array(
        'attributes' => array(
            'id' => array('type' => 'number'),
            'condition' => array('type' => 'string'),
            'content' => array('type' => 'string'),
        ),
        'render_callback' => 'iasb_render_conditional_content_block',
    ));
}
add_action('init', 'iasb_register_conditional_content_block');

function iasb_render_conditional_content_block($attributes, $content) {
   // error_log('iasb_render_conditional_content_block called with: ' . print_r($attributes, true));
    $shortcode_str = '[conditional_content';
    if (isset($attributes['id'])) {
        $shortcode_str .= ' id="' . esc_attr($attributes['id']) . '"';
    }
    if (isset($attributes['condition'])) {
        $shortcode_str .= ' condition="' . esc_attr($attributes['condition']) . '"';
    }
    $shortcode_str .= ']' . ($attributes['content'] ?? '') . '[/conditional_content]';
    //error_log('Generated shortcode: ' . $shortcode_str);
    return do_shortcode($shortcode_str);
}



