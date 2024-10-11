<?php
// templates/archive-iasb_story.php
get_header();
?>

<div class="story-story-archive">
    <h1><?php _e('Stories Archive', 'story-builder'); ?></h1>

    <?php
    // Get all storylines
    $storylines = get_terms(array(
        'taxonomy'   => 'storyline',
        'hide_empty' => false,
    ));

    if ($storylines && !is_wp_error($storylines)) :
        foreach ($storylines as $storyline) :
            echo '<h2>' . esc_html($storyline->name) . '</h2>';

            // Get all universes associated with this storyline
            $universes = get_terms(array(
                'taxonomy'   => 'parallel_universe',
                'hide_empty' => false,
            ));

            if ($universes && !is_wp_error($universes)) :
                foreach ($universes as $universe) :
                    // Check if there are posts in this storyline and universe
                    $posts_in_universe = get_posts(array(
                        'post_type'      => 'story_builder',
                        'posts_per_page' => 1,
                        'tax_query'      => array(
                            'relation' => 'AND',
                            array(
                                'taxonomy' => 'storyline',
                                'field'    => 'term_id',
                                'terms'    => $storyline->term_id,
                            ),
                            array(
                                'taxonomy' => 'parallel_universe',
                                'field'    => 'term_id',
                                'terms'    => $universe->term_id,
                            ),
                        ),
                        'fields'         => 'ids',
                    ));

                    if (!empty($posts_in_universe)) :
                        echo '<h3>' . esc_html($universe->name) . '</h3>';

                        // Get all seasons within this storyline and universe
                        global $wpdb;

                        $seasons = $wpdb->get_col($wpdb->prepare("
                            SELECT DISTINCT pm.meta_value FROM $wpdb->postmeta pm
                            INNER JOIN $wpdb->posts p ON pm.post_id = p.ID
                            INNER JOIN $wpdb->term_relationships tr1 ON p.ID = tr1.object_id
                            INNER JOIN $wpdb->term_taxonomy tt1 ON tr1.term_taxonomy_id = tt1.term_taxonomy_id
                            INNER JOIN $wpdb->term_relationships tr2 ON p.ID = tr2.object_id
                            INNER JOIN $wpdb->term_taxonomy tt2 ON tr2.term_taxonomy_id = tt2.term_taxonomy_id
                            WHERE pm.meta_key = '_iasb_story_builder_season'
                            AND p.post_status = 'publish'
                            AND tt1.taxonomy = 'storyline' AND tt1.term_id = %d
                            AND tt2.taxonomy = 'parallel_universe' AND tt2.term_id = %d
                            ORDER BY pm.meta_value+0 ASC
                        ", $storyline->term_id, $universe->term_id));

                        if ($seasons) :
                            echo '<ul class="story-story-list">';
                            foreach ($seasons as $season) :
                                // Get the first episode of this season in this storyline and universe
                                $args = array(
                                    'post_type'      => 'story_builder',
                                    'posts_per_page' => 1,
                                    'meta_query'     => array(
                                        array(
                                            'key'     => '_iasb_story_builder_season',
                                            'value'   => $season,
                                            'compare' => '=',
                                            'type'    => 'NUMERIC',
                                        ),
                                        array(
                                            'key'     => '_iasb_story_builder_episode',
                                            'value'   => 1,
                                            'compare' => '=',
                                            'type'    => 'NUMERIC',
                                        ),
                                    ),
                                    'tax_query'      => array(
                                        'relation' => 'AND',
                                        array(
                                            'taxonomy' => 'storyline',
                                            'field'    => 'term_id',
                                            'terms'    => $storyline->term_id,
                                        ),
                                        array(
                                            'taxonomy' => 'parallel_universe',
                                            'field'    => 'term_id',
                                            'terms'    => $universe->term_id,
                                        ),
                                    ),
                                    'post_status'    => 'publish',
                                );
                                $query = new WP_Query($args);
                                if ($query->have_posts()) :
                                    while ($query->have_posts()) : $query->the_post();
                                        ?>
                                        <li class="story-story-item">
                                            <h4><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a></h4>

                                            <div class="story-story-meta">
                                                <p><?php echo __('Season:', 'story-builder') . ' ' . $season; ?></p>
                                                <?php
                                                // Get the number of episodes in this season, storyline, and universe
                                                $episodes_args = array(
                                                    'post_type'      => 'story_builder',
                                                    'posts_per_page' => -1,
                                                    'meta_query'     => array(
                                                        array(
                                                            'key'     => '_iasb_story_builder_season',
                                                            'value'   => $season,
                                                            'compare' => '=',
                                                            'type'    => 'NUMERIC',
                                                        ),
                                                    ),
                                                    'tax_query'      => array(
                                                        'relation' => 'AND',
                                                        array(
                                                            'taxonomy' => 'storyline',
                                                            'field'    => 'term_id',
                                                            'terms'    => $storyline->term_id,
                                                        ),
                                                        array(
                                                            'taxonomy' => 'parallel_universe',
                                                            'field'    => 'term_id',
                                                            'terms'    => $universe->term_id,
                                                        ),
                                                    ),
                                                    'fields'         => 'ids',
                                                    'post_status'    => 'publish',
                                                );
                                                $episodes_query = new WP_Query($episodes_args);
                                                $episode_count = $episodes_query->found_posts;
                                                wp_reset_postdata();
                                                ?>
                                                <p><?php echo __('Number of Episodes:', 'story-builder') . ' ' . $episode_count; ?></p>
                                            </div>

                                            <p><?php echo wp_trim_words(get_the_content(), 40); ?></p>
                                            <a href="<?php the_permalink(); ?>" class="read-more"><?php _e('Read more', 'story-builder'); ?></a>
                                        </li>
                                        <?php
                                    endwhile;
                                    wp_reset_postdata();
                                endif;
                            endforeach;
                            echo '</ul>';
                        endif;
                    endif;
                endforeach;
            endif;
        endforeach;
    else :
        ?>
        <p><?php _e('No story stories found.', 'story-builder'); ?></p>
    <?php endif; ?>
</div>

<?php get_footer(); ?>
