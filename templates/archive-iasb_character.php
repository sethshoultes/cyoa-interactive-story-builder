<?php
/**
 * Archive Template for iasb_character CPT
 */

get_header(); ?>

<div class="wrap">
    <h1><?php post_type_archive_title(); ?></h1>

    <?php
    // Display taxonomy filters (Character Types)
    $character_types = get_terms(array(
        'taxonomy' => 'character_type',
        'hide_empty' => true,
    ));

    if (!empty($character_types) && !is_wp_error($character_types)) : ?>
        <div class="character-filters">
            <h2><?php _e('Filter by Character Type:', 'story-builder'); ?></h2>
            <ul class="character-type-list">
                <li><a href="<?php echo get_post_type_archive_link('iasb_character'); ?>"><?php _e('All Types', 'story-builder'); ?></a></li>
                <?php foreach ($character_types as $type) : ?>
                    <li><a href="<?php echo esc_url(get_term_link($type)); ?>"><?php echo esc_html($type->name); ?></a></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>

    <?php if (have_posts()) : ?>
        <div class="characters-archive">
            <?php while (have_posts()) : the_post(); ?>
                <div class="character-item">
                    <?php if (has_post_thumbnail()) : ?>
                        <div class="character-thumbnail">
                            <a href="<?php the_permalink(); ?>">
                                <?php the_post_thumbnail('medium'); ?>
                            </a>
                        </div>
                    <?php endif; ?>

                    <div class="character-content">
                        <h2 class="character-title">
                            <a href="<?php the_permalink(); ?>"><?php the_title(); ?></a>
                        </h2>
                        <div class="character-excerpt">
                            <?php the_excerpt(); ?>
                        </div>
                        <div class="character-meta">
                            <?php
                            // Display Character Types
                            $types = wp_get_post_terms(get_the_ID(), 'character_type', array('fields' => 'names'));
                            if (!empty($types)) {
                                echo '<span class="character-type"><strong>' . __('Type:', 'story-builder') . '</strong> ' . esc_html(implode(', ', $types)) . '</span>';
                            }
                            ?>
                        </div>
                        <a href="<?php the_permalink(); ?>" class="read-more"><?php _e('Read More', 'story-builder'); ?></a>
                    </div>
                </div>
            <?php endwhile; ?>

            <!-- Pagination -->
            <div class="pagination">
                <?php
                the_posts_pagination(array(
                    'prev_text'          => __('Previous', 'story-builder'),
                    'next_text'          => __('Next', 'story-builder'),
                    'before_page_number' => '<span class="meta-nav screen-reader-text">' . __('Page', 'story-builder') . ' </span>',
                ));
                ?>
            </div>
        </div>
    <?php else : ?>
        <p><?php _e('No characters found.', 'story-builder'); ?></p>
    <?php endif; ?>
</div>

<?php get_footer(); ?>
