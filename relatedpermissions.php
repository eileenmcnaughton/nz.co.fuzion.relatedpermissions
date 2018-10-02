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
 * Implements hook_civicrm_xmlMenu().
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_xmlMenu
 */
function relatedpermissions_civicrm_xmlMenu(&$files) {
  _relatedpermissions_civix_civicrm_xmlMenu($files);
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
 * Implements hook_civicrm_postInstall().
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_postInstall
 */
function relatedpermissions_civicrm_postInstall() {
  _relatedpermissions_civix_civicrm_postInstall();
}

/**
 * Implements hook_civicrm_uninstall().
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_uninstall
 */
function relatedpermissions_civicrm_uninstall() {
  _relatedpermissions_civix_civicrm_uninstall();
}

/**
 * Implements hook_civicrm_enable().
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_enable
 */
function relatedpermissions_civicrm_enable() {
  _relatedpermissions_civix_civicrm_enable();
}

/**
 * Implements hook_civicrm_disable().
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_disable
 */
function relatedpermissions_civicrm_disable() {
  _relatedpermissions_civix_civicrm_disable();
}

/**
 * Implements hook_civicrm_upgrade().
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_upgrade
 */
function relatedpermissions_civicrm_upgrade($op, CRM_Queue_Queue $queue = NULL) {
  return _relatedpermissions_civix_civicrm_upgrade($op, $queue);
}

/**
 * Implements hook_civicrm_managed().
 *
 * Generate a list of entities to create/deactivate/delete when this module
 * is installed, disabled, uninstalled.
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_managed
 */
function relatedpermissions_civicrm_managed(&$entities) {
  _relatedpermissions_civix_civicrm_managed($entities);
}

/**
 * Implements hook_civicrm_caseTypes().
 *
 * Generate a list of case-types.
 *
 * Note: This hook only runs in CiviCRM 4.4+.
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_caseTypes
 */
function relatedpermissions_civicrm_caseTypes(&$caseTypes) {
  _relatedpermissions_civix_civicrm_caseTypes($caseTypes);
}

/**
 * Implements hook_civicrm_angularModules().
 *
 * Generate a list of Angular modules.
 *
 * Note: This hook only runs in CiviCRM 4.5+. It may
 * use features only available in v4.6+.
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_angularModules
 */
function relatedpermissions_civicrm_angularModules(&$angularModules) {
  _relatedpermissions_civix_civicrm_angularModules($angularModules);
}

/**
 * Implements hook_civicrm_alterSettingsFolders().
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_alterSettingsFolders
 */
function relatedpermissions_civicrm_alterSettingsFolders(&$metaDataFolders = NULL) {
  _relatedpermissions_civix_civicrm_alterSettingsFolders($metaDataFolders);
}

/**
 * Implements hook_civicrm_entityTypes().
 *
 * Declare entity types provided by this module.
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_entityTypes
 */
function relatedpermissions_civicrm_entityTypes(&$entityTypes) {
  _relatedpermissions_civix_civicrm_entityTypes($entityTypes);
}

// Related Perms stuff

/**
 * Implements hook_civicrm_aclWhereClause().
 *
 * Implement WHERE Clause - we find the contacts for whom this contact has permission and
 * specifically give permission to them
 */
function relatedpermissions_civicrm_aclWhereClause($type, &$tables, &$whereTables, &$contactID, &$where) {
  if (!$contactID) {
    return;
  }

  if (!CRM_Core_Permission::check('edit all contacts')) {
    $tmpTableName = _relatedpermissions_get_permissionedtable($contactID, $type);

    $tables['$tmpTableName'] = $whereTables['$tmpTableName'] =
      " LEFT JOIN $tmpTableName permrelationships
     ON (contact_a.id = permrelationships.contact_id)";
    if (empty($where)) {
      $where = " permrelationships.contact_id IS NOT NULL ";
    }
    else {
      $where = '(' . $where . " OR permrelationships.contact_id IS NOT NULL " . ')';
    }
  }
}
/**
 * Create temporary table of all permissioned contacts.
 * If the contacts are organisations then we want all contacts they have permission
 * over. Note that in order to avoid ORs & unindexed fields in the ON clause we use several queries
 */
function _relatedpermissions_get_permissionedtable($contactID, $type) {
  static $tempTables = array();
  $dateKey = date('dhis');
  if (!empty($tempTables[$contactID][$type])) {
    return $tempTables[$contactID][$type]['permissioned_contacts'];
  }
  else {
    $tmpTableName = 'my_relationships_' . $contactID . '_' . rand(10000, 100000);
    $sql = "CREATE TEMPORARY TABLE $tmpTableName (
     `contact_id` INT(10) NOT NULL,
     PRIMARY KEY (`contact_id`)
    )";

    CRM_Core_DAO::executeQuery($sql);
    $tmpTableSecondaryContacts = 'my_secondary_relationships' . $dateKey . rand(10000, 100000);
    $sql = "CREATE TEMPORARY TABLE $tmpTableSecondaryContacts (
     `contact_id` INT(10) NOT NULL,
     PRIMARY KEY (`contact_id`),
     `contact_type` VARCHAR(50) NULL DEFAULT NULL
    )";

    CRM_Core_DAO::executeQuery($sql);
  }
  $tempTables[$contactID][$type]['permissioned_contacts'] = $tmpTableName;
  $tempTables[$contactID][$type]['permissioned_secondary_contacts'] = $tmpTableSecondaryContacts;

  $now = date('Y-m-d');

  // Ideally would use CRM_Contact_BAO_Relationship::VIEW and CRM_Contact_BAO_Relationship::EDIT
  // but that makes this extension dependent on a recent core release,
  // so set these here to have the same values
  $CRM_Contact_BAO_Relationship_EDIT = 1;
  $CRM_Contact_BAO_Relationship_VIEW = 2;

  // Determine the permission clause from the access type requested
  if ($type == CRM_Core_Permission::VIEW) {
    $permissionClause = " IN ( $CRM_Contact_BAO_Relationship_EDIT , $CRM_Contact_BAO_Relationship_VIEW ) ";
  }
  else {
    $permissionClause = " = $CRM_Contact_BAO_Relationship_EDIT ";
  }

  $sql = "INSERT INTO $tmpTableName
    SELECT DISTINCT contact_id_a FROM civicrm_relationship
    WHERE contact_id_b = $contactID
    AND is_active = 1
    AND (start_date IS NULL OR start_date <= '{$now}' )
    AND (end_date IS NULL OR end_date >= '{$now}')
    AND is_permission_b_a $permissionClause
  ";

  CRM_Core_DAO::executeQuery($sql);

  $sql = "REPLACE INTO $tmpTableName
    SELECT contact_id_b FROM civicrm_relationship
    WHERE contact_id_a = $contactID
    AND is_active = 1
    AND (start_date IS NULL OR start_date <= '{$now}' )
    AND (end_date IS NULL OR end_date >= '{$now}')
    AND is_permission_a_b $permissionClause
  ";

  CRM_Core_DAO::executeQuery($sql);
  /*
   * Next we generate a table of the permissioned contacts permissioned contacts for Orgs & Households
   */

  calculateInheritedPermissions($tmpTableSecondaryContacts, $tmpTableName, $now, $permissionClause);

  $sql = "REPLACE INTO $tmpTableName
    SELECT contact_id FROM $tmpTableSecondaryContacts";
  CRM_Core_DAO::executeQuery($sql);
  try {
    $secondDegreePerms = civicrm_api3('setting', 'getvalue', array('version' => 3, 'name' => 'secondDegRelPermissions', 'group' => 'core'));
  }
  catch (Exception $e) {
    $secondDegreePerms = 0;
  }

  if ($secondDegreePerms) {
    $continue = 1;
    while ($continue > 0) {
      calculateInheritedPermissions($tmpTableSecondaryContacts, $tmpTableName, $now, $permissionClause);
      $newPotentialPermissionInheritingContacts = CRM_Core_DAO::singleValueQuery("
     SELECT count(*) FROM $tmpTableSecondaryContacts s
     LEFT JOIN $tmpTableName m ON s.contact_id = m.contact_id
     WHERE m.contact_id IS NULL AND s.contact_type IN ('Organization', 'Household')");
      $sql = "REPLACE INTO $tmpTableName
      SELECT contact_id FROM $tmpTableSecondaryContacts
    ";

      CRM_Core_DAO::executeQuery($sql);
      //keep going as long as we are adding
      //new contacts to our table
      $continue = $newPotentialPermissionInheritingContacts;
    }
  }
  return $tmpTableName;
}

/**
 * @param $tmpTableSecondaryContacts
 * @param $tmpTableName
 * @param $now
 */
function calculateInheritedPermissions($tmpTableSecondaryContacts, $tmpTableName, $now, $permissionClause) {
  $sql = "REPLACE INTO $tmpTableSecondaryContacts
    SELECT DISTINCT contact_id_b, contact_b.contact_type
    FROM $tmpTableName tmp
    LEFT JOIN civicrm_relationship r  ON tmp.contact_id = r.contact_id_a
    INNER JOIN civicrm_contact c ON c.id = r.contact_id_a AND c.contact_type IN ('Household', 'Organization')
    INNER JOIN civicrm_contact contact_b ON contact_b.id = r.contact_id_b
    WHERE
    r.is_active = 1
    AND (start_date IS NULL OR start_date <= '{$now}' )
    AND (end_date IS NULL OR end_date >= '{$now}')
    AND is_permission_a_b $permissionClause
    AND c.is_deleted = 0
  ";

  CRM_Core_DAO::executeQuery($sql);

  $sql = "REPLACE INTO $tmpTableSecondaryContacts
    SELECT contact_id_a, contact_b.contact_type
    FROM $tmpTableName tmp
    LEFT JOIN civicrm_relationship r ON tmp.contact_id = r.contact_id_b
    INNER JOIN civicrm_contact c ON c.id = r.contact_id_b AND c.contact_type IN ('Household', 'Organization')
    INNER JOIN civicrm_contact contact_b ON contact_b.id = r.contact_id_b
    WHERE
    r.is_active = 1
    AND (start_date IS NULL OR start_date <= '{$now}' )
    AND (end_date IS NULL OR end_date >= '{$now}')
    AND is_permission_b_a $permissionClause
    AND c.is_deleted = 0
  ";

  CRM_Core_DAO::executeQuery($sql);
}

function relatedpermissions_civicrm_buildForm($formName, &$form) {
  if ($formName == 'CRM_Contact_Form_Relationship') {
    CRM_Core_Resources::singleton()->addScriptFile(E::LONG_NAME, 'js/set_permissions.js');
  }
}

/**
 * Set permissions if required
 * @param unknown $a
 * @param unknown $b
 */
function relatedpermissions_civicrm_pre($op, $entity, $objectID, &$entityArray) {
  if ($entity != 'Relationship' || $op == 'delete' || empty($entityArray['relationship_type_id'])) {
    return;
  }
  $relationshipType = explode('_', $entityArray['relationship_type_id']);
  // dpm($entityArray, "entity array");
  $permissionSettings = CRM_Relatedpermissions_Utils_Relatedpermissions::getSettings($relationshipType[0]);
  foreach (['a_b', 'b_a'] as $direction) {
    // check mode & value....
    if ($permissionSettings['permission_' . $direction . '_mode']) {
      if ($permissionSettings['permission_' . $direction] != '') {
        // enforce
        $entityArray['is_permission_' . $direction] = $permissionSettings['permission_' . $direction];
      }
    }
    else {
      if (isset($permissionSettings['permission_' . $direction]) && $entityArray['is_permission_' . $direction] == '') {
        // default
        $entityArray['is_permission_' . $direction] = $permissionSettings['permission_' . $direction];
      }
    }
  }
}

// --- Functions below this ship commented out. Uncomment as required. ---

/**
 * Implements hook_civicrm_preProcess().
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_preProcess
 *
function relatedpermissions_civicrm_preProcess($formName, &$form) {

} // */

/**
 * Implements hook_civicrm_navigationMenu().
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_navigationMenu
 *
function relatedpermissions_civicrm_navigationMenu(&$menu) {
  _relatedpermissions_civix_insert_navigation_menu($menu, 'Mailings', array(
    'label' => E::ts('New subliminal message'),
    'name' => 'mailing_subliminal_message',
    'url' => 'civicrm/mailing/subliminal',
    'permission' => 'access CiviMail',
    'operator' => 'OR',
    'separator' => 0,
  ));
  _relatedpermissions_civix_navigationMenu($menu);
} // */
