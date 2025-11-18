<?php

namespace Civi\Api4\Action\Relatedpermissions;

use Civi\Api4\Relationship;
use Civi\Api4\RelationshipType;

class UpdateEnforcedPermissions extends \Civi\Api4\Generic\AbstractAction {

  public function _run(\Civi\Api4\Generic\Result $result) {
    // Update the relationships with a_b set to "Override"
    $relationshipTypes = RelationshipType::get(FALSE)
      ->addSelect('id', 'relatedpermissions.permission_a_b', 'relatedpermissions.permission_b_a')
      ->addWhere('relatedpermissions.permission_a_b_mode:name', '=', 'Override')
      ->execute();
    foreach ($relationshipTypes as $relationshipType) {
      \Civi::log()->info("Updating {$relationshipType['id']} with permissions a_b: {$relationshipType['relatedpermissions.permission_a_b']}, b_a: {$relationshipType['relatedpermissions.permission_b_a']}");
      Relationship::update(FALSE)
        ->addValue('is_permission_a_b', $relationshipType['relatedpermissions.permission_a_b'])
        ->addValue('is_permission_b_a', $relationshipType['relatedpermissions.permission_b_a'])
        ->addWhere('relationship_type_id', '=', $relationshipType['id'])
        ->execute();
    }

    // Now update the relationships with b_a set to "Override"
    $relationshipTypes = RelationshipType::get(FALSE)
      ->addSelect('id', 'relatedpermissions.permission_a_b', 'relatedpermissions.permission_b_a')
      ->addWhere('relatedpermissions.permission_b_a_mode:name', '=', 'Override')
      ->execute();
    foreach ($relationshipTypes as $relationshipType) {
      \Civi::log()->info("Updating {$relationshipType['id']} with permissions a_b: {$relationshipType['relatedpermissions.permission_a_b']}, b_a: {$relationshipType['relatedpermissions.permission_b_a']}");
      Relationship::update(FALSE)
        ->addValue('is_permission_a_b', $relationshipType['relatedpermissions.permission_a_b'])
        ->addValue('is_permission_b_a', $relationshipType['relatedpermissions.permission_b_a'])
        ->addWhere('relationship_type_id', '=', $relationshipType['id'])
        ->execute();
    }
  }

}
