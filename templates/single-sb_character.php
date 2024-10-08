<?php
// templates/single-iasb_character.php
get_header();

if (have_posts()) :
    while (have_posts()) : the_post();
        ?>
        <article id="post-<?php the_ID(); ?>" <?php post_class('fc-character'); ?>>
            <h1 class="character-title"><?php the_title(); ?></h1>
            <div class="character-content">
                <?php the_content(); ?>
            </div>
            <?php
            // Display Character Type
            $types = wp_get_post_terms(get_the_ID(), 'character_type', array('fields' => 'names'));
            if (!empty($types)) {
                echo '<p>' . __('Type:', 'story-builder') . ' ' . esc_html(implode(', ', $types)) . '</p>';
            }

            // Display related stories
            $related_stories = get_posts(array(
                'post_type'      => 'story_builder',
                'posts_per_page' => -1,
                'meta_query'     => array(
                    array(
                        'key'     => '_iasb_story_characters',
                        'value'   => '"' . get_the_ID() . '"',
                        'compare' => 'LIKE',
                    ),
                ),
            ));

            if (!empty($related_stories)) {
                echo '<h3>' . __('Stories featuring this character:', 'story-builder') . '</h3>';
                echo '<ul>';
                foreach ($related_stories as $story) {
                    echo '<li><a href="' . get_permalink($story->ID) . '">' . get_the_title($story->ID) . '</a></li>';
                }
                echo '</ul>';
            }
            ?>
        </article>
        <?php
    endwhile;
endif;

get_footer();
