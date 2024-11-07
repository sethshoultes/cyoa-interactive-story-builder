<?php

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
    if (!$user_id) {
        return '';
    }

    $state_manager = new IASB_State_Manager($user_id, $atts['id']);
    
    $condition = html_entity_decode(str_replace(array('"', '"'), '"', $atts['condition']), ENT_QUOTES);

    error_log("Evaluating condition in function iasb_conditional_content_shortcode--> " . $condition);

    if ($state_manager->evaluate_complex_condition($condition)) {
        error_log("Condition evaluated to true in function iasb_conditional_content_shortcode--> " . $condition);
        return do_shortcode($content);
    }

    error_log("Condition evaluated to false in function iasb_conditional_content_shortcode--> " . $condition);
    return '';
}
add_shortcode('conditional_content', 'iasb_conditional_content_shortcode');

/* State Shortcodes */
// Shortcode for displaying state variables
function iasb_state_variable_shortcode($atts) {
    $atts = shortcode_atts(array(
        'name' => '',
    ), $atts);

    $user_id = get_current_user_id();
    $post_id = get_the_ID();
    $state_manager = new IASB_State_Manager($user_id, $post_id);
    
    $value = $state_manager->get_state_variable($atts['name']);
    error_log("State Variable - Getting in function iasb_state_variable_shortcode--> {$atts['name']}, Value: $value");
    return $value;
}
add_shortcode('state_variable', 'iasb_state_variable_shortcode');

// Shortcode for updating state
function iasb_character_attribute_shortcode($atts) {
    $atts = shortcode_atts(array(
        'name' => '',
    ), $atts);

    $user_id = get_current_user_id();
    $post_id = get_the_ID();
    $state_manager = new IASB_State_Manager($user_id, $post_id);
    
    $value = $state_manager->get_character_attribute($atts['name']);
    error_log("Character Attribute - Getting in function iasb_character_attribute_shortcode--> {$atts['name']}, Value: $value");
    return $value;
}
add_shortcode('character_attribute', 'iasb_character_attribute_shortcode');

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

    return $output;
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

/* Debug Shortcodes */
// Shortcode to display debug information about the state
function iasb_debug_state_shortcode($atts) {
    $user_id = get_current_user_id();
    $post_id = get_the_ID();
    $state_manager = new IASB_State_Manager($user_id, $post_id);

   // error_log('Debug State Shortcode - Function called');
    
    $variables = $state_manager->get_all_variables();
    
    $debug_output = "Current State:\n";
    foreach ($variables as $key => $value) {
        $debug_output .= "$key: $value\n";
    }

   // error_log('Debug State Shortcode - Output: ' . $debug_output);

    return '<pre>' . esc_html($debug_output) . '</pre>';
}
add_shortcode('debug_state', 'iasb_debug_state_shortcode');

// Shortcode to test if shortcodes are working
function iasb_test_shortcode($atts) {
   //error_log('Test Shortcode - Function called');
    return 'Test shortcode is working!';
}
add_shortcode('test_shortcode', 'iasb_test_shortcode');

// Shortcode to update quest progress
function iasb_update_quest_progress_shortcode($atts) {
    $atts = shortcode_atts(array(
        'quest' => '',
        'status' => '',
    ), $atts);

    if (empty($atts['quest']) || empty($atts['status'])) {
        return '';
    }

    $user_id = get_current_user_id();
    $story_id = get_the_ID();
    $state_manager = new IASB_State_Manager($user_id, $story_id);
    $state_manager->update_quest_progress($atts['quest'], $atts['status']);

    return ''; // This shortcode doesn't output anything
}
add_shortcode('update_quest_progress', 'iasb_update_quest_progress_shortcode');

// Shortcode to display quest progress
function iasb_display_quest_progress_shortcode($atts) {
    $atts = shortcode_atts(array(
        'quest' => '',
    ), $atts);

    if (empty($atts['quest'])) {
        return '';
    }

    $user_id = get_current_user_id();
    $story_id = get_the_ID();
    $state_manager = new IASB_State_Manager($user_id, $story_id);
    $quest_progress = $state_manager->get_quest_progress($atts['quest']);

    return isset($quest_progress[$atts['quest']]) ? esc_html($quest_progress[$atts['quest']]) : 'Not started';
}
add_shortcode('display_quest_progress', 'iasb_display_quest_progress_shortcode');

function iasb_quest_progress_condition_shortcode($atts, $content = null) {
    $atts = shortcode_atts(array(
        'quest' => '',
        'status' => 'complete',
    ), $atts);

    if (empty($atts['quest'])) {
        return '';
    }

    $user_id = get_current_user_id();
    $story_id = get_the_ID();
    $state_manager = new IASB_State_Manager($user_id, $story_id);
    $quest_progress = $state_manager->get_quest_progress($atts['quest']);

    if ($quest_progress == $atts['status']) {
        return do_shortcode($content);
    }

    return '';
}
add_shortcode('quest_progress_condition', 'iasb_quest_progress_condition_shortcode');




/* Gutenberg Blocks */
// Register Gutenberg blocks
function iasb_register_gutenberg_blocks() {
    // Check if Gutenberg is available
    if (!function_exists('register_block_type')) {
        return;
    }

    // Register Resume Reading block
    register_block_type('iasb/resume-reading', array(
        'render_callback' => 'iasb_resume_reading_shortcode',
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
    error_log('iasb_render_conditional_content_block called with: ' . print_r($attributes, true));
    $shortcode_str = '[conditional_content';
    if (isset($attributes['id'])) {
        $shortcode_str .= ' id="' . esc_attr($attributes['id']) . '"';
    }
    if (isset($attributes['condition'])) {
        $shortcode_str .= ' condition="' . esc_attr($attributes['condition']) . '"';
    }
    $shortcode_str .= ']' . ($attributes['content'] ?? '') . '[/conditional_content]';
    error_log('Generated shortcode: ' . $shortcode_str);
    return do_shortcode($shortcode_str);
}