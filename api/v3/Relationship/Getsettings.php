<?php
use CRM_Relatedpermissions_ExtensionUtil as E;

/**
 * Relationship.GetSettings API specification (optional)
 * This is used for documentation and validation.
 *
 * @param array $spec description of fields supported by this API call
 * @return void
 * @see http://wiki.civicrm.org/confluence/display/CRMDOC/API+Architecture+Standards
 */
function _civicrm_api3_relationship_getsettings_spec(&$spec) {
  // $spec['magicword']['api.required'] = 1;
  $spec['relationship_type_id'] = 1;
}

/**
 * Relationship.GetSettings API
 *
 * @param array $params
 * @return array API result descriptor
 * @see civicrm_api3_create_success
 * @see civicrm_api3_create_error
 * @throws API_Exception
 */
function civicrm_api3_relationship_getsettings($params) {
  $type = CRM_Utils_Array::value('relationship_type_id', $params);
  if ($type) {
    // Allow rel to have a_b part
    $bits = explode("_", $type);
    $type = $bits[0];
  }
  $returnValues = CRM_Relatedpermissions_Utils_Relatedpermissions::getSettings($type);

  return civicrm_api3_create_success($returnValues, $params, 'Relationship', 'getsettings');
}
