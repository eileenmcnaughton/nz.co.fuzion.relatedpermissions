<?php

namespace Civi\Relatedpermissions;

use Civi\API\Exception\UnauthorizedException;
use Civi\Api4\Contact;

/**
 * Covers the APIv4 authorization hooks.
 *
 * The write cases go through an organisation rather than a direct
 * relationship: core grants a directly permissioned relationship by itself, so
 * only an inherited permission shows whether these hooks did anything.
 *
 * @group headless
 */
class ApiAuthorizeTest extends PermissionsTestBase {

  private const EDIT = 1;
  private const VIEW = 2;

  public function testUpdateIsAllowedForAContactPermissionedThroughAnOrganisation(): void {
    $target = $this->individualCreate();
    $user = $this->createUserWithInheritedPermissionOver($target, self::EDIT);
    $this->logIn($user);

    Contact::update(TRUE)
      ->addWhere('id', '=', $target)
      ->addValue('nick_name', 'Updated')
      ->execute();

    $this->assertSame('Updated', Contact::get(FALSE)
      ->addWhere('id', '=', $target)
      ->execute()
      ->single()['nick_name']);
  }

  public function testUpdateIsRefusedWhenTheOrganisationOnlyPassesOnViewPermission(): void {
    $target = $this->individualCreate();
    $user = $this->createUserWithInheritedPermissionOver($target, self::VIEW);
    $this->logIn($user);

    $this->expectException(UnauthorizedException::class);
    Contact::update(TRUE)
      ->addWhere('id', '=', $target)
      ->addValue('nick_name', 'Updated')
      ->execute();
  }

  public function testUpdateIsRefusedForAnUnrelatedContact(): void {
    $target = $this->individualCreate();
    // Permission over somebody else entirely, so the user does have a
    // permissions table -- it just does not contain the target.
    $user = $this->createUserWithInheritedPermissionOver($this->individualCreate(), self::EDIT);
    $this->logIn($user);

    $this->expectException(UnauthorizedException::class);
    Contact::update(TRUE)
      ->addWhere('id', '=', $target)
      ->addValue('nick_name', 'Updated')
      ->execute();
  }

  public function testGetReturnsAPermissionedContact(): void {
    $target = $this->individualCreate();
    $user = $this->individualCreate();
    $this->createRelationship($user, $target, ['is_permission_a_b' => self::VIEW]);
    $this->logIn($user);

    $this->assertCount(1, Contact::get(TRUE)->addWhere('id', '=', $target)->execute());
  }

  public function testGetDoesNotReturnAnUnrelatedContact(): void {
    $target = $this->individualCreate();
    $user = $this->individualCreate();
    $this->createRelationship($user, $this->individualCreate(), ['is_permission_a_b' => self::VIEW]);
    $this->logIn($user);

    $this->assertCount(0, Contact::get(TRUE)->addWhere('id', '=', $target)->execute());
  }

  /**
   * user -> organisation -> target, both hops carrying $permission.
   *
   * The relationships have to exist before the contact logs in: the
   * permissions table is built once per contact per request, and logging in
   * builds it.
   */
  private function createUserWithInheritedPermissionOver(int $target, int $permission): int {
    $user = $this->individualCreate();
    $organisation = $this->organizationCreate();
    $this->createRelationship($user, $organisation, ['is_permission_a_b' => $permission]);
    $this->createRelationship($organisation, $target, ['is_permission_a_b' => $permission]);
    return $user;
  }

}
