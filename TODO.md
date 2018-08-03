# ToDo list for RelatedPermssions extension

## Sort out default values

 - idea is that admin can set mode to 'enforce' or 'default'
 - in 'enforce' mode, the admin-set perm overrides anything else: in GUI, in Rel Add/Edit the other options are disabled. In API it should override
 - in 'default' mode, the admin-set perm is selected by default in Rel Add/Edit.  In API, it is used if permission_a_b is not specificed

But:
 in Add/Edit, the form default value is 0 not '', despite setDefaults setting it to '' so not able to distinguish between unset and 0 (None)


## Improve creation of custom values:

 - on Admin > Customize > Relationship Types > Edit, the custom values are editable - make them reserved ?
 - on Admin > Settings > Option Groups there are two groups each for Permission & Permission mode - could just be one of each

## There is no upgrade path from entitysettings 

  - could do this in the Upgrader but is it worth the effort?
 
