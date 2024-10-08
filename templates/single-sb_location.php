<?php
// templates/single-iasb_location.php
get_header();

if (have_posts()) :
    while (have_posts()) : the_post();
        ?>
        <article id="post-<?php the_ID(); ?>" <?php post_class('fc-location'); ?>>
            <h1 class="location-name"><?php the_title(); ?></h1>
            <div class="location-content">
                <?php the_content(); ?>
            </div>
            <?php
            // Display vehicle type
            $types = wp_get_post_terms(get_the_ID(), 'location_type', array('fields' => 'names'));
            if (!empty($types)) {
                echo '<p>' . __('Type:', 'story-builder') . ' ' . esc_html(implode(', ', $types)) . '</p>';
            }
            ?>
        </article>
        <?php
    endwhile;
endif;

get_footer();
