<?php
// Create the tracking tables
function iasb_create_tracking_tables() {
    global $wpdb;
    $table_name = "{$wpdb->prefix}iasb_metrics";
    $charset_collate = $wpdb->get_charset_collate();

    $sql = "CREATE TABLE $table_name (
        id bigint(20) NOT NULL AUTO_INCREMENT,
        user_id bigint(20) NOT NULL,
        metric_key varchar(100) NOT NULL,
        metric_value bigint(20) NOT NULL,
        recorded_at datetime DEFAULT CURRENT_TIMESTAMP NOT NULL,
        PRIMARY KEY  (id)
    ) $charset_collate;";
    
    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    dbDelta($sql);
}
register_activation_hook(__FILE__, 'iasb_create_tracking_tables');

// Add the Story Metrics page to the admin menu
function iasb_add_metrics_page() {
    add_menu_page(
        page_title: 'Story Metrics',
        menu_title: 'Story Metrics',
        capability: 'manage_options',
        menu_slug: 'iasb-story-metrics',
        callback: 'iasb_render_metrics_page'
    );
}
add_action('admin_menu', 'iasb_add_metrics_page');

//
function iasb_render_metrics_page() {
    global $wpdb;
    $table_name = $wpdb->prefix . 'iasb_metrics';
    $total_completions = $wpdb->get_var( "SELECT COUNT(*) FROM $table_name WHERE metric_key = 'completion'" );
    echo '<h2>' . __( 'Completion Metrics', 'story-builder' ) . '</h2>';
    echo '<p>' . sprintf( __( 'Total completions: %d', 'story-builder' ), $total_completions ) . '</p>';

    // Fetch and display completions with storyline and story name
    $completions = $wpdb->get_results( "SELECT * FROM $table_name WHERE metric_key = 'completion' ORDER BY recorded_at DESC" );
    if ( $completions ) {
        echo '<table>';
        echo '<tr><th>User</th><th>Storyline</th><th>Story Name</th><th>Completed At</th></tr>';
        foreach ( $completions as $completion ) {
            $user_info = get_userdata( $completion->user_id );
            $user_name = $user_info ? $user_info->display_name : 'Guest';
            $post_id = $completion->metric_value;
            $post_title = get_the_title( $post_id );
            $storylines = wp_get_post_terms( $post_id, 'storyline', array('fields' => 'names') );
            $storyline = !empty($storylines) ? implode(', ', $storylines) : '—';
            $completed_at = date( 'Y-m-d H:i:s', strtotime( $completion->recorded_at ) );
            echo '<tr>';
            echo '<td>' . esc_html( $user_name ) . '</td>';
            echo '<td>' . esc_html( $storyline ) . '</td>';
            echo '<td>' . esc_html( $post_title ) . '</td>';
            echo '<td>' . esc_html( $completed_at ) . '</td>';
            echo '</tr>';
        }
        echo '</table>';
    } else {
        echo '<p>No completions recorded.</p>';
    }
}

function iasb_save_metric($user_id, $metric_key, $metric_value) {
    global $wpdb;
    $table_name = $wpdb->prefix . 'iasb_metrics';
    $wpdb->insert(
        $table_name,
        array(
            'user_id' => $user_id,
            'metric_key' => $metric_key,
            'metric_value' => maybe_serialize($metric_value),
            'recorded_at' => current_time('mysql')
        )
    );
}

// Track time spent per path
function iasb_track_time_spent() {
    if ( is_singular('story_builder') ) {
        ?>
        <script>
        document.addEventListener('DOMContentLoaded', function() {
            var startTime = Date.now();
            window.addEventListener('beforeunload', function() {
                var timeSpent = Date.now() - startTime;
                // Send timeSpent to the server via AJAX
                jQuery.post('<?php echo admin_url('admin-ajax.php'); ?>', {
                    action: 'iasb_record_time_spent',
                    time_spent: timeSpent,
                    post_id: <?php echo get_the_ID(); ?>,
                    nonce: '<?php echo wp_create_nonce('iasb_time_nonce'); ?>'
                });
            });
        });
        </script>
        <?php
    }
}
add_action('wp_footer', 'iasb_track_time_spent');

function iasb_record_time_spent() {
    check_ajax_referer('iasb_time_nonce', 'nonce');
    $user_id = get_current_user_id();
    $post_id = intval($_POST['post_id']);
    $time_spent = intval($_POST['time_spent']);

    // Save or update the time spent in user meta or a custom table
    // ...code to record time_spent...

    wp_send_json_success();
}
add_action('wp_ajax_iasb_record_time_spent', 'iasb_record_time_spent');
add_action('wp_ajax_nopriv_iasb_record_time_spent', 'iasb_record_time_spent');

// Track path popularity
function iasb_track_path_popularity($post_id) {
    $count = get_post_meta($post_id, 'iasb_path_count', true);
    $count = $count ? $count + 1 : 1;
    update_post_meta($post_id, 'iasb_path_count', $count);
}
/*add_action('wp', function() {
    if ( is_singular('story_builder') ) {
        iasb_track_path_popularity(get_the_ID());
    }
});
*/

// Track completion rate
function iasb_check_for_completion($post_id) {
    $is_ending = get_post_meta($post_id, '_iasb_is_ending', true);
    if ( $is_ending == '1' ) {
        $user_id = get_current_user_id();
        // Record completion with post ID
        iasb_save_metric($user_id, 'completion', $post_id);
    }
}
// Hook into the story completion event
add_action('iasb_story_completed', function() {
    if ( is_singular('story_builder') ) {
        iasb_check_for_completion(get_the_ID());
    }
});

// Add a meta box to mark an episode as an ending
function iasb_add_is_ending_meta_box() {
    add_meta_box(
        'iasb_is_ending_meta_box',
        __('Ending Episode', 'story-builder'),
        'iasb_render_is_ending_meta_box',
        'story_builder',
        'side',
        'default'
    );
}
add_action('add_meta_boxes', 'iasb_add_is_ending_meta_box');

// Render the episode ending meta box
function iasb_render_is_ending_meta_box($post) {
    $is_ending = get_post_meta($post->ID, '_iasb_is_ending', true);
    wp_nonce_field('iasb_is_ending_nonce', 'iasb_is_ending_nonce');
    ?>
    <label for="iasb_is_ending">
        <input type="checkbox" name="iasb_is_ending" id="iasb_is_ending" value="1" <?php checked($is_ending, '1'); ?> />
        <?php _e('Mark this episode as an ending.', 'story-builder'); ?>
    </label>
    <?php
}

// Save the episode ending meta box
function iasb_save_is_ending_meta_box($post_id) {
    if ( ! isset( $_POST['iasb_is_ending_nonce'] ) ) {
        return;
    }
    if ( ! wp_verify_nonce( $_POST['iasb_is_ending_nonce'], 'iasb_is_ending_nonce' ) ) {
        return;
    }
    if ( defined('DOING_AUTOSAVE') && DOING_AUTOSAVE ) {
        return;
    }
    if ( 'story_builder' != $_POST['post_type'] ) {
        return;
    }
    if ( ! current_user_can( 'edit_post', $post_id ) ) {
        return;
    }
    $is_ending = isset( $_POST['iasb_is_ending'] ) ? '1' : '0';
    update_post_meta( $post_id, '_iasb_is_ending', $is_ending );
}
add_action( 'save_post', 'iasb_save_is_ending_meta_box' );

// Add Google Analytics
/*function iasb_add_google_analytics() {
    ?>
    <!-- Global site tag (gtag.js) - Google Analytics -->
    <script async src="https://www.googletagmanager.com/gtag/js?id=YOUR_TRACKING_ID"></script>
    <script>
      window.dataLayer = window.dataLayer || [];
      function gtag(){dataLayer.push(arguments);}
      gtag('js', new Date());
      gtag('config', 'YOUR_TRACKING_ID');
    </script>
    <?php
}
add_action('wp_head', 'iasb_add_google_analytics');*/