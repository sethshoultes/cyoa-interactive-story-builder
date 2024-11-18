<?php
/**
 * The template for displaying single story posts
 *
 * @package IASB
 */

get_header();

// Add theme support for wide alignments
if (function_exists('wp_body_open')) {
    wp_body_open();
}
?>

<div class="wp-site-blocks">
    <main id="primary" class="site-main">
        <?php
        if (have_posts()) :
            while (have_posts()) : the_post();

                // Get the current post ID and user ID
                $post_id = get_the_ID();
                $user_id = get_current_user_id();
                $character_id = get_post_meta($post_id, 'iasb_character_id', true);
                $state_manager = new IASB_State_Manager($user_id, $post_id, $character_id);

                // Get the universes associated with the current post
                $current_universes = wp_get_post_terms($post_id, 'parallel_universe', array('fields' => 'ids'));
                $current_universe_id = !empty($current_universes) ? $current_universes[0] : 'default_universe';

                // Save user progress if the user is logged in
                if (is_user_logged_in()) {
                    iasb_save_user_story_progress($user_id, $post_id);
                    do_action('HOOK_ACTION_iasb_story_completed', $post_id);
                }

                // Check if a universe switch occurred
                if (isset($_GET['from_universe'])) {
                    $from_universe_id = sanitize_text_field($_GET['from_universe']);
                    setcookie('iasb_previous_universe', $from_universe_id, time() + 3600, COOKIEPATH, COOKIE_DOMAIN);
                }
                ?>
                
                <article id="post-<?php the_ID(); ?>" <?php post_class('story-builder wp-block-post'); ?>>
                    <div class="entry-content wp-block-post-content">
                        <div class="alignwide">
                            <header class="entry-header">
                                <?php the_title('<h1 class="entry-title">', '</h1>'); ?>
                                <div class="story-meta">
                                    <?php do_action('HOOK_ACTION__iasb_breadcrumbs', $post_id); ?>
                                </div>
                            </header>

                            <?php if (has_post_thumbnail()) : ?>
                                <div class="post-thumbnail alignwide">
                                    <?php the_post_thumbnail('full', array(
                                        'class' => 'wp-block-post-featured-image',
                                        'alt' => get_the_title()
                                    )); ?>
                                </div>
                            <?php endif; ?>

                            <div class="story-content">
                                <?php 
                                $content = get_the_content();
                                $processed_content = $state_manager->process_conditional_content($content);
                                echo apply_filters('the_content', $processed_content);
                                ?>
                            </div>

                            <?php
                            // Story completed message
                            do_action('HOOK_ACTION_iasb_story_completed_message');

                            // Previous universe link
                            if (isset($_COOKIE['iasb_previous_universe'])) {
                                $previous_universe_id = sanitize_text_field($_COOKIE['iasb_previous_universe']);
                                if ($previous_universe_id !== $current_universe_id) {
                                    $progress = get_user_meta($user_id, 'story_builder_progress', true);
                                    if ($progress && isset($progress[$previous_universe_id])) {
                                        $previous_story_id = $progress[$previous_universe_id]['story_id'];
                                        $universe_name = ($previous_universe_id === 'default_universe') ? 
                                            __('Default Universe', 'story-builder') : 
                                            get_term($previous_universe_id, 'parallel_universe')->name;
                                        ?>
                                        <div class="return-to-previous-universe wp-block-button">
                                            <a class="wp-block-button__link" href="<?php echo esc_url(get_permalink($previous_story_id)); ?>">
                                                <?php printf(esc_html__('Return to your place in %s', 'story-builder'), esc_html($universe_name)); ?>
                                            </a>
                                        </div>
                                        <?php
                                    }
                                }
                            }

                            // Render story components
                            do_action('HOOK_ACTION_iasb_render_child_episodes', $post_id);
                            do_action('HOOK_ACTION__iasb_render_universes', $post_id);
                            do_action('HOOK_ACTION__iasb_display_user_progress', $user_id);
                            ?>

                            <footer class="entry-footer">
                                <div class="story-meta">
                                    <?php do_action('HOOK_ACTION__iasb_breadcrumbs', $post_id); ?>
                                </div>

                                <div class="story-entities alignwide">
                                    <?php
                                    $entity_actions = array(
                                        'characters', 'locations', 'vehicles', 'weapons',
                                        'items', 'lore', 'organizations', 'technology', 'laws'
                                    );

                                    foreach ($entity_actions as $entity) {
                                        do_action("HOOK_ACTION__iasb_display_story_{$entity}", $post_id);
                                    }
                                    ?>
                                </div>
                            </footer>
                        </div>
                    </div>
                </article>
                <?php
            endwhile;
        endif;
        ?>
    </main>
</div>

<?php
get_footer();