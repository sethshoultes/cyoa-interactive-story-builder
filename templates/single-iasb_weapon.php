<?php
// templates/single-iasb_weapon.php
get_header();

if (have_posts()) :
    while (have_posts()) : the_post();
        ?>
        <article id="post-<?php the_ID(); ?>" <?php post_class('story_builder weapon_builder'); ?>>
            <!-- Title -->
            <h1 class="story-story-title"><?php the_title(); ?></h1>
            
            <!-- Featured Image (Optional) -->
            <?php if (has_post_thumbnail()) : ?>
                <div class="weapon-image">
                    <?php the_post_thumbnail('large', array('alt' => get_the_title())); ?>
                </div>
            <?php endif; ?>
            
            <!-- Meta Information -->
            <div class="story-story-meta weapon-meta">
                <?php
                // Display weapon type
                $types = wp_get_post_terms(get_the_ID(), 'weapon_type', array('fields' => 'names'));
                if (!empty($types)) {
                    echo '<span class="weapon-type"><i class="fas fa-crosshairs"></i> <strong>' . __('Type:', 'story-builder') . '</strong> ' . esc_html(implode(', ', $types)) . '</span>';
                }
                ?>
            </div>

            <!-- Content -->
            <div class="story-content weapon-content">
                <?php the_content(); ?>
            </div>
            
            <?php
            // Allow themes/plugins to add additional content after weapon content
            do_action('iasb_after_weapon_content', get_the_ID());
            ?>
        </article>
        <?php
    endwhile;
endif;

get_footer();
?>
