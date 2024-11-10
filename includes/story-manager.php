<?php
function enqueue_story_manager_scripts($hook = '') {
    if ('story_builder_page_story-story-manager' !== $hook) {
        return;
    }
    wp_enqueue_script('jquery');
    wp_enqueue_script('wp-element');
    wp_enqueue_script('wp-components');
    wp_enqueue_script('wp-api-fetch');
    wp_enqueue_script('wp-i18n');

    wp_enqueue_script(
        'story-manager',
        plugins_url('../js/build/story-manager.js', __FILE__),
        array('wp-element', 'wp-components', 'wp-api-fetch', 'wp-i18n'),
        filemtime(plugin_dir_path(__FILE__) . '../js/build/story-manager.js'),
        true
    );

    wp_enqueue_style('wp-components');

    // Add these lines
    wp_enqueue_script('jquery-ui-sortable');
    wp_enqueue_script('story-manager-js', plugin_dir_url(__FILE__) . '../js/story-manager.js', array('jquery', 'jquery-ui-sortable'), '1.0', true);
    wp_localize_script('story-manager-js', 'storyManagerData', array(
        'ajaxurl' => admin_url('admin-ajax.php'),
        'nonce' => wp_create_nonce('story_manager_nonce')
    ));
}
add_action('admin_enqueue_scripts', 'enqueue_story_manager_scripts', 10, 1);

// Render the Story Manager page
function render_story_manager_page() {
    ?>
    <div class="wrap">
        <h1><?php echo esc_html(__('Story Manager', 'story-builder')); ?></h1>
        <select id="storyline-selector">
            <option value=""><?php _e('Select a Storyline', 'story-builder'); ?></option>
            <?php
            $storylines = get_terms(array('taxonomy' => 'storyline', 'hide_empty' => false));
            foreach ($storylines as $storyline) {
                echo '<option value="' . esc_attr($storyline->term_id) . '">' . esc_html($storyline->name) . '</option>';
            }
            ?>
        </select>
        <div id="story-manager-root"></div>
    </div>
    <?php
}

function iasb_get_stories_by_storyline() {
    check_ajax_referer('story_manager_nonce', 'nonce');
    
    $storyline_id = isset($_POST['storyline_id']) ? intval($_POST['storyline_id']) : 0;
    if (!$storyline_id) {
        wp_send_json_error('Invalid storyline ID');
        return;
    }
    $args = array(
        'post_type' => 'story_builder',
        'posts_per_page' => -1,
        'tax_query' => array(
            array(
                'taxonomy' => 'storyline',
                'field' => 'term_id',
                'terms' => $storyline_id,
            ),
        ),
        'orderby' => 'menu_order',
        'order' => 'ASC',
    );
    
    $stories = get_posts($args);
    if (empty($stories)) {
        wp_send_json_success(array()); // Send an empty array if no stories found
        return;
    }
    
    $story_tree = array();
    foreach ($stories as $story) {
        if ($story->post_parent == 0) {
            $node = array(
                'id' => $story->ID,
                'title' => $story->post_title,
                'children' => array(),
            );
            $story_tree[] = $node;
        }
    }
    
    foreach ($stories as $story) {
        foreach ($story_tree as &$node) {
            if ($node['id'] == $story->post_parent) {
                $node['children'][] = array(
                    'id' => $story->ID,
                    'title' => $story->post_title,
                );
                break;
            }
            if (!empty($node['children'])) {
                foreach ($node['children'] as &$child) {
                    if ($child['id'] == $story->post_parent) {
                        $child['children'][] = array(
                            'id' => $story->ID,
                            'title' => $story->post_title,
                        );
                        break;
                    }
                }
            }
        }
    }
    
    wp_send_json_success($story_tree);
}
add_action('wp_ajax_get_stories_by_storyline', 'iasb_get_stories_by_storyline');

function iasb_update_story_order() {
    error_log('iasb_update_story_order function called');
    error_log('POST data: ' . print_r($_POST, true));
    
    if (!check_ajax_referer('story_manager_nonce', 'nonce', false)) {
        error_log('Nonce check failed');
        wp_send_json_error('Invalid nonce');
        return;
    }
    
    $order = isset($_POST['order']) ? $_POST['order'] : '';
    $parent_id = isset($_POST['parent_id']) ? intval($_POST['parent_id']) : 0;
    
    error_log('Received order: ' . print_r($order, true));
    error_log('Parent ID: ' . $parent_id);

    // Decode the JSON string if it's a string
    if (is_string($order)) {
        $order = json_decode(stripslashes($order), true);
    }

    // Ensure $order is an array
    if (!is_array($order)) {
        wp_send_json_error('Invalid order data');
        return;
    }

    $menu_order = 0;
    foreach ($order as $story_id) {
        $menu_order++;
        $story_id = intval($story_id);
        
        // Update menu order
        wp_update_post(array(
            'ID' => $story_id,
            'menu_order' => $menu_order,
            'post_parent' => $parent_id
        ));
        
        // Update parent relationship
        iasb_save_parent_episodes_meta($story_id);
    }

    wp_send_json_success('Story order updated successfully');
}
add_action('wp_ajax_update_story_order', 'iasb_update_story_order');

function update_story_hierarchy($stories, $parent_id = 0, $menu_order = 0) {
    foreach ($stories as $story) {
        $menu_order++;
        $story_id = intval($story);
        
        // Update menu order
        wp_update_post(array(
            'ID' => $story_id,
            'menu_order' => $menu_order
        ));
        
        // Update parent relationship
        iasb_save_parent_episodes_meta($story_id);
        
        // Update children relationships
        $child_ids = array();
        if (!empty($item['children'])) {
            foreach ($item['children'] as $child) {
                $child_ids[] = $child['id'];
            }
        }
        $_POST['iasb_child_episodes'] = $child_ids;
        iasb_save_child_episodes_meta($story_id);
        
        if (!empty($item['children'])) {
            $menu_order = update_story_hierarchy($item['children'], $story_id, $menu_order);
        }
    }
    return $menu_order;
}



function iasb_save_storyline_order() {
    check_ajax_referer('story_manager_nonce', 'nonce');
    
    $order = isset($_POST['order']) ? json_decode(stripslashes($_POST['order']), true) : array();
    
    foreach ($order as $position => $storyline_id) {
        wp_update_term($storyline_id, 'storyline', array(
            'menu_order' => $position
        ));
    }
    
    wp_send_json_success();
}
add_action('wp_ajax_save_storyline_order', 'iasb_save_storyline_order');