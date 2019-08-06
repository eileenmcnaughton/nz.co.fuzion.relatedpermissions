<?php
use CRM_Relatedpermissions_ExtensionUtil as E;

/**
 * Collection of upgrade steps.
 */
class CRM_Relatedpermissions_Upgrader extends CRM_Relatedpermissions_Upgrader_Base {

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
        'label' => ts('Relationship Type'),
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
      ]);
    }
    $customFields = civicrm_api3('CustomField', 'get', [
      'custom_group_id' => $customGroups['id'],
    ]);
    if (!$customFields['count']) {
      $newFields[] = civicrm_api3('CustomField', 'create', [
        'custom_group_id' => $customGroups['id'],
        'name' => 'permission_a_b',
        'label' => E::ts('Permission that A has over B'),
        'weight' => 1,
        'data_type' => 'Int',
        'html_type' => 'Radio',
        'option_values' => CRM_Core_SelectValues::getPermissionedRelationshipOptions(),
      ]);
      $newFields[] = civicrm_api3('CustomField', 'create', [
        'custom_group_id' => $customGroups['id'],
        'name' => 'permission_a_b_mode',
        'label' => E::ts('Permission A over B mode'),
        'help_post' => E::ts("If set to 'Override' this permission will be enforced and cannot be changed for individual relationships."),
        'weight' => 2,
        'data_type' => 'Int',
        'html_type' => 'Radio',
        'required' => 1,
        'default_value' => 0,
        'option_values' => [E::ts('Default'), E::ts('Override')],
      ]);
      $newFields[] = civicrm_api3('CustomField', 'create', [
        'custom_group_id' => $customGroups['id'],
        'name' => 'permission_b_a',
        'label' => E::ts('Permission that B has over A'),
        'weight' => 3,
        'data_type' => 'Int',
        'html_type' => 'Radio',
        'option_values' => CRM_Core_SelectValues::getPermissionedRelationshipOptions(),
      ]);
      $newFields[] = civicrm_api3('CustomField', 'create', [
        'custom_group_id' => $customGroups['id'],
        'name' => 'permission_b_a_mode',
        'label' => E::ts('Permission B over A mode'),
        'help_post' => E::ts("If set to 'Override' this permission will be enforced and cannot be changed for individual relationships."),
        'weight' => 4,
        'data_type' => 'Int',
        'html_type' => 'Radio',
        'required' => 1,
        'default_value' => 0,
        'option_values' => [E::ts('Default'), E::ts('Override')],
      ]);
    }
  }

  public function install() {
    $this->create_custom_fields();
  }

  public function upgrade_1501() {
    $this->create_custom_fields();
    return TRUE;
  }

  /**
   * Example: Run an external SQL script when the module is installed.
   *
  public function install() {
    $this->executeSqlFile('sql/myinstall.sql');
  }

  /**
   * Example: Work with entities usually not available during the install step.
   *
   * This method can be used for any post-install tasks. For example, if a step
   * of your installation depends on accessing an entity that is itself
   * created during the installation (e.g., a setting or a managed entity), do
   * so here to avoid order of operation problems.
   *
  public function postInstall() {
    $customFieldId = civicrm_api3('CustomField', 'getvalue', array(
      'return' => array("id"),
      'name' => "customFieldCreatedViaManagedHook",
    ));
    civicrm_api3('Setting', 'create', array(
      'myWeirdFieldSetting' => array('id' => $customFieldId, 'weirdness' => 1),
    ));
  }

  /**
   * Example: Run an external SQL script when the module is uninstalled.
   *
  public function uninstall() {
   $this->executeSqlFile('sql/myuninstall.sql');
  }

  /**
   * Example: Run a simple query when a module is enabled.
   *
  public function enable() {
    CRM_Core_DAO::executeQuery('UPDATE foo SET is_active = 1 WHERE bar = "whiz"');
  }

  /**
   * Example: Run a simple query when a module is disabled.
   *
  public function disable() {
    CRM_Core_DAO::executeQuery('UPDATE foo SET is_active = 0 WHERE bar = "whiz"');
  }

  /**
   * Example: Run a couple simple queries.
   *
   * @return TRUE on success
   * @throws Exception
   *
  public function upgrade_4200() {
    $this->ctx->log->info('Applying update 4200');
    CRM_Core_DAO::executeQuery('UPDATE foo SET bar = "whiz"');
    CRM_Core_DAO::executeQuery('DELETE FROM bang WHERE willy = wonka(2)');
    return TRUE;
  } // */


  /**
   * Example: Run an external SQL script.
   *
   * @return TRUE on success
   * @throws Exception
  public function upgrade_4201() {
    $this->ctx->log->info('Applying update 4201');
    // this path is relative to the extension base dir
    $this->executeSqlFile('sql/upgrade_4201.sql');
    return TRUE;
  } // */


  /**
   * Example: Run a slow upgrade process by breaking it up into smaller chunk.
   *
   * @return TRUE on success
   * @throws Exception
  public function upgrade_4202() {
    $this->ctx->log->info('Planning update 4202'); // PEAR Log interface

    $this->addTask(E::ts('Process first step'), 'processPart1', $arg1, $arg2);
    $this->addTask(E::ts('Process second step'), 'processPart2', $arg3, $arg4);
    $this->addTask(E::ts('Process second step'), 'processPart3', $arg5);
    return TRUE;
  }
  public function processPart1($arg1, $arg2) { sleep(10); return TRUE; }
  public function processPart2($arg3, $arg4) { sleep(10); return TRUE; }
  public function processPart3($arg5) { sleep(10); return TRUE; }
  // */


  /**
   * Example: Run an upgrade with a query that touches many (potentially
   * millions) of records by breaking it up into smaller chunks.
   *
   * @return TRUE on success
   * @throws Exception
  public function upgrade_4203() {
    $this->ctx->log->info('Planning update 4203'); // PEAR Log interface

    $minId = CRM_Core_DAO::singleValueQuery('SELECT coalesce(min(id),0) FROM civicrm_contribution');
    $maxId = CRM_Core_DAO::singleValueQuery('SELECT coalesce(max(id),0) FROM civicrm_contribution');
    for ($startId = $minId; $startId <= $maxId; $startId += self::BATCH_SIZE) {
      $endId = $startId + self::BATCH_SIZE - 1;
      $title = E::ts('Upgrade Batch (%1 => %2)', array(
        1 => $startId,
        2 => $endId,
      ));
      $sql = '
        UPDATE civicrm_contribution SET foobar = whiz(wonky()+wanker)
        WHERE id BETWEEN %1 and %2
      ';
      $params = array(
        1 => array($startId, 'Integer'),
        2 => array($endId, 'Integer'),
      );
      $this->addTask($title, 'executeSql', $sql, $params);
    }
    return TRUE;
  } // */

}
