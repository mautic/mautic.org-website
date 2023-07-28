# Mautic Blog

This module provides custom functionality for the Mautic.org blog.

## RSS Feeds

RSS feeds for this project are powered by the `rest` module and custom rest
resource plugins. The feeds are formatted to the Atom RSS specification.

### Main feed

The main RSS feed is located at `/blog/rss.xml`.

### Category feed

The category feed is located at `/blog/category/[category_name]/rss.xml`. A
category name with spaces can use `-` in place of a space. For example, the
`Marketer Blog` category can be represented as `marketer-blog` in the URL in
place of `[category_name]`.

### Tag feed

The tag feed is located at `/blog/tag/[tag_name]/rss.xml`. A tag name with
spaces can use `-` in place of a space. For example, the `landing page` tag can
be represented as `landing-page` in the URL in place of `[tag_name]`.
