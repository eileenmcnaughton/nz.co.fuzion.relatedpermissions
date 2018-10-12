CRM.$(function($) {
  var origAB = $('input[type="radio"][name="is_permission_a_b"]:checked').val();
  var origBA = $('input[type="radio"][name="is_permission_b_a"]:checked').val();
  var origType = $('#relationship_type_id').val();
  $('#relationship_type_id').change(function () {
    // Reset
    $('input[type="radio"][name="is_permission_a_b"]').prop('disabled', false);
    $('input[type="radio"][name="is_permission_b_a"]').prop('disabled', false);
    $('input[type="radio"][name="is_permission_a_b"][value="' + origAB + '"]').prop('checked', true);
    $('input[type="radio"][name="is_permission_b_a"][value="' + origBA + '"]').prop('checked', true);
    // on Add Relationship screen, relationship type is not set
    // on Edit Relationship screen, relationship type is current type
    var relType = $(this).val();
    if (!relType) {
      return;
    }

    CRM.api3('Relationship', 'getsettings', { relationship_type_id: relType })
      .done(function (data) {
        if (data.values) {
          var ABmode = data.values.permission_a_b_mode;
          var BAmode = data.values.permission_b_a_mode;
          var ABperm = data.values.permission_a_b;
          var BAperm = data.values.permission_b_a;
          var alert = 0;
          if (ABmode == 1) {
            // Enforce mode
            // Set specified value
            if (ABperm != origAB) {
              // console.log("ABperm = " + ABperm + ", origAB = " + origAB);
              $('input[type="radio"][name="is_permission_a_b"][value="' + ABperm +  '"]').prop('checked', true);
              alert = 1;
            }
            // Disable other values
            $('input[type="radio"][name="is_permission_a_b"]:not(:checked)').prop('disabled', true);
          } else {
            // Default mode
            if (!origType && ABperm != origAB) {
              // console.log("ABperm = " + ABperm + ", origAB = " + origAB);
              $('input[type="radio"][name="is_permission_a_b"][value="' + ABperm +  '"]').prop('checked', true);
              alert = 1;
            }
          }

          if (BAmode == 1) {
            // Enforce mode
            if (BAperm != origBA) {
              // console.log("BAperm = " + BAperm + ", origBA = " + origBA);
              $('input[type="radio"][name="is_permission_b_a"][value=' + BAperm + ']').prop('checked', true);
              alert = 1;
            }
            $('input[type="radio"][name="is_permission_b_a"]:not(:checked)').prop('disabled', true);
          } else {
            // Default mode
            if (!origType && BAperm != origBA) {
              // console.log("BAperm = " + BAperm + ", origBA = " + origBA);
              $('input[type="radio"][name="is_permission_b_a"][value=' + BAperm + ']').prop('checked', true);
              alert = 1;
            }
          }

          if (alert == 1) {
            CRM.alert('Default or enforced permissions have been applied', '', 'info');
          }
        }
      });

  });

});
