/**
 * @author Nicolas CARPi <nicolas.carpi@curie.fr>
 * @copyright 2012 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */
(function() {
  'use strict';

  // ARCHIVE USER
  $(document).on('click', '.archiveUser', function(e) {
    // don't trigger the form
    e.preventDefault();
    // show alert
    if (confirm('Are you sure you want to archive this user?')) {
      $.post('app/controllers/UsersAjaxController.php', {
        usersArchive: true,
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
