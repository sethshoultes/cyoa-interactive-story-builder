<?php   
// Add the Support page to the admin menu
add_action('admin_menu', 'adventurebuildr_add_support_page');

function adventurebuildr_add_support_page() {
    add_submenu_page(
        'edit.php?post_type=story_builder', // Parent slug
        'AdventureBuildr Support', // Page title
        'Support', // Menu title
        'manage_options', // Capability
        'adventurebuildr-support', // Menu slug
        'adventurebuildr_render_support_page' // Callback function
    );
}

// Render the Support page content
function adventurebuildr_render_support_page() {
    ?>
    <div class="wrap">
        <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
        <h2>Available Shortcodes</h2>
        <table class="widefat">
            <thead>
                <tr>
                    <th>Shortcode</th>
                    <th>Description</th>
                    <th>Usage</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td><code>[iasb_resume_reading]</code></td>
                    <td>Adds a resume reading button</td>
                    <td><code>[iasb_resume_reading]</code></td>
                </tr>
                <tr>
                    <td><code>[user_story_name]</code></td>
                    <td>Displays the reader's name</td>
                    <td><code>[user_story_name]</code></td>
                </tr>
                <tr>
                    <td><code>[conditional_content]</code></td>
                    <td>Shows content based on progress</td>
                    <td><code>[conditional_content condition="progress > 50"]Content here[/conditional_content]</code></td>
                </tr>
                <tr>
                    <td><code>[dynamic_content]</code></td>
                    <td>Creates adaptive content</td>
                    <td><code>[dynamic_content type="character" id="123"]</code></td>
                </tr>
            </tbody>
        </table>

        <h2>Technical Details</h2>
        <h3>Custom Post Types</h3>
        <ul>
            <li><strong>story_builder</strong>: Main story post type</li>
            <li><strong>iasb_character</strong>: Character post type</li>
            <li><strong>iasb_location</strong>: Location post type</li>
            <li><strong>iasb_vehicle</strong>: Vehicle post type</li>
            <li><strong>iasb_weapon</strong>: Weapon post type</li>
        </ul>

        <h3>Taxonomies</h3>
        <ul>
            <li><strong>parallel_universe</strong>: For alternate versions of stories</li>
            <li><strong>storyline</strong>: To organize stories into different narratives</li>
            <li><strong>character_type</strong>: To categorize characters</li>
            <li><strong>location_type</strong>: To categorize locations</li>
        </ul>

        <h3>Template Hierarchy</h3>
        <p>The plugin uses custom templates for displaying stories. You can override these in your theme by creating files with the following names:</p>
        <ul>
            <li><code>single-story_builder.php</code></li>
            <li><code>archive-story_builder.php</code></li>
        </ul>

        <h3>Hooks and Filters</h3>
        <p>Developers can extend the plugin functionality using these hooks:</p>
        <ul>
            <li><code>adventurebuildr_before_story_content</code></li>
            <li><code>adventurebuildr_after_story_content</code></li>
            <li><code>adventurebuildr_modify_story_query</code></li>
        </ul>

        <h2>Need More Help?</h2>
        <p>For more detailed information, please refer to our <a href="https://github.com/sethshoultes/cyoa-interactive-story-builder/wiki" target="_blank">online documentation</a> or contact our support team at <a href="mailto:support@adventurebuildr.com">support@adventurebuildr.com</a>.</p>
        <p>Bugs and feature requests should be added to our <a href="https://github.com/sethshoultes/cyoa-interactive-story-builder/issues" target="_blank">Github Issue Tracker</a></p>
    </div>
    <?php
}