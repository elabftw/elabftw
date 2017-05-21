$(document).ready(function() {
    // add the title in the page name (see #324)
    document.title = $('#entityInfos').data('title');

    var type = $('#entityInfos').data('type');
    var id = $('#entityInfos').data('id');
    var confirmText = $('#entityInfos').data('confirm');

    // TOGGLE LOCK
    $(document).on('click', '#lock', function() {
        $.post("app/controllers/EntityController.php", {
            lock: true,
            type: type,
            id: id
        }).done(function(data) {
            var json = JSON.parse(data);
            if (json.res) {
                notif(json.msg, 'ok');
                // change the lock icon
                current = $('#lock').attr('src');
                if (current === 'app/img/lock-gray.png') {
                    $('#lock').attr('src', 'app/img/unlock.png');
                } else {
                    $('#lock').attr('src', 'app/img/lock-gray.png');
                }
            } else {
                notif(json.msg, 'ko');
            }
        });
    });

    // CLICK TITLE TO GO IN EDIT MODE
    $(document).on('click', '.click2Edit', function() {
        var page = $(this).data('page');
        document.location = page + '?mode=edit&id=' + id;
    });

    // COMMENTS
    var Comments = {
        controller: 'app/controllers/CommentsController.php',
        create: function() {
            document.getElementById('commentsCreateButton').disabled = true;
            comment = $('#commentsCreateArea').val();
            // check length
            if (comment.length < 2) {
                notif('Comment too short!');
                document.getElementById('commentsCreateButton').disabled = false;
                return false;
            }

            $.post(this.controller, {
                commentsCreate: true,
                comment: comment,
                id: id
            }).done(function(data) {
                var json = JSON.parse(data);
                if (json.res) {
                    notif(json.msg, 'ok');
                    $('#expcomment_container').load("experiments.php?mode=view&id=" + id + " #expcomment");
                } else {
                    notif(json.msg, 'ko');
                }
            });
        },
        destroy: function(comment) {
            if (confirm(confirmText)) {
                $.post(this.controller, {
                destroy: true,
                id: comment
            }).done(function(data) {
                var json = JSON.parse(data);
                if (json.res) {
                    notif(json.msg, 'ok');
                     $('#expcomment_container').load("experiments.php?mode=view&id=" + id + " #expcomment");
                } else {
                    notif(json.msg, 'ko');
                }
            });
            } else {
                return false;
            }
        }
    };

    // CREATE COMMENTS
    $('#commentsCreateButtonDiv').hide();
    $(document).on('focus', '#commentsCreateArea', function() {
        $('#commentsCreateButtonDiv').show();
    });

    $(document).on('click', '#commentsCreateButton', function() {
        Comments.create($(this).data('expid'));
    });

    // UPDATE COMMENTS
    $(document).on('mouseover', '.editable', function(){
        $('div#expcomment p.editable').editable(Comments.controller, {
            name: 'commentsUpdate',
            tooltip : 'Click to edit',
            indicator : $(this).data('indicator'),
            submit : $(this).data('submit'),
            cancel : $(this).data('cancel'),
            style : 'display:inline',
            callback : function() {
                // now we reload the comments part to show the comment we just submitted
                $('#expcomment_container').load('experiments.php?mode=view&id=' + id + ' #expcomment');
            }
        });
    });

    // DESTROY COMMENTS
    $(document).on('click', '.commentsDestroy', function() {
        Comments.destroy($(this).data('id'));
    });

    // TIMESTAMP
    $(document).on('click', '#confirmTimestamp', function() {
        $('#confirmTimestampDiv').dialog({
            resizable: false,
            height: 'auto',
            width: 400,
            modal: true,
            buttons: {
                'Timestamp it': function() {
                    $('#confirmTimestampDiv').text($(this).data('wait'));
                    $.post('app/controllers/ExperimentsController.php', {
                        timestamp: true,
                        id: id
                    }).done(function (data) {
                        var json = JSON.parse(data);
                        if (json.res) {
                            window.location.replace("experiments.php?mode=view&id=" + id);
                        } else {
                            notif(json.msg, 'ko');
                        }
                    });
                },
                Cancel: function() {
                    $(this).dialog('close');
                }
            },
        });
    });
    $('#confirmTimestampDiv').hide();

    // KEYBOARD SHORTCUTS
    key($('#shortcuts').data('create'), function(){
        location.href = $('#entityInfos').data('controller') + '?create=true';
    });
    key($('#shortcuts').data('edit'), function(){
        location.href = $('#entityInfos').data('page') + '?mode=edit&id=' + $('#entityInfos').data('id');
    });
});
