<?php

/**
 * Relationship.PruneTempTables API specification (optional)
 * This is used for documentation and validation.
 *
 * @param array $spec description of fields supported by this API call
 * @return void
 * @see http://wiki.civicrm.org/confluence/display/CRM/API+Architecture+Standards
 */
function _civicrm_api3_relationship_prune_temp_tables_spec(&$spec) {
  $spec['magicword']['api.required'] = 0;
}

/**
 * Relationship.PruneTempTables API
 *
 * @param array $params
 * @return array API result descriptor
 * @see civicrm_api3_create_success()
 * @see civicrm_api3_create_error()
 * @throws API_Exception
 */
function civicrm_api3_relationship_prune_temp_tables($params) {
  try {
    $dao = CRM_Core_DAO::executeQuery(
      "SELECT TABLE_NAME
       FROM information_schema.TABLES
       WHERE TABLE_SCHEMA = '" . CRM_Core_DAO::getDatabaseName() . "'
     ");

    while ($dao->fetch()) {
      $tables[] = $dao->TABLE_NAME;
    }
    $dao->free();
    if (!empty($tables)) {
      foreach ($tables as $table_name) {
        if (strpos($table_name, 'myrelationships') === 0 || strpos($table_name, 'mysecondaryrelationships') === 0) {
          $dao = CRM_Core_DAO::executeQuery("DROP TABLE IF EXISTS $table_name");
        }
      }
    }
    $return_values = array('status' => 'success');
    return civicrm_api3_create_success($return_values, $params, 'Relationship', 'rp_prune_temp_tables');
  }
  catch (Exception $e) {
    throw new API_Exception('Error clearing temp tables.');
  }
}