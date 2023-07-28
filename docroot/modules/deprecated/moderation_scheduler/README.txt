CONTENTS OF THIS FILE
---------------------

* Introduction
* Requirements
* Recommended Modules
* Installation
* Configuration
* Maintainers


INTRODUCTION
------------

Moderation Scheduler gives content editors the ability to schedule nodes to be
published and unpublished at specified dates and times in the future.

Moderation Scheduler provides hooks and events for third-party modules to
interact with the processing during node edit and during cron publishing.


 * For a full description of the module visit:
  https://www.drupal.org/project/moderation_scheduler

 * To submit bug reports and feature suggestions, or to track changes visit:
  https://www.drupal.org/project/issues/moderation_scheduler


REQUIREMENTS
------------

This module requires no modules outside of Drupal core.


RECOMMENDED MODULES
-------------------

The core node and core datetime modules is required to support the project.


INSTALLATION
------------

Install the moderation_scheduler module as you would normally install a contrib
Drupal module. Visit https://www.drupal.org/project/moderation_scheduler for
further information.


CONFIGURATION
--------------

    1. Go to Extend and enable the Moderation Scheduler module.
    2. Go to System >Cron > Moderation Scheduler.
    3. A default field_scheduled_time was created to all node types when the
       module was enabled.
    4. Go to the "Add Content" tab and create a node filling scheduled time
       or edit existing node scheduling.
    5. Go to System > Cron > Moderation Scheduler.
       Run cron to see the result of Moderation Scheduler publish.


MAINTAINERS
-----------


The 8.x branches were created by:

- Alberto Cocchiara (bigbabert) - https://www.drupal.org/user/3591390

as an independent volunteer
