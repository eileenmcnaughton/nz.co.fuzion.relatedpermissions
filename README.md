# nz.co.fuzion.relatedpermissions

This extension makes the permission flag on a contact's relationship work as a true ACL. In core CiviCRM that flag only allows the user to see the contact's dashboard. However, in many cases it is a useful mechanism to give people permission to view contact records and search for contacts.

Contacts also get 'transitive permissions' - if you give, for example, a secretary permission over an organisation they will have permission over anyone that organisation has permissions over. Transitive permissions go only one step & only where the first contact is a Household or Organisation

## Features

- Ability to set some relationship types to always be permissioned (this doesn't retrospectively change them but does cause save on any relationships to have the permission flag going forwards)

- Second degree permissions. (If the second degree permissions is checked then on admin/misc screen). For example if you have a organisation called 'Chapter' and it has a permissioned relationship over an organisation called 'branch' then anyone with a permissioned relationship over the chapter will have permissions to access anyone the branch has a permissioned relationship over. There is no theoretical limit to the number of relationshipsbut only organisations and households pass on their permissions. This is because you cannot login as an organisation / household so there is no other purpose served by a household having permission over someone. However, individuals may throw up some more nuances - so for now they are excluded.

## Outcomes

When extension is enabled, on any Relationship Type you should now see a new field saying Always Permission A to B (and vv). And at civicrm/admin/setting/misc?reset=1 you should see an option for 'Allow second-degree relationship permissions'
