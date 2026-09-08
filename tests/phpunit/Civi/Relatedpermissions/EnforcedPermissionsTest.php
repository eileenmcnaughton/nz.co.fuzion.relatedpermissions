<?php

namespace Civi\Relatedpermissions;

use Civi\Api4\Relatedpermissions;
use Civi\Api4\RelationshipType;

/**
 * Covers the per-relationship-type permission settings: the defaults applied
 * to new relationships, and the values enforced on every relationship of a
 * type set to "Override".
 *
 * @group headless
 */
class EnforcedPermissionsTest extends PermissionsTestBase {

  private const NONE = 0;
  private const EDIT = 1;
  private const VIEW = 2;

  private const MODE_DEFAULT = 0;
  private const MODE_OVERRIDE = 1;

  public function testDefaultIsAppliedWhenTheRelationshipDoesNotSayOtherwise(): void {
    $this->relationshipTypeID = $this->createRelationshipType([
      'permission_a_b' => self::VIEW,
      'permission_a_b_mode' => self::MODE_DEFAULT,
    ]);
    $relationship = $this->createRelationship($this->individualCreate(), $this->individualCreate(), [
      'is_permission_a_b' => '',
    ]);

    $this->assertSame(self::VIEW, $this->getPermission($relationship, 'a_b'));
  }

  public function testDefaultDoesNotOverrideAValueTheRelationshipSupplied(): void {
    $this->relationshipTypeID = $this->createRelationshipType([
      'permission_a_b' => self::VIEW,
      'permission_a_b_mode' => self::MODE_DEFAULT,
    ]);
    $relationship = $this->createRelationship($this->individualCreate(), $this->individualCreate(), [
      'is_permission_a_b' => self::NONE,
    ]);

    $this->assertSame(self::NONE, $this->getPermission($relationship, 'a_b'));
  }

  /**
   * A default is a starting point for new relationships only; changing an
   * existing relationship must not silently reinstate it.
   */
  public function testDefaultIsNotReappliedOnUpdate(): void {
    $this->relationshipTypeID = $this->createRelationshipType([
      'permission_a_b' => self::VIEW,
      'permission_a_b_mode' => self::MODE_DEFAULT,
    ]);
    $relationship = $this->createRelationship($this->individualCreate(), $this->individualCreate(), [
      'is_permission_a_b' => self::NONE,
    ]);
    $this->callAPISuccess('Relationship', 'create', [
      'id' => $relationship,
      'relationship_type_id' => $this->relationshipTypeID,
      'description' => 'touched',
    ]);

    $this->assertSame(self::NONE, $this->getPermission($relationship, 'a_b'));
  }

  public function testOverrideWinsOverTheValueTheRelationshipSupplied(): void {
    $this->relationshipTypeID = $this->createRelationshipType([
      'permission_a_b' => self::EDIT,
      'permission_a_b_mode' => self::MODE_OVERRIDE,
    ]);
    $relationship = $this->createRelationship($this->individualCreate(), $this->individualCreate(), [
      'is_permission_a_b' => self::NONE,
    ]);

    $this->assertSame(self::EDIT, $this->getPermission($relationship, 'a_b'));
  }

  public function testOverrideIsReappliedOnUpdate(): void {
    $this->relationshipTypeID = $this->createRelationshipType([
      'permission_a_b' => self::EDIT,
      'permission_a_b_mode' => self::MODE_OVERRIDE,
    ]);
    $relationship = $this->createRelationship($this->individualCreate(), $this->individualCreate());
    $this->callAPISuccess('Relationship', 'create', [
      'id' => $relationship,
      'relationship_type_id' => $this->relationshipTypeID,
      'is_permission_a_b' => self::NONE,
    ]);

    $this->assertSame(self::EDIT, $this->getPermission($relationship, 'a_b'));
  }

  public function testOverrideOnOneDirectionLeavesTheOtherAlone(): void {
    $this->relationshipTypeID = $this->createRelationshipType([
      'permission_a_b' => self::EDIT,
      'permission_a_b_mode' => self::MODE_OVERRIDE,
    ]);
    $relationship = $this->createRelationship($this->individualCreate(), $this->individualCreate(), [
      'is_permission_a_b' => self::NONE,
      'is_permission_b_a' => self::VIEW,
    ]);

    $this->assertSame(self::VIEW, $this->getPermission($relationship, 'b_a'));
  }

  /**
   * Switching a type to "Override" only affects relationships saved after the
   * change, so there is an action to bring the existing ones into line.
   */
  public function testUpdateEnforcedPermissionsRewritesExistingRelationships(): void {
    $this->relationshipTypeID = $this->createRelationshipType([
      'permission_a_b' => self::EDIT,
      'permission_a_b_mode' => self::MODE_DEFAULT,
    ]);
    $relationship = $this->createRelationship($this->individualCreate(), $this->individualCreate(), [
      'is_permission_a_b' => self::NONE,
    ]);
    $this->assertSame(self::NONE, $this->getPermission($relationship, 'a_b'));

    $this->setRelationshipTypeSettings($this->relationshipTypeID, [
      'permission_a_b_mode' => self::MODE_OVERRIDE,
    ]);
    Relatedpermissions::updateEnforcedPermissions(FALSE)->execute();

    $this->assertSame(self::EDIT, $this->getPermission($relationship, 'a_b'));
  }

  public function testUpdateEnforcedPermissionsLeavesTypesInDefaultModeAlone(): void {
    $this->relationshipTypeID = $this->createRelationshipType([
      'permission_a_b' => self::EDIT,
      'permission_a_b_mode' => self::MODE_DEFAULT,
    ]);
    $relationship = $this->createRelationship($this->individualCreate(), $this->individualCreate(), [
      'is_permission_a_b' => self::NONE,
    ]);

    Relatedpermissions::updateEnforcedPermissions(FALSE)->execute();

    $this->assertSame(self::NONE, $this->getPermission($relationship, 'a_b'));
  }

  private function getPermission(int $relationshipID, string $direction): int {
    return (int) $this->callAPISuccess('Relationship', 'getvalue', [
      'id' => $relationshipID,
      'return' => 'is_permission_' . $direction,
    ]);
  }

  private function setRelationshipTypeSettings(int $relationshipTypeID, array $settings): void {
    $values = [];
    foreach ($settings as $field => $value) {
      $values['relatedpermissions.' . $field] = $value;
    }
    RelationshipType::update(FALSE)
      ->addWhere('id', '=', $relationshipTypeID)
      ->setValues($values)
      ->execute();
    unset(\Civi::$statics['CRM_Relatedpermissions_Utils_Relatedpermissions']);
  }

}
