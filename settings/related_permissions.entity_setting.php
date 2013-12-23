<?php

return array (
  array(
    'key' => 'nz.co.fuzion.relatedpermissions',
    'entity' => 'relationship_type',
    'name' => 'always_permission_a_b',
    'type' => 'Boolean',
    'html_type' => 'Checkbox',
    'add' => '1.0',
    'title' => 'Always Permission A to B',
    'description' => null,
    'help_text' => null,
    'add_to_setting_form' => TRUE,
    'form_child_of_parents_parent' => 'label_a_b',
  ),
  array(
    'key' => 'nz.co.fuzion.relatedpermissions',
    'entity' => 'relationship_type',
    'name' => 'always_permission_b_a',
    'type' => 'Boolean',
    'html_type' => 'Checkbox',
    'add' => '1.0',
    'title' => 'Always Permission B to A',
    'description' => null,
    'help_text' => null,
    'add_to_setting_form' => TRUE,
    'form_child_of_parents_parent' => 'label_b_a',
  ),
);