# AdventureBuilder

[AdventureBuildr](https://adventurebuildr.com/?utm_source=github&utm_medium=wiki&utm_campaign=github_wiki_links&utm_id=github_wiki) is an online platform powered by [WordPress](https://wordpress.org/?utm_source=github&utm_medium=wiki&utm_campaign=github_wiki_links&utm_id=github_wiki) and the CYOA Interactive Story Builder plugin. The AdventureBuildr website was created to showcase the power of CYOA Interactive Story Builder, which allows website users to easily create, share, and play text-based adventure games. It provides a user-friendly interface where you can design your own [interactive stories](https://adventurebuildr.com/creating-a-branching-storyline/?utm_source=github&utm_medium=wiki&utm_campaign=github_wiki_links&utm_id=github_wiki), complete with characters, items, and various outcomes based on the choices made by the players. It's a great tool for creative individuals who want to engage their audience with fun and interactive storytelling.

AdventureBuildr can be used in a variety of ways! Here are some more examples:

1. Creative Writing: If you're a writer, you can use AdventureBuildr to draft an interactive version of your story, allowing your readers to make choices and explore different paths within your narrative.

2. Game Development: If you're a game developer, you can use AdventureBuildr to prototype and test the narrative structure of your game before investing time and resources into full-scale development.

3. Interactive Fiction: If you're a fan of choose-your-own-adventure books or interactive fiction, you can use AdventureBuildr to create your own stories and share them with the community.

4. Training and Simulations: AdventureBuildr can be used to create interactive training modules for businesses or organizations. For example, a customer service training game where employees navigate through different scenarios and learn how to handle various customer interactions.

5. Personal Projects: You can also use AdventureBuildr for personal projects, like creating an interactive birthday card or a fun adventure for your friends to play at a party. The possibilities are endless!

## CYOA Interactive Story Builder

A WordPress plugin that allows you to create interactive, [choose-your-own-adventure-style stories](https://adventurebuildr.com/choose-an-adventure-stories/?utm_source=github&utm_medium=wiki&utm_campaign=github_wiki_links&utm_id=github_wiki) directly on your WordPress site. Engage your readers by letting them decide the course of the story, making for a unique and personalized reading experience.

Let's say you're a teacher and you want to create an engaging learning experience for your students. You can use AdventureBuildr to develop an educational text-based adventure game where students must solve puzzles related to your subject matter, like history or science, in order to progress through the story.


You can design a historical adventure where students take on the role of a detective solving mysteries in different time periods. They would need to make choices that impact the storyline, gather clues, and interact with historical figures. Correct decisions will help them unlock new levels and learn more about the era they're exploring. You can then share the game with your students, who can play it on their own devices and enjoy learning in a fun, interactive way.

## Features

### Custom Post Types

- **Stories**: Create stories with hierarchical parent-child relationships to define different paths.
- **Characters, Locations, Vehicles, Weapons**: Manage these entities and link them to your stories.

### Taxonomies

- **Parallel Universes**: Create alternate versions of stories.
- **Storylines**: Organize stories into different narratives.
- **Character Types, Location Types, etc.**: Categorize your entities for better organization.

### Meta Boxes

- **Season and Episode**: Assign seasons and episodes to your stories.
- **Entity Linking**: Easily link characters, locations, vehicles, and weapons to stories.

### User Interaction

- **Choices**: Display choices to readers, allowing them to decide the next path.
- **Next Episode Links**: Automatically generate links to the next episode or season.
- **Parallel Universe Switching**: Let users explore alternate versions of episodes.

### User Progress Tracking

- **Save Progress**: Users can save their progress per universe.
- **Resume Reading Shortcode**: `[iasb_resume_reading]` allows users to resume where they left off.


### Breadcrumb Navigation

- **Enhanced Navigation**: Display breadcrumbs to help users navigate through the story.

## Installation

### Download the Plugin

1. Clone or download this repository to your local machine.

### Upload to WordPress

1. Upload the plugin files to the `/wp-content/plugins/` directory of your WordPress installation.

### Activate the Plugin

1. Go to the 'Plugins' menu in WordPress and activate the CYOA Interactive Adventure Story Builder plugin.

### Configure the Plugin

1. Navigate to the 'Stories' section in the WordPress admin to start creating your interactive stories.
2. Use the 'Characters', 'Locations', 'Vehicles', and 'Weapons' custom post types to create entities and link them to stories.
3. Use the 'Story Manager' under the 'Stories' menu to visualize and manage your story structure.

## Usage

### Creating Stories

1. **Add New Story**:
    - Go to `Stories > Add New` to create a new story segment.
    - Use the Parent attribute to create hierarchical relationships between story segments.

2. **Assign Taxonomies**:
    - Categorize your stories using Storyline, Parallel Universe, Season, and Episode taxonomies.

3. **Link Entities**:
    - In the story editor, link characters, locations, vehicles, and weapons using the provided meta boxes.

4. **Set Season and Episode**:
    - Use the Season and Episode meta boxes to organize your story segments chronologically.

### Managing Entities

1. **Create Entities**:
    - Use the Characters, Locations, Vehicles, and Weapons custom post types to create and manage entities.

2. **Assign Types**:
    - Categorize entities using Character Type, Location Type, Vehicle Type, and Weapon Type taxonomies.

### Visualizing Story Structure

1. **Story Manager**:
    - Navigate to `Stories > Story Manager` to view a visual representation of your story structure using D3.js.

### User Interaction

1. **Reading Stories**:
    - Users can read stories and make choices that lead them through different paths.

2. **Switching Universes**:
    - If alternate versions exist, users can switch between parallel universes to explore different outcomes.

3. **Resume Reading**:
    - Logged-in users can resume their progress using the `[iasb_resume_reading]` shortcode.

### Shortcodes

- `[iasb_resume_reading]`: Displays a 'Resume Reading' button for logged-in users to continue where they left off.
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

```php
// Function to override the user story name with character profile name
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
add_filter('iasb_user_story_name', 'myplugin_override_user_story_name', 10, 2);
```

### Displaying Breadcrumbs and Navigation

- The plugin automatically displays breadcrumb navigation and 'Next Episode' links in the story templates.

## Frequently Asked Questions

### Can I customize the look and feel of the stories?

Yes, you can customize the templates by copying the template files from the plugin's templates directory into your theme and modifying them as needed.

### Can I add more entities to my stories?

In our Wiki, you can learn how to use Advanced Custom Fields (ACF) to [add new entity types](https://github.com/sethshoultes/cyoa-interactive-story-builder/wiki/Tutorial:-Using-ACF-to-Enhance-the-CYOA-Story-Builder-Plugin)—such as Lore, Organizations, and Laws—to your stories created with the CYOA Story Builder plugin.


### Do users need to be logged in to track their progress?

Yes, user progress tracking is available for logged-in users. This allows users to resume their reading across sessions.

### How do I create alternate universes or storylines?

Use the Parallel Universe and Storyline taxonomies to categorize your story segments. Create alternate versions by assigning different universes or storylines to similar episodes.

## Contributing

Contributions are welcome! Please submit a pull request or open an issue to discuss any changes or enhancements.

## License

This plugin is licensed under the GPL2 license. See the LICENSE file for details.

## Screenshots

(Screenshots coming soon)

- **Story Editor**: Meta boxes for linking characters, locations, vehicles, and weapons.
- **Story Manager**: Visual representation of the story structure using D3.js.
- **Front-end Story Display**: Users making choices in the story.
- **Resume Reading Button**: Shortcode output for resuming reading.

## Changelog

### Version 1.0

- Initial release of the CYOA Interactive Adventure Story Builder plugin.

*Note: This plugin requires basic knowledge of WordPress custom post types and taxonomies. For advanced customization, you may need to modify template files or write custom code.*
