<?php
use CRM_Relatedpermissions_ExtensionUtil as E;

/**
 * Collection of upgrade steps.
 */
class CRM_Relatedpermissions_Upgrader extends CRM_Extension_Upgrader_Base {

  // By convention, functions that look like "function upgrade_NNNN()" are
  // upgrade tasks. They are executed in order (like Drupal's hook_update_N).

  /**
   * Called when the extension is installed to create custom fields
   * on the relationship_type
   * @return [type] [description]
   */
  public function create_custom_fields() {
    $optionValues = civicrm_api3('OptionValue', 'get', [
      'option_group_id' => 'cg_extend_objects',
      'name' => 'civicrm_relationship_type'
    ]);
    if (!$optionValues['count']) {
      civicrm_api3('OptionValue', 'create', [
        'option_group_id' => 'cg_extend_objects',
        'name' => 'civicrm_relationship_type',
        'label' => E::ts('Relationship Type'),
        'value' => 'RelationshipType',
      ]);
    }
    $customGroups = civicrm_api3('CustomGroup', 'get', [
      'extends' => 'RelationshipType',
      'name' => E::SHORT_NAME,
    ]);
    if (!$customGroups['count']) {
      $customGroups = civicrm_api3('CustomGroup', 'create', [
        'extends' => 'RelationshipType',
        'name' => E::SHORT_NAME,
        'title' => E::ts('Related Permissions Settings'),
        'is_active' => 1,
        'is_reserved' => 1,
      ]);
    }
    $customFields = civicrm_api3('CustomField', 'get', [
      'custom_group_id' => $customGroups['id'],
    ]);
    if (!$customFields['count']) {
      civicrm_api3('CustomField', 'create', [
        'custom_group_id' => $customGroups['id'],
        'name' => 'permission_a_b',
        'label' => E::ts('Permission that A has over B'),
        'weight' => 1,
        'data_type' => 'Int',
        'html_type' => 'Radio',
        'option_values' => $this->getPermissionedRelationshipOptions(),
        'is_active' => 1,
      ]);
      civicrm_api3('CustomField', 'create', [
        'custom_group_id' => $customGroups['id'],
        'name' => 'permission_a_b_mode',
        'label' => E::ts('Permission A over B mode'),
        'help_pre' => E::ts("If set to 'Override' this permission will be enforced and cannot be changed for individual relationships."),
        'weight' => 2,
        'data_type' => 'Int',
        'html_type' => 'Radio',
        'required' => 1,
        'default_value' => 0,
        'option_values' => [E::ts('Default'), E::ts('Override')],
        'is_active' => 1,
      ]);
      civicrm_api3('CustomField', 'create', [
        'custom_group_id' => $customGroups['id'],
        'name' => 'permission_b_a',
        'label' => E::ts('Permission that B has over A'),
        'weight' => 3,
        'data_type' => 'Int',
        'html_type' => 'Radio',
        'option_values' => $this->getPermissionedRelationshipOptions(),
        'is_active' => 1,
      ]);
      civicrm_api3('CustomField', 'create', [
        'custom_group_id' => $customGroups['id'],
        'name' => 'permission_b_a_mode',
        'label' => E::ts('Permission B over A mode'),
        'help_pre' => E::ts("If set to 'Override' this permission will be enforced and cannot be changed for individual relationships."),
        'weight' => 4,
        'data_type' => 'Int',
        'html_type' => 'Radio',
        'required' => 1,
        'default_value' => 0,
        'option_values' => [E::ts('Default'), E::ts('Override')],
        'is_active' => 1,
      ]);
    }
  }

  public function install() {
    $this->create_custom_fields();
  }

  public function upgrade_1502() {
    $this->create_custom_fields();
    return TRUE;
  }

  /**
   * See https://github.com/civicrm/civicrm-core/pull/23865
   *
   * @return array
   */
  private function getPermissionedRelationshipOptions() {
    return [
      CRM_Contact_BAO_Relationship::NONE => E::ts('None'),
      CRM_Contact_BAO_Relationship::VIEW => E::ts('View only'),
      CRM_Contact_BAO_Relationship::EDIT => E::ts('View and update')
    ];
  }

}
