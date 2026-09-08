<?php

namespace Civi\Relatedpermissions;

/**
 * Covers hook_civicrm_aclWhereClause: which contacts a permissioned
 * relationship actually lets its holder reach.
 *
 * @group headless
 */
class AclWhereClauseTest extends PermissionsTestBase {

  private const NONE = 0;
  private const EDIT = 1;
  private const VIEW = 2;

  public function testPermissionOnAToBIsGrantedToA(): void {
    $user = $this->individualCreate();
    $target = $this->individualCreate();
    $this->createRelationship($user, $target, ['is_permission_a_b' => self::VIEW]);

    $this->assertCanView($user, $target);
  }

  public function testPermissionOnBToAIsGrantedToB(): void {
    $user = $this->individualCreate();
    $target = $this->individualCreate();
    $this->createRelationship($target, $user, ['is_permission_b_a' => self::VIEW]);

    $this->assertCanView($user, $target);
  }

  public function testPermissionIsNotGrantedInTheOtherDirection(): void {
    $user = $this->individualCreate();
    $target = $this->individualCreate();
    // The permission runs from B to A, but the user is A.
    $this->createRelationship($user, $target, ['is_permission_b_a' => self::VIEW]);

    $this->assertCannotView($user, $target);
  }

  public function testUnpermissionedRelationshipGrantsNothing(): void {
    $user = $this->individualCreate();
    $target = $this->individualCreate();
    $this->createRelationship($user, $target, ['is_permission_a_b' => self::NONE]);

    $this->assertCannotView($user, $target);
  }

  public function testViewPermissionDoesNotGrantEdit(): void {
    $user = $this->individualCreate();
    $target = $this->individualCreate();
    $this->createRelationship($user, $target, ['is_permission_a_b' => self::VIEW]);

    $this->assertContains($target, $this->getPermittedContactIDs($user, \CRM_Core_Permission::VIEW));
    $this->assertNotContains($target, $this->getPermittedContactIDs($user, \CRM_Core_Permission::EDIT));
  }

  public function testEditPermissionGrantsViewAsWell(): void {
    $user = $this->individualCreate();
    $target = $this->individualCreate();
    $this->createRelationship($user, $target, ['is_permission_a_b' => self::EDIT]);

    $this->assertContains($target, $this->getPermittedContactIDs($user, \CRM_Core_Permission::EDIT));
    $this->assertContains($target, $this->getPermittedContactIDs($user, \CRM_Core_Permission::VIEW));
  }

  /**
   * The permissions table is cached per contact and per permission type. A
   * cache hit must not leave the check pointed at the table built for the
   * other type, or a view-only relationship would carry edit rights.
   */
  public function testARepeatedEditCheckDoesNotPickUpTheViewTable(): void {
    $user = $this->individualCreate();
    $target = $this->individualCreate();
    $this->createRelationship($user, $target, ['is_permission_a_b' => self::VIEW]);

    $this->assertNotContains($target, $this->getPermittedContactIDs($user, \CRM_Core_Permission::EDIT));
    $this->assertContains($target, $this->getPermittedContactIDs($user, \CRM_Core_Permission::VIEW));
    $this->assertNotContains($target, $this->getPermittedContactIDs($user, \CRM_Core_Permission::EDIT),
      'the second edit check answered from the view table');
  }

  public function testInactiveRelationshipGrantsNothing(): void {
    $user = $this->individualCreate();
    $target = $this->individualCreate();
    $this->createRelationship($user, $target, [
      'is_permission_a_b' => self::VIEW,
      'is_active' => 0,
    ]);

    $this->assertCannotView($user, $target);
  }

  public function testExpiredRelationshipGrantsNothing(): void {
    $user = $this->individualCreate();
    $target = $this->individualCreate();
    $this->createRelationship($user, $target, [
      'is_permission_a_b' => self::VIEW,
      'start_date' => date('Y-m-d', strtotime('-2 years')),
      'end_date' => date('Y-m-d', strtotime('-1 day')),
    ]);

    $this->assertCannotView($user, $target);
  }

  public function testRelationshipThatHasNotStartedGrantsNothing(): void {
    $user = $this->individualCreate();
    $target = $this->individualCreate();
    $this->createRelationship($user, $target, [
      'is_permission_a_b' => self::VIEW,
      'start_date' => date('Y-m-d', strtotime('+1 day')),
    ]);

    $this->assertCannotView($user, $target);
  }

  /**
   * Permission over an organisation carries to everyone that organisation has
   * permission over.
   */
  public function testPermissionIsInheritedThroughAnOrganisation(): void {
    $user = $this->individualCreate();
    $organisation = $this->organizationCreate();
    $target = $this->individualCreate();
    $this->createRelationship($user, $organisation, ['is_permission_a_b' => self::VIEW]);
    $this->createRelationship($organisation, $target, ['is_permission_a_b' => self::VIEW]);

    $this->assertCanView($user, $organisation);
    $this->assertCanView($user, $target);
  }

  public function testPermissionIsInheritedThroughAHousehold(): void {
    $user = $this->individualCreate();
    $household = $this->householdCreate();
    $target = $this->individualCreate();
    $this->createRelationship($user, $household, ['is_permission_a_b' => self::VIEW]);
    $this->createRelationship($household, $target, ['is_permission_a_b' => self::VIEW]);

    $this->assertCanView($user, $target);
  }

  /**
   * Individuals do not pass their permissions on: they can log in, so an
   * inherited permission would have no purpose beyond widening the grant.
   */
  public function testPermissionIsNotInheritedThroughAnIndividual(): void {
    $user = $this->individualCreate();
    $intermediary = $this->individualCreate();
    $target = $this->individualCreate();
    $this->createRelationship($user, $intermediary, ['is_permission_a_b' => self::VIEW]);
    $this->createRelationship($intermediary, $target, ['is_permission_a_b' => self::VIEW]);

    $this->assertCanView($user, $intermediary);
    $this->assertCannotView($user, $target);
  }

  public function testSecondDegreeInheritanceIsOffByDefault(): void {
    [$user, $target] = $this->createChainOfTwoOrganisations();

    $this->assertCannotView($user, $target);
  }

  public function testSecondDegreeInheritanceCanBeEnabled(): void {
    \Civi::settings()->set('secondDegRelPermissions', 1);
    [$user, $target] = $this->createChainOfTwoOrganisations();

    $this->assertCanView($user, $target);
  }

  /**
   * user -> organisation -> organisation -> target, every hop permissioned.
   *
   * @return array{0: int, 1: int}
   *   The logged-in user and the contact two organisations away.
   */
  private function createChainOfTwoOrganisations(): array {
    $user = $this->individualCreate();
    $first = $this->organizationCreate([], 1);
    $second = $this->organizationCreate([], 2);
    $target = $this->individualCreate();
    $this->createRelationship($user, $first, ['is_permission_a_b' => self::VIEW]);
    $this->createRelationship($first, $second, ['is_permission_a_b' => self::VIEW]);
    $this->createRelationship($second, $target, ['is_permission_a_b' => self::VIEW]);
    return [$user, $target];
  }

}
