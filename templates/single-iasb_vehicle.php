<?php
// templates/single-iasb_vehicle.php
get_header();

if (have_posts()) :
    while (have_posts()) : the_post();
        ?>
        <article id="post-<?php the_ID(); ?>" <?php post_class('story_builder vehicle_builder'); ?>>
            <h1 class="story-story-title"><?php the_title(); ?></h1>
            
            <?php if (has_post_thumbnail()) : ?>
                <div class="vehicle-image">
                    <?php the_post_thumbnail('large', array('alt' => get_the_title())); ?>
                </div>
            <?php endif; ?>
            
            <div class="story-story-meta vehicle-meta">
                <?php
                // Display vehicle type
                $types = wp_get_post_terms(get_the_ID(), 'vehicle_type', array('fields' => 'names'));
                if (!empty($types)) {
                    echo '<span class="vehicle-type"><i class="fas fa-car"></i> <strong>' . __('Type:', 'story-builder') . '</strong> ' . esc_html(implode(', ', $types)) . '</span>';
                }
                ?>
            </div>

            <div class="story-content vehicle-content">
                <?php the_content(); ?>
            </div>
            
            <?php
            // Additional vehicle-related content or meta can be added here

            // Optionally, allow themes to inject content
            do_action('iasb_after_vehicle_content', get_the_ID());
            ?>
        </article>
        <?php
    endwhile;
endif;

get_footer();
?>