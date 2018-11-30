<?php

/**
 * The record will be automatically inserted, updated, or deleted from the
 * database as appropriate. For more details, see "hook_civicrm_managed" at:
 * http://wiki.civicrm.org/confluence/display/CRMDOC/Hook+Reference
 */
return array(
  0 => array (
    'name' => 'Cron:Relationship.prune_temp_tables',
    'entity' => 'Job',
    'update' => 'never',
    'params' => array (
      'version' => 3,
      'name' => 'Drop temporary relationship ACL tables',
      'description' => 'Cleanup temporary tables created by Related Permissions extension',
      'run_frequency' => 'Daily',
      'api_entity' => 'Relationship',
      'api_action' => 'prune_temp_tables',
      'parameters' => '',
    ),
  ),
);
