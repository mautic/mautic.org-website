
Module: Mautic
Author: jarodriguez <https://www.drupal.org/user/1551452>


Description
===========
Adds the Mautic tracking system to your website.

Requirements
============

* A instante of Mautic or a user account in http://www.mautic.org


Installation
============
Copy the 'mautic' module directory in to your Drupal 'modules'
directory as usual.


Usage
=====
In the settings page enter the URL of your mtc.js script.

All pages will now have the required JavaScript added to the
HTML header can confirm this by viewing the page source from
your browser.

Acquia Personalization integration
=======================
This module can automatically send the user's Personalization data into Mautic **IF** you create the [custom fields](https://docs.mautic.org/en/contacts/manage-custom-fields "custom fields") for the data to be added to your contacts.

Please note that the below machine names (like lift_segments) are the *alias* for the custom field. The label can be anything you like. Add any or all of these fields to get access to the data as part of the Mautic contact:
#### site_url
Adds the current site url to the contact. Very useful for creating links and return urls that are dynamic with a user token instead of hardcoded.
*eg. http s://{contactfield=site_url}/formreturn/thank-you*

#### lift_segments
Adds the user's segments as a comma separated list to the contact. This is updated on each page load, so it reflects that last time the user hit the site. VERY handy for creating Mautic segments so you can initiate actions and campaigns based on Lift's decision engine.
*eg. new_customer,order_in_past_week,from_instagram*

#### lift_account
Adds the lift account id to the user's contact. This may be useful for tracking and testing purposes.
