// ARCHIVE USER
$(document).on('click', '.archiveUser', function(e) {
    // don't trigger the form
    e.preventDefault();
    // show alert
    if (confirm('Are you sure you want to archive this user?')) {
        $.post('app/controllers/UsersAjaxController.php', {
            usersArchive: true,
            userid: $(this).data('userid'),
            csrf: $(this).data('csrf')
        }).done(function(data) {
            if (data.res) {
                notif(data.msg, 'ok');
                window.location.replace('admin.php?tab=3');
            } else {
                notif(data.msg, 'ko');
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
            userid: elem.data('userid'),
            csrf: $(this).data('csrf')
        }).done(function(data) {
            if (data.res) {
                notif(data.msg, 'ok');
                elem.closest('ul', 'list-group').hide();
            } else {
                notif(data.msg, 'ko');
            }
        });
    }
});
