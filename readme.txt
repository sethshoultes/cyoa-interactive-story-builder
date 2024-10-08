=== Interactive Adventure Story Builder ===
Contributors: sethshoultes
Donate link: https://adventurebuildr.com/
Tags: interactive, adventure, story, choose-your-own-adventure, stories
Requires at least: 5.0
Tested up to: 6.2
Stable tag: 1.0
Requires PHP: 7.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

A storytelling platform for creating choose-your-own-adventure style stories. Users can choose their own path through the story, exploring different branches and alternate endings.

== Description ==

The Interactive Adventure Story Builder is a WordPress plugin that provides a storytelling platform for creating choose-your-own-adventure style stories. Users can choose their own path through the story, exploring different branches and alternate endings.

Features:

* Custom post type for Stories with a hierarchical structure for parent-child relationships
* Custom taxonomies for organizing stories: Parallel Universes, Storylines, Story Branches, Alternate Endings
* Custom post types for story elements: Characters, Locations, Vehicles, Weapons
* Custom taxonomies for story element types
* Meta boxes for linking story elements to stories
* Season and Episode tracking for stories
* Story Manager page with a visual tree representation of the story structure
* Shortcode for displaying a "Resume Reading" button
* User progress tracking per universe
* Breadcrumb navigation for stories
* Template overrides for single story pages

== Installation ==

1. Upload the `story-builder` directory to the `/wp-content/plugins/` directory.
2. Activate the plugin through the 'Plugins' menu in WordPress.

== Usage ==

1. Create Parallel Universes, Storylines, Story Branches, and Alternate Endings as needed using the respective taxonomies.
2. Create Characters, Locations, Vehicles, and Weapons using the respective custom post types and assign them to appropriate types using the taxonomies.
3. Create Stories using the Story custom post type. Assign them to Universes, Storylines, Branches, and Endings as applicable. 
4. Use the meta boxes to link the story elements (Characters, Locations, etc.) to the Stories.
5. Set the Season and Episode numbers for each Story using the meta boxes.
6. Use the Story Manager page to visualize the structure of your stories.
7. Use the `[iasb_resume_reading]` shortcode to display a "Resume Reading" button for logged-in users.

== Template Overrides ==

The plugin includes a template override for the single story pages. To customize the look and feel of these pages, create a `single-sb_story.php` file in your theme directory.

== Changelog ==

= 1.0 =
* Initial release

== Frequently Asked Questions ==

= How can I customize the look of the single story pages? =

You can create a `single-sb_story.php` file in your theme directory to override the default template provided by the plugin.

= Can users save their progress in the stories? =

Yes, the plugin tracks user progress per universe for logged-in users. They can resume reading from where they left off.

== Credits ==

This plugin was developed by Seth Shoultes. If you have any questions, suggestions, or feedback, please contact me at [Your Email].