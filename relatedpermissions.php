<?php

require_once 'relatedpermissions.civix.php';

/**
 * Implementation of hook_civicrm_config
 */
function relatedpermissions_civicrm_config(&$config) {
  dpm("config");
  _relatedpermissions_civix_civicrm_config($config);
}

/**
 * Implementation of hook_civicrm_xmlMenu
 *
 * @param $files array(string)
 */
function relatedpermissions_civicrm_xmlMenu(&$files) {
  _relatedpermissions_civix_civicrm_xmlMenu($files);
}

/**
 * Implementation of hook_civicrm_install
 */
function relatedpermissions_civicrm_install() {
  return _relatedpermissions_civix_civicrm_install();
}

/**
 * Implementation of hook_civicrm_uninstall
 */
function relatedpermissions_civicrm_uninstall() {
  return _relatedpermissions_civix_civicrm_uninstall();
}

/**
 * Implementation of hook_civicrm_enable
 */
function relatedpermissions_civicrm_enable() {
  return _relatedpermissions_civix_civicrm_enable();
}

/**
 * Implementation of hook_civicrm_disable
 */
function relatedpermissions_civicrm_disable() {
  return _relatedpermissions_civix_civicrm_disable();
}

/**
 * Implementation of hook_civicrm_upgrade
 *
 * @param $op string, the type of operation being performed; 'check' or 'enqueue'
 * @param $queue CRM_Queue_Queue, (for 'enqueue') the modifiable list of pending up upgrade tasks
 *
 * @return mixed  based on op. for 'check', returns array(boolean) (TRUE if upgrades are pending)
 *                for 'enqueue', returns void
 */
function relatedpermissions_civicrm_upgrade($op, CRM_Queue_Queue $queue = NULL) {
  return _relatedpermissions_civix_civicrm_upgrade($op, $queue);
}

/**
 * Implementation of hook_civicrm_managed
 *
 * Generate a list of entities to create/deactivate/delete when this module
 * is installed, disabled, uninstalled.
 */
function relatedpermissions_civicrm_managed(&$entities) {
  return _relatedpermissions_civix_civicrm_managed($entities);
}
/*
 * Implement WHERE Clause - we find the contacts for whom this contact has permission and
 * specifically give permission to them
 */
function relatedpermissions_civicrm_aclWhereClause($type, &$tables, &$whereTables, &$contactID, &$where) {
dpm("here");
  if (! $contactID) {
    return;
  }
/*  $relationships = _relatedpermissions_get_permissionedrelatedcontacts( $contactID );
  if(empty($relationships )){
    return;
  }
  */
  if(!empty($where)){
    $clause[] = $where;
  }
  foreach ($relationships as $rel){
  $tables ['civicrm_relationship_perm_a'] = $whereTables ['civicrm_relationship_perm_a'] =
    "LEFT JOIN civicrm_relationship civicrm_relationship_perm_a}
     ON (civicrm_relationship_perm_a.contact_id_a = {$contactID} AND abagroup{$rel['contact_id_b']}.contact_id_b = {$rel['contact_id_b']})
";
  $clause[] = "(abagroup{$rel['contact_id_b']}.start_date IS NULL OR abagroup{$rel['contact_id_b']}.start_date < NOW())
    AND abagroup{$rel['contact_id_b']}.is_active = 1
    AND (abagroup{$rel['contact_id_b']}.end_date IS NULL OR abagroup{$rel['contact_id_b']}.end_date > NOW())
  ";
  }
  dpm($tables);
  dpm($whereTables);
  dpm($where);
  $where = implode(' OR ', $clause);
 // return TRUE;
}

/**
 * Implementation of hook_civicrm_config
 */
function relatedpermissions_civicrm_buildForm($formName, &$form ) {
  dpm("I ran");
}


function _relatedpermissions_get_permissionedrelatedcontacts($contact_id) {
  $params = array(
      'contact_id_a' => $contact_id,
      'version' => 3,
      'relationship_type_id' => array('IN' => array(22,35)),
      'is_active' => 1,
      'sequential' => 1,
      'filters' => array(
          'is_current' => 1,
      )
  );

  $relationships = civicrm_api( 'relationship', 'get', $params );
  if (empty( $relationships ['is_error'] ) && $relationships ['count']) {
    return $relationships ['values'];
  }
}