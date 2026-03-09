=== PlugMint Stock Images ===
Contributors: MD.Ridwan
Tags: stock images, unsplash, pexels, pixabay, media library
Requires PHP: 7.4
Stable tag: 1.0.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Search and import free stock images from Unsplash, Pexels, and Pixabay directly into your WordPress Media Library.

== Description ==

PlugMint Stock Images adds a stock photo browser to WordPress so editors can search, import, and use free images without leaving the admin area.

The plugin adds:

* a dedicated `Media > Free Stock Images` screen for browsing and importing images,
* integration with the WordPress media modal on post edit screens,
* support for Unsplash, Pexels, and Pixabay,
* orientation and color filters for image searches,
* direct import into the Media Library with attachment metadata,
* optional Elementor editor support when Elementor is active.

Provider notes:

* Unsplash requires your own API key before the source is enabled.
* Pixabay and Pexels work without a saved key, but you can still add your own keys for reliability and quota control.

Imported attachments store source details in post meta so the original provider and attribution data can be tracked.

== Installation ==

1. Upload the plugin folder to `/wp-content/plugins/`, or install it through the WordPress plugins screen.
2. Activate the plugin through the `Plugins` screen in WordPress.
3. Go to `Settings > Free Stock Images` and save your provider API keys.
4. Open `Media > Free Stock Images` to search and import images.
5. You can also use the stock image tab from the WordPress media modal while editing content.

== Frequently Asked Questions ==

= Do I need API keys for every provider? =

No. Unsplash requires a key. Pixabay and Pexels can run without a saved key, though adding your own keys is recommended.

= Where do I get provider API keys? =

You can create keys from each provider:

* Unsplash: `https://unsplash.com/developers`
* Pexels: `https://www.pexels.com/api/`
* Pixabay: `https://pixabay.com/api/docs/`

= Who can import images? =

Users need the `upload_files` capability to search and import images.

= Where is the settings page? =

The plugin adds `Settings > Free Stock Images` for API key management.

= Does it work with the WordPress media popup? =

Yes. The plugin enhances the media frame so you can search and import images while editing posts and pages.

== Screenshots ==

1. The dedicated `Media > Free Stock Images` browser.
2. Source switching between Unsplash, Pexels, and Pixabay.
3. Search filters for orientation and color.
4. Importing an image directly into the Media Library.
5. The settings page for provider API keys.

== Changelog ==

= 1.0.0 =

* Initial public release.
* Added stock image search and import for Unsplash, Pexels, and Pixabay.
* Added WordPress media modal integration.
* Added dedicated media admin page.
* Added provider API key settings.

== Upgrade Notice ==

= 1.0.0 =

Initial release.
