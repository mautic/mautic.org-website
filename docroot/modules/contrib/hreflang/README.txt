HREFLANG
--------

Search engines use <link rel="alternate" hreflang="x" /> tags to serve the
correct language or regional URL in search results.

The Drupal 8 core Content Translation module adds hreflang tags only to 
translated entity pages. This simple module automatically adds hreflang tags to
all pages.

More info about hreflang can be found at the article "Use hreflang for language
and regional URLs": https://support.google.com/webmasters/answer/189077

A few days after installing this module, you should see a message reading
"Currently, your site has no hreflang tag errors" at Google Webmaster Tools:
https://www.google.com/webmasters/tools/i18n

If for some reason you'd like to modify the hreflang tags on a page, you can do
so by implementing hook_language_switch_links_alter() or
hook_page_attachments_alter() in a site-specific custom module.

To file a bug report, feature request or support request for this module, please
visit the module project page: https://www.drupal.org/project/hreflang
