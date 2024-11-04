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

// Shortcode to display content based on user's current story progress
function iasb_conditional_content_shortcode($atts, $content = null) {
    $atts = shortcode_atts([
        'id' => 0, // Add post_id parameter
        'content' => $content,
    ], $atts, 'conditional_content');

    $user_id = get_current_user_id();
    if (!$user_id) {
        return ''; // Not logged in
    }

    $progress = get_user_meta($user_id, '_iasb_viewed_episodes', true);
    if (!$progress || !is_array($progress)) {
        return ''; // No progress found
    }

    $specific_id = intval($atts['id']);

    if (in_array($specific_id, $progress)) {
        return do_shortcode($atts['content']);
    }

    return ''; // Episode not reached
}
add_shortcode('conditional_content', 'iasb_conditional_content_shortcode');

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

    // Register Conditional Content block
    register_block_type('iasb/conditional-content', array(
        'attributes' => array(
            'id' => array('type' => 'number'),
            'content' => array('type' => 'string'),
        ),
        'render_callback' => 'iasb_conditional_content_shortcode',
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