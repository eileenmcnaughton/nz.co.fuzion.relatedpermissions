CRM.$(function($) {
  var origAB = $('input[type="radio"][name="is_permission_a_b"]:checked').val();
  var origBA = $('input[type="radio"][name="is_permission_b_a"]:checked').val();
  $('#relationship_type_id').change(function () {
    // Reset
    $('input[type="radio"][name="is_permission_a_b"]').prop('disabled', false);
    $('input[type="radio"][name="is_permission_b_a"]').prop('disabled', false);
    $('input[type="radio"][name="is_permission_a_b"][value="' + origAB + '"]').prop('checked', true);
    $('input[type="radio"][name="is_permission_b_a"][value="' + origBA + '"]').prop('checked', true);
    CRM.api3('Relationship', 'getsettings',
      { relationship_type_id: $(this).val() })
      .done(function (data) {
        if (data.values) {
          alert (data.values);
          var ABmode = data.values.permission_a_b_mode;
          var BAmode = data.values.permission_b_a_mode;
          var ABperm = data.values.permission_a_b;
          var BAperm = data.values.permission_b_a;
          if (ABmode) {
            // Enforce mode
            // Set specified value
            $('input[type="radio"][name="is_permission_a_b"][value="' + ABperm +  '"]').prop('checked', true);
            // Disable other values
            $('input[type="radio"][name="is_permission_a_b"]:not(:checked)').prop('disabled', true);
          } else {
            // TODO Default mode
          }

          if (BAmode) {
            // Enforce mode
            $('input[type="radio"][name="is_permission_b_a"][value=' + BAperm + ']').prop('checked', true);
            $('input[type="radio"][name="is_permission_b_a"]:not(:checked)').prop('disabled', true);
          } else {
            // TODO Default mode
          }
        }
      });

  });

});
