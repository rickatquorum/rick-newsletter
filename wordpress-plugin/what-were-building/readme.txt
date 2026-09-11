=== What We're Building ===
Requires at least: 6.0
Requires PHP: 7.4
Stable tag: 1.0.0
License: GPLv2 or later

Import a GitHub Pages newsletter issue into WordPress as an exact standalone landing page.

== Description ==

This plugin connects to the public GitHub Pages publication for *What We're Building*, lets you choose a product edition and an issue, then:

1. Downloads that HTML page and every local asset it needs (images, logos, audio, theme.js).
2. Rewrites those files so they load from your WordPress uploads folder.
3. Creates a WordPress page with the name you choose.
4. Serves that page as a full landing page — your theme header, footer, and navigation are not used.

== Installation ==

1. Copy the `what-were-building` folder into `wp-content/plugins/` on your WordPress site.
2. In WP Admin, go to Plugins and activate **What We're Building**.
3. Open **What We're Building** in the left-hand admin menu.
4. Confirm the GitHub Pages URL (default: `https://rickatquorum.github.io/rick-newsletter/`) and click **Load issues**.
5. Choose the newsletter, choose the edition, name the page, and click **Create landing page**.

PHP 7.4 or newer is required. The WordPress server must be allowed to make outbound HTTPS requests to GitHub Pages.

== Notes ==

Imported pages are stored under `wp-content/uploads/what-were-building/{page-id}/`. Deleting the WordPress page also deletes those files. Uninstalling the plugin does not delete imported pages.
