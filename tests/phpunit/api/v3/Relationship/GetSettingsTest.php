<?php

use Civi\Test\HeadlessInterface;
use Civi\Test\HookInterface;
use Civi\Test\TransactionalInterface;

/**
 * Relationship.GetSettings API Test Case
 * This is a generic test class implemented with PHPUnit.
 * @group headless
 */
class api_v3_Relationship_GetSettingsTest extends \PHPUnit\Framework\TestCase implements HeadlessInterface, HookInterface, TransactionalInterface {

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

  public function setUp() {
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

  public function tearDown() {
    $this->callAPISuccess('RelationshipType', 'delete', ['id' => $this->relationshipTypeID]);
    parent::tearDown();
  }

  public function testGetSettings() {
    $result = $this->callAPISuccess('Relationship', 'getsettings', ['relationship_type_id' => $this->relationshipTypeID]);
    $this->assertEquals([
      'permission_a_b' => 0,
      'permission_a_b_mode' => 0,
      'permission_b_a' => 0,
      'permission_b_a_mode' => 0,
    ], $result['values']);
  }

  public function testGetSettingsNoRelationshipType() {
    $this->callAPIFailure('Relationship', 'getsettings', []);
  }

}
