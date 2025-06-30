=== AMFM Tools ===
Contributors: Adrian T. Saycon
Tags: custom, tools, acf, optimization, text, performance
Requires at least: 5.0
Tested up to: 6.5
Stable tag: 1.0.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

A comprehensive WordPress plugin providing ACF helpers, performance optimization, and text utilities for the AMFM website.

== Description ==

AMFM Tools is a custom WordPress plugin designed to enhance functionality with three main components:

1. **ACF Helper** - Advanced Custom Fields integration and cookie management
2. **Optimization** - Performance improvements for Gravity Forms and other assets
3. **Text** - Text manipulation utilities and shortcodes

== Features ==

= ACF Helper =
* Automatically sets ACF field values to cookies for frontend access
* Manages `amfm_keywords` and `amfm_other_keywords` fields
* Converts comma-separated values to arrays
* Sets cookies with 1-hour expiration
* Includes ACF availability checking

= Optimization =
* Conditionally loads Gravity Forms assets only on pages that need them
* Prevents Gravity Forms conflicts with other scripts and styles
* Dequeues unnecessary form scripts on non-form pages
* Improves page load performance

= Text Utilities =
* `[limit_words]` shortcode for text truncation
* Supports both ACF field content and direct content
* Customizable word count limits
* Automatic ellipsis addition for truncated text

== Installation ==

1. Upload the plugin files to the `/wp-content/plugins/amfm-tools` directory
2. Activate the plugin through the 'Plugins' menu in WordPress
3. The plugin will automatically initialize all components

== Usage ==

= ACF Helper =
The ACF Helper automatically runs on all posts and pages. It looks for:
* `amfm_keywords` - ACF text field with comma-separated keywords
* `amfm_other_keywords` - ACF text field with additional keywords

These are automatically converted to JavaScript-accessible cookies.

= Text Shortcode =
Use the limit_words shortcode to truncate text:

`[limit_words text="description" words="20"]` - Limits ACF field "description" to 20 words
`[limit_words words="15"]Content here[/limit_words]` - Limits direct content to 15 words

= Optimization =
Optimization features run automatically and require no configuration. Gravity Forms assets will only load on pages containing `[gravityform]` shortcodes.

== Requirements ==

* WordPress 5.0 or higher
* Advanced Custom Fields (ACF) plugin (for ACF Helper functionality)
* Gravity Forms (for optimization features)

== Changelog ==

= 1.0.0 =
* Initial release
* ACF Helper functionality
* Gravity Forms optimization
* Text utilities with limit_words shortcode

== Author ==

Adrian T. Saycon
Website: https://adzbyte.com/adz