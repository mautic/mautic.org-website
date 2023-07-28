<?php

/**
 * @file
 * Moderation Scheduler publishes nodes on datetime specified by the user.
 */

/**
 * Hook_views_data()
 */
function moderation_scheduler_views_data() {

  $data = [];

  // Base data.
  $data['node_revision__field_scheduled_time']['table']['group'] = t('Moderation Scheduler Query');

  $data['node_revision__field_scheduled_time']['table']['base'] = [
    'title' => t('Moderation Scheduler Query'),
    'help' => t('Moderation Scheduler Query provide data from content that have field scheduled time not empty.'),
    'query_id' => 'moderation_scheduler_query',
    'plugin_id' => 'moderation_scheduler',
  ];

  $data['node_revision__field_scheduled_time']['table']['entity revision'] = FALSE;

  // Fields.
  $data['node_revision__field_scheduled_time']['title'] = [
    'title' => t('Title'),
    'help' => t('Content title'),
    'field' => [
      'id' => 'standard',
    ],
  ];

  $data['node_revision__field_scheduled_time']['content_type'] = [
    'title' => t('Content Type'),
    'help' => t('Content type'),
    'field' => [
      'id' => 'standard',
    ],
  ];

  $data['node_revision__field_scheduled_time']['revision'] = [
    'title' => t('Revision'),
    'help' => t('Entity Revision (vid)'),
    'field' => [
      'id' => 'numeric',
    ],
  ];

  $data['node_revision__field_scheduled_time']['field_scheduled_time'] = [
    'title' => t('Scheduled time'),
    'help' => t('Content scheduled time'),
    'field' => [
      'id' => 'standard',
    ],
  ];

  $data['node_revision__field_scheduled_time']['author'] = [
    'title' => t('Author'),
    'help' => t('Content author'),
    'field' => [
      'id' => 'standard',

    ],
  ];

  $data['node_revision__field_scheduled_time']['status'] = [
    'title' => t('Status'),
    'help' => t('Content status'),
    'field' => [
      'id' => 'standard',

    ],
  ];

  return $data;

}
