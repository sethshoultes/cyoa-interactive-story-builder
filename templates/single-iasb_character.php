<?php
// templates/single-iasb_character.php
get_header();

if (have_posts()) :
    while (have_posts()) : the_post();
        ?>
        <article id="post-<?php the_ID(); ?>" <?php post_class('story_builder character_builder'); ?>>
            <!-- Title -->
            <h1 class="story-story-title"><?php the_title(); ?></h1>
            
            <!-- Featured Image (Optional) -->
            <?php if (has_post_thumbnail()) : ?>
                <div class="character-image">
                    <?php the_post_thumbnail('large', array('alt' => get_the_title())); ?>
                </div>
            <?php endif; ?>
            
            <!-- Meta Information -->
            <div class="story-story-meta character-meta">
                <?php
                // Display Character Type
                $types = wp_get_post_terms(get_the_ID(), 'character_type', array('fields' => 'names'));
                if (!empty($types)) {
                    echo '<span class="character-type"><i class="fa-solid fa-user"></i> <strong>' . __('Type:', 'story-builder') . '</strong> ' . esc_html(implode(', ', $types)) . '</span>';
                }
                ?>
            </div>

            <!-- Content -->
            <div class="story-content character-content">
                <?php the_content(); ?>
            </div>
            
            <?php
            // Allow themes/plugins to add additional content after character content
            do_action('iasb_after_character_content', get_the_ID());
            ?>
            
            <?php
            // Display Child Episodes
            iasb_render_child_episodes(get_the_ID());

            // Display Next Episode Link (if no child episodes)
            // This is handled inside iasb_render_child_episodes()
            
            // Display Related Stories
            $current_character_id = get_the_ID();

            $args = array(
                'post_type'      => 'story_builder', // Confirm this is correct
                'posts_per_page' => -1,
                'post_status'    => 'publish',
                'meta_query'     => array(
                    array(
                        'key'     => 'iasb_story_characters',
                        'value'   =>  $current_character_id, // Adjust based on storage format
                        'compare' => 'LIKE',
                    ),
                ),
            );

            $related_stories_query = new WP_Query($args);

            if ($related_stories_query->have_posts()) {
                echo '<h3>' . __('Stories featuring this character:', 'story-builder') . '</h3>';
                echo '<ul>';
                while ($related_stories_query->have_posts()) : $related_stories_query->the_post();
                    echo '<li><a href="' . get_permalink() . '">' . get_the_title() . '</a></li>';
                endwhile;
                echo '</ul>';
                wp_reset_postdata();
            } else {
                // Optional: Display a message if no related stories are found
                // echo '<p>' . __('No related stories found for this character.', 'story-builder') . '</p>';
            }
            ?>
        </article>
        <?php
    endwhile;
endif;

get_footer();
?>
