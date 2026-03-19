<?php

use Civi\Test\HeadlessInterface;
use Civi\Test\HookInterface;

/**
 * FIXME - Add test description.
 *
 * Tips:
 *  - With HookInterface, you may implement CiviCRM hooks directly in the test class.
 *    Simply create corresponding functions (e.g. "hook_civicrm_post(...)" or similar).
 *  - With TransactionalInterface, any data changes made by setUp() or test****() functions will
 *    rollback automatically -- as long as you don't manipulate schema or truncate tables.
 *    If this test needs to manipulate schema or truncate tables, then either:
 *       a. Do all that using setupHeadless() and Civi\Test.
 *       b. Disable TransactionalInterface, and handle all setup/teardown yourself.
 *
 * @group headless
 */
class RelatedpermissionsHookTest extends \PHPUnit\Framework\TestCase implements HeadlessInterface, HookInterface {

  use \Civi\Test\ContactTestTrait;
  use \Civi\Test\Api3TestTrait;

  /**
   * @var int
   * Relationship Type ID
   */
  private $relationshipTypeID;

  public function setUpHeadless() {
    // Civi\Test has many helpers, like install(), uninstall(), sql(), and sqlFile().
    // See: https://docs.civicrm.org/dev/en/latest/testing/phpunit/#civitest
    return \Civi\Test::headless()
      ->installMe(__DIR__)
      ->apply();
  }

  public function setUp(): void {
    $customFields = CRM_Relatedpermissions_Utils_Relatedpermissions::getPermissionFields();
    $params = [
      'name_a_b' => 'Relation 1 for create',
      'name_b_a' => 'Relation 2 for create',
      'description' => 'Testing relationship type',
      'contact_type_a' => 'Individual',
      'contact_type_b' => 'Individual',
      'is_reserved' => 1,
      'is_active' => 1,
    ];
    foreach (array_keys($customFields) as $field) {
      $params[$field] = 0;
    }
    $relationshipType = $this->callAPISuccess('RelationshipType', 'create', $params);
    $this->relationshipTypeID = $relationshipType['id'];
    parent::setUp();
  }

  public function tearDown(): void {
    $this->callAPISuccess('RelationshipType', 'delete', ['id' => $this->relationshipTypeID]);
    parent::tearDown();
  }

  /**
   * Example: Test that a version is returned.
   */
  public function testPermissionedRelationshipsWithNoWhere() {
    CRM_Core_Config::singleton()->userPermissionClass->permissions = [
      'access CiviCRM',
      'view my contact',
    ];
    $loggedInUser = $this->createLoggedInUser();
    $individual  = $this->individualCreate();
    $this->callAPISuccess('Relationship', 'create', [
      'contact_id_a' => $loggedInUser,
      'contact_id_b' => $individual,
      'relationship_type_id' => $this->relationshipTypeID,
      'is_active' => 1,
      'start_date' => date('Y-m-d'),
      'is_permission_a_b' => 0,
      'is_permission_b_a' => 0,
    ]);
    $result = $this->callAPISuccess('contact', 'get', [
      'check_permissions' => 1,
      'return' => 'display_name',
    ]);
    $this->assertEquals(1, $result['count']);
    $this->assertFalse(array_search($individual, CRM_Utils_Array::collect('id', $result['values'])));
    $this->callAPISuccess('System', 'flush', []);
    CRM_Core_Config::singleton()->userPermissionClass->permissions = [
      'access CiviCRM',
    ];
    $result = $this->callAPISuccess('contact', 'get', [
      'check_permissions' => 1,
      'return' => 'display_name',
    ]);
    $this->assertEquals(0, $result['count']);
    $this->assertFalse(array_search($individual, CRM_Utils_Array::collect('id', $result['values'])));
    $this->assertFalse(array_search($loggedInUser, CRM_Utils_Array::collect('id', $result['values'])));
  }

}
