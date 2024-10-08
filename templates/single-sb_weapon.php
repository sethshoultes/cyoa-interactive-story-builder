<?php
// templates/single-sb_weapon.php
get_header();

if (have_posts()) :
    while (have_posts()) : the_post();
        ?>
        <article id="post-<?php the_ID(); ?>" <?php post_class('fc-weapon'); ?>>
            <h1 class="weapon-name"><?php the_title(); ?></h1>
            <div class="weapon-content">
                <?php the_content(); ?>
            </div>
            <?php
            // Display weapon type
            $types = wp_get_post_terms(get_the_ID(), 'weapon_type', array('fields' => 'names'));
            if (!empty($types)) {
                echo '<p>' . __('Type:', 'story-builder') . ' ' . esc_html(implode(', ', $types)) . '</p>';
            }
            ?>
        </article>
        <?php
    endwhile;
endif;

get_footer();
