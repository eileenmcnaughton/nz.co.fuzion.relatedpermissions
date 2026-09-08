<?php

namespace Civi\Relatedpermissions;

use Civi\Test\Api3TestTrait;
use Civi\Test\ContactTestTrait;
use Civi\Test\HeadlessInterface;
use Civi\Test\TransactionalInterface;
use PHPUnit\Framework\TestCase;

/**
 * Shared setup for tests covering permissioned relationships.
 */
abstract class PermissionsTestBase extends TestCase implements HeadlessInterface, TransactionalInterface {

  use Api3TestTrait;
  use ContactTestTrait;

  /**
   * A relationship type with no contact-type restriction, so a single type can
   * be used for individual/organisation/household combinations alike.
   *
   * @var int
   */
  protected int $relationshipTypeID;

  public function setUpHeadless(): \Civi\Test\CiviEnvBuilder {
    return \Civi\Test::headless()->installMe(__DIR__)->apply();
  }

  public function setUp(): void {
    parent::setUp();
    $this->relationshipTypeID = $this->createRelationshipType();
    \CRM_Core_Config::singleton()->userPermissionClass->permissions = ['access CiviCRM'];
  }

  public function tearDown(): void {
    \CRM_Core_Config::singleton()->userPermissionClass->permissions = NULL;
    parent::tearDown();
  }

  /**
   * @param array $permissionSettings
   *   Values for the extension's own custom fields, keyed without the
   *   "relatedpermissions." prefix, e.g. ['permission_a_b' => 2].
   */
  protected function createRelationshipType(array $permissionSettings = []): int {
    $values = [];
    foreach ($permissionSettings as $field => $value) {
      $values['relatedpermissions.' . $field] = $value;
    }
    $id = (int) \Civi\Api4\RelationshipType::create(FALSE)
      ->setValues($values + [
        'name_a_b' => uniqid('a_b_'),
        'name_b_a' => uniqid('b_a_'),
        'label_a_b' => 'A to B',
        'label_b_a' => 'B to A',
        'is_active' => TRUE,
      ])
      ->execute()
      ->first()['id'];
    // The settings are read through a static cache that was populated before
    // this type existed.
    unset(\Civi::$statics['CRM_Relatedpermissions_Utils_Relatedpermissions']);
    return $id;
  }

  protected function createRelationship(int $contactA, int $contactB, array $params = []): int {
    return (int) $this->callAPISuccess('Relationship', 'create', $params + [
      'contact_id_a' => $contactA,
      'contact_id_b' => $contactB,
      'relationship_type_id' => $this->relationshipTypeID,
      'is_active' => 1,
      'is_permission_a_b' => 0,
      'is_permission_b_a' => 0,
    ])['id'];
  }

  /**
   * Log an existing contact in.
   *
   * Unlike ContactTestTrait::createLoggedInUser() this does not create the
   * contact, so a test can set up the relationships first. That matters: the
   * permissions table is built once per contact per request and never
   * recalculated, and logging in is itself enough to trigger the first build.
   */
  protected function logIn(int $contactID): void {
    $this->callAPISuccess('UFMatch', 'create', [
      'contact_id' => $contactID,
      'uf_name' => 'user' . $contactID,
      'uf_id' => $contactID,
    ]);
    \CRM_Core_Session::singleton()->set('userID', $contactID);
  }

  /**
   * The contacts $userID may act on, as far as the ACL hooks are concerned.
   *
   * Goes through CRM_ACL_API rather than a contact API call because only this
   * lets the caller ask about CRM_Core_Permission::EDIT; the contact APIs
   * always ask about VIEW. "Permission on self" is skipped so that what comes
   * back is only what the relationships granted.
   */
  protected function getPermittedContactIDs(int $userID, int $type = \CRM_Core_Permission::VIEW): array {
    $tables = $whereTables = [];
    $where = \CRM_ACL_API::whereClause($type, $tables, $whereTables, $userID, FALSE, FALSE, TRUE);
    $from = \CRM_Contact_BAO_Query::fromClause($whereTables);
    $ids = \CRM_Core_DAO::executeQuery("SELECT DISTINCT contact_a.id AS id $from WHERE $where")
      ->fetchAll();
    return array_map('intval', array_column($ids, 'id'));
  }

  protected function assertCanView(int $userID, int $contactID, string $message = ''): void {
    $this->assertContains($contactID, $this->getPermittedContactIDs($userID), $message);
  }

  protected function assertCannotView(int $userID, int $contactID, string $message = ''): void {
    $this->assertNotContains($contactID, $this->getPermittedContactIDs($userID), $message);
  }

}
