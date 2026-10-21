# WordPress Posts Widget

A free [XenForo](https://xenforo.com/) add-on that shows posts from your WordPress site in a XenForo widget. Feature specific articles, list the latest posts from chosen categories, and show WooCommerce products with their prices.

## Features

- **WordPress Posts** widget for any widget position in XenForo
- Feature specific posts, pages or products by ID. They show first, in the order you enter them.
- List the latest posts from chosen categories, including WooCommerce product categories
- Featured images in any WordPress image size, or no images at all. Images reserve their space before they load, so lazy loading doesn't shift the page.
- Image alt text comes from the WordPress media library, or the post title when an image has none
- Each post shows its author and date; products show their price instead
- Optional link on the widget title
- Caches the list for a set number of minutes, so WordPress isn't loaded on every page view

## Settings

**Setup → Options → WordPress Posts Widget:**

- **WordPress installation path**: the full filesystem path to your WordPress folder, such as `/home/user/public_html/blog`. It's checked when you save.

**In each widget's options** (Appearance → Widgets):

- **Post IDs**: comma-separated posts, pages or products to show first
- **Category IDs**: comma-separated post or product categories to list from
- **Maximum entries**: how many posts to list from the categories (default 5)
- **Image size**: Thumbnail, Medium (default), Medium large, Large, Full or Don't show images
- **Widget title URL**: a link for the widget title
- **Refresh interval**: minutes to keep the list before asking WordPress again (default 5, 0 always loads fresh posts). Caching needs a cache backend in your XenForo config.

## Developers

- Templates: `widget_kolakube_wp_posts` (output), `widget_def_options_kolakube_wp_posts` (widget options) and `kolakube_wp_posts.less` (styles)
- Markup uses `.wp_post-container`, `.wp_post-thumbnail`, `.wp_post-image`, `.wp_post-content`, `.wp_post-title`, `.wp_post-meta` and `.wp_product-price` classes

## Requirements

- XenForo 2.2 or later
- PHP 7.2 or later
- WordPress installed on the same server as XenForo, since the widget loads WordPress directly from its path
- WooCommerce is optional, for showing products

## Install

1. Download the latest version from the [XenForo resource page](https://xenforo.com/community/resources/wordpress-posts-category-widget.8820/).
2. In your XenForo admin, go to **Add-ons → Install/upgrade from archive** and upload the zip.
3. Enter your WordPress installation path in **Setup → Options → WordPress Posts Widget**.
4. Add the **WordPress Posts** widget in **Appearance → Widgets**.

To work on the code, clone this repository into `src/addons/Kolakube/WpPostsWidget` in a XenForo development install.

## License

MIT. See [LICENSE.txt](LICENSE.txt).
