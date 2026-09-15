# nz.co.fuzion.relatedpermissions

This extension makes the permission flag on a contact's relationship work as a true ACL. In core CiviCRM that flag only allows the user to see the contact's dashboard. However, in many cases it is a useful mechanism to give people permission to view contact records and search for contacts.

Contacts also get 'transitive permissions' - if you give, for example, a secretary permission over an organisation they will have permission over anyone that organisation has permissions over. Transitive permissions go only one step & only where the first contact is a Household or Organisation

## Features

- Ability to set some relationship types to always be permissioned (this doesn't retrospectively change them but does cause save on any relationships to have the permission flag going forwards)

- Second degree permissions. (If the second degree permissions is checked then on admin/misc screen). For example if you have a organisation called 'Chapter' and it has a permissioned relationship over an organisation called 'branch' then anyone with a permissioned relationship over the chapter will have permissions to access anyone the branch has a permissioned relationship over. There is no theoretical limit to the number of relationshipsbut only organisations and households pass on their permissions. This is because you cannot login as an organisation / household so there is no other purpose served by a household having permission over someone. However, individuals may throw up some more nuances - so for now they are excluded.

- Integrates with Searchkit and FormBuilder permissions to allow/disallow modifications based on Relationship permissions configured.
- Allows viewing and modification of `Email, Relationship, Contact, Household, and Organization`
- It does not allow the creation of entities based on Relationships.


## Custom Permissioning

_Please note:_ If you are adding additional permissioning that is based on Relationships you will need to create a custom extension that potentially integrates elements below. The main reason being is that outright this respects the Relationship rules setup in the extension.

+ [hook_civicrm_aclWhereClause](https://docs.civicrm.org/dev/en/latest/hooks/hook_civicrm_aclWhereClause/)
+ [civi.api.authorize](https://docs.civicrm.org/dev/en/latest/security/permissions/)
+ [civi.api4.authorizeRecord](https://docs.civicrm.org/dev/en/latest/hooks/civi.api4.authorizeRecord/)

## Outcomes

+ When extension is enabled, on any Relationship Type you should now see a new fields saying Always Permission A to B (and vice versa).
+ On `civicrm/admin/setting/misc?reset=1` you should see an option for `Allow second-degree relationship permissions`

[!example-relationship.png](images/example-relationship.png)
