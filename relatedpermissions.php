<?php

require_once 'relatedpermissions.civix.php';
use CRM_Relatedpermissions_ExtensionUtil as E;

/**
 * Implements hook_civicrm_config().
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_config
 */
function relatedpermissions_civicrm_config(&$config) {
  _relatedpermissions_civix_civicrm_config($config);
}

/**
 * Implements hook_civicrm_install().
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_install
 */
function relatedpermissions_civicrm_install() {
  _relatedpermissions_civix_civicrm_install();
}

/**
 * Implements hook_civicrm_enable().
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_enable
 */
function relatedpermissions_civicrm_enable() {
  _relatedpermissions_civix_civicrm_enable();
}

// Related Perms stuff

function relatedpermissions_civicrm_buildForm($formName, &$form) {
  if ($formName == 'CRM_Contact_Form_Relationship') {
    CRM_Core_Resources::singleton()->addScriptFile(E::LONG_NAME, 'js/set_permissions.js');
  }
}

/**
 * Set permissions if required
 * @param string $op
 * @param string $entity
 * @param int|null $objectID
 * @param array $entityArray
 */
function relatedpermissions_civicrm_pre($op, $entity, $objectID, &$entityArray) {
  if ($entity != 'Relationship' || $op == 'delete' || empty($entityArray['relationship_type_id'])) {
    return;
  }
  $relationshipType = explode('_', $entityArray['relationship_type_id']);
  // dpm($entityArray, "entity array");
  $permissionSettings = CRM_Relatedpermissions_Utils_Relatedpermissions::getSettings($relationshipType[0]) ?? [];
  foreach (['a_b', 'b_a'] as $direction) {
    // check mode & value....
    if (isset($permissionSettings['permission_' . $direction . '_mode']) && $permissionSettings['permission_' . $direction . '_mode']) {
      if ($permissionSettings['permission_' . $direction] != '') {
        // enforce
        $entityArray['is_permission_' . $direction] = $permissionSettings['permission_' . $direction];
      }
    }
    else {
      if (isset($permissionSettings['permission_' . $direction]) &&
        $entityArray['is_permission_' . $direction] == '' &&
        $op == 'create'
      ) {
        // default
        $entityArray['is_permission_' . $direction] = $permissionSettings['permission_' . $direction];
      }
    }
  }
}
