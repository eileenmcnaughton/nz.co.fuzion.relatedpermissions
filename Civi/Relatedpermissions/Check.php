<?php
namespace Civi\Relatedpermissions;

use Civi\API\Events;
use Civi\Api4\Contact;
use Civi\Api4\Event\AuthorizeRecordEvent;
use Civi\Core\Service\AutoSubscriber;
use CRM_Relatedpermissions_ExtensionUtil as E;

class Check extends AutoSubscriber {

  public static function getSubscribedEvents() {
    return [
      '&hook_civicrm_aclWhereClause' => ['hook_civicrm_aclWhereClause'],
      'civi.api4.authorizeRecord' => ['onApiAuthorizeRecord'],
      'civi.api.authorize' => ['onApiAuthorize'],
    ];
  }

  /**
   * @var string
   */
  private string $tmpTableName;


  /**
   * Create temporary table of all permissioned contacts.
   * If the contacts are organisations then we want all contacts they have permission
   * over. Note that in order to avoid ORs & unindexed fields in the ON clause we use several queries
   *
   * @param int $contactID
   * @param int $type (eg. CRM_Core_Permission::VIEW, CRM_Core_Permission::EDIT)
   *
   * @return mixed|void
   * @throws \CRM_Core_Exception
   */
  private function buildPermissionsTable(int $contactID, int $type) {
    static $tempTables = [];
    $dateKey = date('dhis');
    if (!empty($tempTables[$contactID][$type])) {
      return $tempTables[$contactID][$type]['permissioned_contacts'];
    }
    else {
      $this->tmpTableName = 'my_relationships_' . $contactID . '_' . rand(10000, 100000);
      $sql = "CREATE TEMPORARY TABLE $this->tmpTableName (
     `contact_id` INT(10) NOT NULL,
     PRIMARY KEY (`contact_id`)
    )";

      \CRM_Core_DAO::executeQuery($sql);
      $tmpTableSecondaryContacts = 'my_secondary_relationships' . $dateKey . rand(10000, 100000);
      $sql = "CREATE TEMPORARY TABLE $tmpTableSecondaryContacts (
     `contact_id` INT(10) NOT NULL,
     PRIMARY KEY (`contact_id`),
     `contact_type` VARCHAR(50) NULL DEFAULT NULL
    )";

      \CRM_Core_DAO::executeQuery($sql);
    }
    $tempTables[$contactID][$type]['permissioned_contacts'] = $this->tmpTableName;
    $tempTables[$contactID][$type]['permissioned_secondary_contacts'] = $tmpTableSecondaryContacts;

    $now = date('Y-m-d');

    // Ideally would use CRM_Contact_BAO_Relationship::VIEW and CRM_Contact_BAO_Relationship::EDIT
    // but that makes this extension dependent on a recent core release,
    // so set these here to have the same values
    $CRM_Contact_BAO_Relationship_EDIT = 1;
    $CRM_Contact_BAO_Relationship_VIEW = 2;

    // Determine the permission clause from the access type requested
    if ($type == \CRM_Core_Permission::VIEW) {
      $permissionClause = " IN ( $CRM_Contact_BAO_Relationship_EDIT , $CRM_Contact_BAO_Relationship_VIEW ) ";
    }
    else {
      $permissionClause = " = $CRM_Contact_BAO_Relationship_EDIT ";
    }

    $sql = "INSERT INTO $this->tmpTableName
    SELECT DISTINCT contact_id_a FROM civicrm_relationship
    WHERE contact_id_b = $contactID
    AND is_active = 1
    AND (start_date IS NULL OR start_date <= '{$now}' )
    AND (end_date IS NULL OR end_date >= '{$now}')
    AND is_permission_b_a $permissionClause
  ";

    \CRM_Core_DAO::executeQuery($sql);

    $sql = "REPLACE INTO $this->tmpTableName
    SELECT contact_id_b FROM civicrm_relationship
    WHERE contact_id_a = $contactID
    AND is_active = 1
    AND (start_date IS NULL OR start_date <= '{$now}' )
    AND (end_date IS NULL OR end_date >= '{$now}')
    AND is_permission_a_b $permissionClause
  ";

    \CRM_Core_DAO::executeQuery($sql);

    // Next we generate a table of the permissioned contacts permissioned contacts for Orgs & Households
    $this->calculateInheritedPermissions($tmpTableSecondaryContacts, $now, $permissionClause);

    $sql = "REPLACE INTO $this->tmpTableName
    SELECT contact_id FROM $tmpTableSecondaryContacts";
    \CRM_Core_DAO::executeQuery($sql);
    $secondDegreePerms = \Civi::settings()->get('secondDegRelPermissions');

    if ($secondDegreePerms) {
      $continue = 1;
      while ($continue > 0) {
        $this->calculateInheritedPermissions($tmpTableSecondaryContacts, $now, $permissionClause);
        $newPotentialPermissionInheritingContacts = \CRM_Core_DAO::singleValueQuery("
     SELECT count(*) FROM $tmpTableSecondaryContacts s
     LEFT JOIN $this->tmpTableName m ON s.contact_id = m.contact_id
     WHERE m.contact_id IS NULL AND s.contact_type IN ('Organization', 'Household')");
        $sql = "REPLACE INTO $this->tmpTableName
      SELECT contact_id FROM $tmpTableSecondaryContacts
    ";

        \CRM_Core_DAO::executeQuery($sql);
        //keep going as long as we are adding
        //new contacts to our table
        $continue = $newPotentialPermissionInheritingContacts;
      }
    }
  }

  /**
   * @param string $tmpTableSecondaryContacts
   * @param string $now
   * @param string $permissionClause
   *
   * @return void
   * @throws \Civi\Core\Exception\DBQueryException
   */
  private function calculateInheritedPermissions(string $tmpTableSecondaryContacts, string $now, string $permissionClause) {
    $sql = "REPLACE INTO $tmpTableSecondaryContacts
    SELECT DISTINCT contact_id_b, contact_b.contact_type
    FROM $this->tmpTableName tmp
    LEFT JOIN civicrm_relationship r  ON tmp.contact_id = r.contact_id_a
    INNER JOIN civicrm_contact c ON c.id = r.contact_id_a AND c.contact_type IN ('Household', 'Organization')
    INNER JOIN civicrm_contact contact_b ON contact_b.id = r.contact_id_b
    WHERE
    r.is_active = 1
    AND (start_date IS NULL OR start_date <= '{$now}' )
    AND (end_date IS NULL OR end_date >= '{$now}')
    AND is_permission_a_b $permissionClause
    AND c.is_deleted = 0
  ";

    \CRM_Core_DAO::executeQuery($sql);

    $sql = "REPLACE INTO $tmpTableSecondaryContacts
    SELECT contact_id_a, contact_b.contact_type
    FROM $this->tmpTableName tmp
    LEFT JOIN civicrm_relationship r ON tmp.contact_id = r.contact_id_b
    INNER JOIN civicrm_contact c ON c.id = r.contact_id_b AND c.contact_type IN ('Household', 'Organization')
    INNER JOIN civicrm_contact contact_b ON contact_b.id = r.contact_id_b
    WHERE
    r.is_active = 1
    AND (start_date IS NULL OR start_date <= '{$now}' )
    AND (end_date IS NULL OR end_date >= '{$now}')
    AND is_permission_b_a $permissionClause
    AND c.is_deleted = 0
  ";

    \CRM_Core_DAO::executeQuery($sql);
  }

  /**
   * Implements hook_civicrm_aclWhereClause().
   *
   * Implement WHERE Clause - we find the contacts for whom this contact has permission and
   * specifically give permission to them
   */
  function hook_civicrm_aclWhereClause($type, &$tables, &$whereTables, &$contactID, &$where) {
    if (!$contactID) {
      return;
    }

    if (!\CRM_Core_Permission::check('edit all contacts')) {
      $this->buildPermissionsTable($contactID, $type);
      // Do not add in the permission table join and associated OR clause if there are no permitted relationships
      $check = \CRM_Core_DAO::singleValueQuery("SELECT count(contact_id) as contact_count FROM {$this->tmpTableName}");

      if (!empty($check) || (empty($check) && empty($where))) {
        $tables['$tmpTableName'] = $whereTables['$tmpTableName'] =
          " LEFT JOIN $this->tmpTableName permrelationships
       ON (contact_a.id = permrelationships.contact_id)";
        if (empty($where)) {
          $where = " permrelationships.contact_id IS NOT NULL ";
        }
        else {
          $where = '(' . $where . " OR permrelationships.contact_id IS NOT NULL " . ')';
        }
      }
    }
  }

  /**
   * @param \Civi\Api4\Event\AuthorizeRecordEvent $event
   *
   * @return void
   * @throws \CRM_Core_Exception
   * @throws \Civi\API\Exception\UnauthorizedException
   */
  public function onApiAuthorizeRecord(AuthorizeRecordEvent $event) {
    $apiRequest = $event->getApiRequest();

    if ($apiRequest['version'] != 4) {
      return;
    }

    $loggedInContactID = \CRM_Core_Session::getLoggedInContactID();
    if (!\CRM_Core_Permission::check('edit all contacts') && $loggedInContactID) {
      $this->buildPermissions($apiRequest->getActionName(), $loggedInContactID);
      // Do not add in the permission table join and associated OR clause if there are no permitted relationships
      $sql = "SELECT count(contact_id) as contact_count FROM {$this->tmpTableName} WHERE contact_id = %1";
      $queryParams = [
        1 => [$event->getRecord()['id'], 'Integer'],
      ];
      $check = \CRM_Core_DAO::singleValueQuery($sql, $queryParams);
      if (empty($check)) {
        // Current contact doesn't have permission to edit this contact.
        $event->setAuthorized(FALSE);
      }
      else {
        // Current contact has permission to edit this contact.
        $event->authorize();
      }
      $event->stopPropagation();
    }
  }

  /**
   * Alters APIv4 permissions to allow users with 'administer search_kit' to create/delete a SavedSearch
   *
   * @param \Civi\API\Event\AuthorizeEvent $event
   *   API authorization event.
   */
  public function onApiAuthorize(\Civi\API\Event\AuthorizeEvent $event) {
    $apiRequest = $event->getApiRequest();

    if ($apiRequest['version'] != 4) {
      return;
    }

    // Contact.getFields/getActions should be allowed if contact can see/edit one or more contacts
    if (in_array($apiRequest->getEntityName(), ['Contact', 'Email'])
      && in_array($apiRequest->getActionName(), ['getFields', 'getActions'])) {

      $loggedInContactID = \CRM_Core_Session::getLoggedInContactID();
      if (!\CRM_Core_Permission::check('edit all contacts') && $loggedInContactID) {
        $this->buildPermissions($apiRequest->getActionName(), $loggedInContactID);
        // Do not add in the permission table join and associated OR clause if there are no permitted relationships
        $sql = "SELECT count(contact_id) as contact_count FROM {$this->tmpTableName}";
        $queryParams = [];
        $check = \CRM_Core_DAO::singleValueQuery($sql, $queryParams);

        if (!empty($check)) {
          $event->authorize();
          $event->stopPropagation();
        }
      }
    }
  }

  private function buildPermissions(string $actionName, int $loggedInContactID) {
    switch ($actionName) {
      case 'create':
      case 'update':
      case 'save':
        $permissionType = \CRM_Core_Permission::EDIT;
        break;

      default:
        $permissionType = \CRM_Core_Permission::VIEW;
    }

    $contact = Contact::get(FALSE)
      ->addSelect('id', 'contact_type')
      ->addWhere('id', '=', $loggedInContactID)
      ->execute()
      ->first();
    $this->buildPermissionsTable($contact['id'], $permissionType);
  }

}