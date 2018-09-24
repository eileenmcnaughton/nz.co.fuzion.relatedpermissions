<?php
use CRM_Relatedpermissions_ExtensionUtil as E;

class CRM_Relatedpermissions_Utils_Relatedpermissions {

  public static function getSettings($relationshipType) {
    if (!isset(\Civi::$statics[__CLASS__]['permission_settings'])) {
      $fields = CRM_Relatedpermissions_Utils_Relatedpermissions::getPermissionFields();
      $res = civicrm_api3('RelationshipType', 'get', [
        'return' => array_keys($fields),
      ]);
      $permissionSettings = [];
      foreach ($res['values'] as $relType) {
        foreach ($relType as $key => $value) {
          if (CRM_Utils_Array::value($key, $fields)) {
            $permissionSettings[$relType['id']][$fields[$key]['name']] = $value;
          }
        }
      }
      \Civi::$statics[__CLASS__]['permission_settings'] = $permissionSettings;
    }
    if ($relationshipType) {
      return CRM_Utils_Array::value($relationshipType, \Civi::$statics[__CLASS__]['permission_settings']);
    }
    else {
      return \Civi::$statics[__CLASS__]['permission_settings'];
    }
  }

  public static function getPermissionFields() {
    if (!isset(\Civi::$statics[__CLASS__]['custom_fields'])) {
      $res = civicrm_api3('CustomField', 'get', [
        'custom_group_id' => E::SHORT_NAME,
      ]);
      $fields = [];
      foreach ($res['values'] as $field) {
        $fields['custom_' . $field['id']] = $field;
      }
      \Civi::$statics[__CLASS__]['custom_fields'] = $fields;
    }
    return \Civi::$statics[__CLASS__]['custom_fields'];
  }

  /**
   * Given a relationship array, enforce the permissions and return the array with the updated permissions
   *
   * @param array $relationship
   */
  public static function enforcePermissions($relationship) {
    $permissionSettings = CRM_Relatedpermissions_Utils_Relatedpermissions::getSettings($relationship['relationship_type_id']);
    foreach (['a_b', 'b_a'] as $direction) {
      // check mode & value....
      if ($permissionSettings['permission_' . $direction . '_mode']) {
        if ($permissionSettings['permission_' . $direction] != '') {
          // enforce
          $relationship['is_permission_' . $direction] = $permissionSettings['permission_' . $direction];
        }
      }
    }
    return $relationship;
  }

}
