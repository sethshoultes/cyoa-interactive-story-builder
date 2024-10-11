=== CYOA Interactive Adventure Story Builder ===
Contributors: Seth Shoultes
Tags: storytelling, adventure, cyoa, choose-your-own-adventure, interactive, stories, game
Requires at least: 5.0
Tested up to: 6.3
Requires PHP: 7.0
Stable tag: 1.0
License: GPL2
License URI: https://www.gnu.org/licenses/gpl-2.0.html

A storytelling platform for choose-your-own-adventure style stories. Users can choose their own path through the story.

== Description ==

The **CYOA Interactive Adventure Story Builder** is a WordPress plugin that allows you to create interactive, choose-your-own-adventure style stories directly on your WordPress site. Engage your readers by letting them decide the course of the story, making for a unique and personalized reading experience.

**Features:**

- **Custom Post Types:**
  - **Stories:** Create stories with hierarchical parent-child relationships to define different paths.
  - **Characters, Locations, Vehicles, Weapons:** Manage these entities and link them to your stories.
- **Taxonomies:**
  - **Parallel Universes:** Create alternate versions of stories.
  - **Storylines:** Organize stories into different narratives.
  - **Character Types, Location Types, etc.:** Categorize your entities for better organization.
- **Meta Boxes:**
  - **Season and Episode:** Assign seasons and episodes to your stories.
  - **Entity Linking:** Easily link characters, locations, vehicles, and weapons to stories.
- **User Interaction:**
  - **Choices:** Display choices to readers, allowing them to decide the next path.
  - **Next Episode Links:** Automatically generate links to the next episode or season.
  - **Parallel Universe Switching:** Let users explore alternate versions of episodes.
- **User Progress Tracking:**
  - **Save Progress:** Users can save their progress per universe.
  - **Resume Reading Shortcode:** `[iasb_resume_reading]` allows users to resume where they left off.
- **Story Manager:**
  - **D3.js Visualization:** Visualize your story structure in the admin area using D3.js.
- **Breadcrumb Navigation:**
  - **Enhanced Navigation:** Display breadcrumbs to help users navigate through the story.

== Installation ==

1. **Upload Plugin Files:**
   - Upload the `cyoa-interactive-adventure-story-builder` folder to the `/wp-content/plugins/` directory.
   - Alternatively, install the plugin through the WordPress plugins screen directly.
2. **Activate Plugin:**
   - Activate the plugin through the 'Plugins' screen in WordPress.
3. **Configure Plugin:**
   - Navigate to the 'Stories' section in the WordPress admin to start creating your interactive stories.
   - Use the 'Characters', 'Locations', 'Vehicles', and 'Weapons' custom post types to create entities and link them to stories.
   - Use the 'Story Manager' under the 'Stories' menu to visualize and manage your story structure.

== Usage ==

**Creating Stories:**

- **Add New Story:**
  - Go to 'Stories' > 'Add New' to create a new story segment.
  - Use the 'Parent' attribute to create hierarchical relationships between story segments.
- **Assign Taxonomies:**
  - Categorize your stories using 'Storyline', 'Parallel Universe', 'Season', and 'Episode' taxonomies.
- **Link Entities:**
  - In the story editor, link characters, locations, vehicles, and weapons using the provided meta boxes.
- **Set Season and Episode:**
  - Use the 'Season' and 'Episode' meta boxes to organize your story segments chronologically.

**Managing Entities:**

- **Create Entities:**
  - Use the 'Characters', 'Locations', 'Vehicles', and 'Weapons' custom post types to create and manage entities.
- **Assign Types:**
  - Categorize entities using 'Character Type', 'Location Type', 'Vehicle Type', and 'Weapon Type' taxonomies.

**Visualizing Story Structure:**

- **Story Manager:**
  - Navigate to 'Stories' > 'Story Manager' to view a visual representation of your story structure using D3.js.

**User Interaction:**

- **Reading Stories:**
  - Users can read stories and make choices that lead them through different paths.
- **Switching Universes:**
  - If alternate versions exist, users can switch between parallel universes to explore different outcomes.
- **Resume Reading:**
  - Logged-in users can resume their progress using the `[iasb_resume_reading]` shortcode.

**Shortcodes:**

- `[iasb_resume_reading]`: Displays a 'Resume Reading' button for logged-in users to continue where they left off.
- `[user_story_name]`: Displays the logged in user name

**Usage Examples**
Example 1: Display Content Only to Administrators
`[conditional_content role="administrator"]
<p>This content is only visible to administrators.</p>
[/conditional_content]`

Example 2: Display Content Based on Story Progress

Assuming story_id corresponds to the storyline's term ID or post ID as per your implementation.
`[conditional_content story_id="123" season="2" episode="5"]
<p>You have unlocked this special scene because you've reached Season 2, Episode 5!</p>
[/conditional_content]`

Example 3: Combine Role and Story Progress Conditions

`[conditional_content role="subscriber" story_id="123" season="3" episode="10"]
<p>Subscribers who have reached Season 3, Episode 10 can see this exclusive content.</p>
[/conditional_content]`

Explanation:

The content within the shortcode will only be displayed if the user meets all specified conditions.
If multiple attributes are provided, all must be satisfied for the content to render.

`[conditional_content]`:

Attributes:
role: Specify the user role required to view the content.
story_id, season, episode: Define the story progress required.
Example:
`[conditional_content role="subscriber" story_id="123" season="2" episode="4"]
<p>Exclusive content for subscribers who have reached Season 2, Episode 4.</p>
[/conditional_content]`

`[dynamic_content]`:

Attributes:
type: Define the type of content (text, image, link, etc.).
id: Related ID for the content.
class: Additional CSS classes.
title: Title or text content.
target: Link target attribute.
Example:
`[dynamic_content type="image" id="456" class="story-image" title="A mysterious artifact"]`


**Displaying Breadcrumbs and Navigation:**

- The plugin automatically displays breadcrumb navigation and 'Next Episode' links in the story templates.

**Filters**
`// Function to override the user story name with character profile name
function myplugin_override_user_story_name($output, $atts) {
    $user_id = get_current_user_id();
    if ($user_id) {
        // Retrieve the character profile ID associated with the user
        $character_profile_id = get_user_meta($user_id, 'iasb_character_profile_id', true);
        if ($character_profile_id) {
            // Get the character's name from the profile
            $character_name = get_the_title($character_profile_id);
            if ($character_name) {
                $output = esc_html($character_name);
            }
        }
    }
    return $output;
}
add_filter('iasb_user_story_name', 'myplugin_override_user_story_name', 10, 2);`

== Frequently Asked Questions ==

= Can I customize the look and feel of the stories? =

Yes, you can customize the templates by copying the template files from the plugin's `templates` directory into your theme and modifying them as needed.

= Do users need to be logged in to track their progress? =

Yes, user progress tracking is available for logged-in users. This allows users to resume their reading across sessions.

= How do I create alternate universes or storylines? =

Use the 'Parallel Universe' and 'Storyline' taxonomies to categorize your story segments. Create alternate versions by assigning different universes or storylines to similar episodes.

== Screenshots ==

1. **Story Editor:** Meta boxes for linking characters, locations, vehicles, and weapons.
2. **Story Manager:** Visual representation of the story structure using D3.js.
3. **Front-end Story Display:** Users making choices in the story.
4. **Resume Reading Button:** Shortcode output for resuming reading.

== Changelog ==

= 1.0 =
* Initial release of the CYOA Interactive Adventure Story Builder plugin.

== Upgrade Notice ==

= 1.0 =
* First stable release.

== Notes ==

This plugin requires that you have basic knowledge of WordPress custom post types and taxonomies. For advanced customization, you may need to modify template files or write custom code.

== License ==

This plugin is licensed under the GPL2 license. See the [License URI](https://www.gnu.org/licenses/gpl-2.0.html) for details.