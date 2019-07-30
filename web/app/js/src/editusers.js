/**
 * @author Nicolas CARPi <nicolas.carpi@curie.fr>
 * @copyright 2012 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */
(function() {
  'use strict';

  // ARCHIVE USER TOGGLE
  $(document).on('click', '.toggleArchiveUser', function(e) {
    // don't trigger the form
    e.preventDefault();
    // show alert
    if (confirm('Are you sure you want to archive/unarchive this user?\nAll experiments will be locked and user will not be able to login anymore.')) {
      $.post('app/controllers/UsersAjaxController.php', {
        toggleArchiveUser: true,
        userid: $(this).data('userid')
      }).done(function(json) {
        notif(json);
        if (json.res) {
          window.location.replace('admin.php?tab=3');
        }
      });
    }
  });

  // DESTROY USER
  $(document).on('click', '.destroyUser', function(e) {
    // don't trigger the form
    e.preventDefault();
    // show alert
    if (confirm('Are you sure you want to remove permanently this user and all associated data?')) {
      // store the element here because 'this' will change in the done function
      const elem = $(this);
      $.post('app/controllers/UsersAjaxController.php', {
        usersDestroy: true,
        userid: elem.data('userid')
      }).done(function(json) {
        notif(json);
        if (json.res) {
          elem.closest('ul', 'list-group').hide();
        }
      });
    }
  });
}());
