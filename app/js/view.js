$(document).ready(function() {
    // TOGGLE LOCK
    $(document).on('click', '#lock', function() {
        $.post("app/controllers/EntityController.php", {
            lock: true,
            type: $(this).data('type'),
            id: $(this).data('id')
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

    $(document).on('click', '.click2Edit', function() {
        var page = $(this).data('page');
        var id = $(this).data('id');
        document.location = page + '?mode=edit&id=' + id;
    });


    var Comments = {
        controller: 'app/controllers/CommentsController.php',
        create: function(expId) {
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
                id: expId
            }).done(function(data) {
                var json = JSON.parse(data);
                if (json.res) {
                    notif(json.msg, 'ok');
                    $('#expcomment_container').load("experiments.php?mode=view&id=" + expId + " #expcomment");
                } else {
                    notif(json.msg, 'ko');
                }
            });
        },
        destroy: function(id, expId, confirmText) {
            if (confirm(confirmText)) {
                $.post(this.controller, {
                destroy: true,
                id: id
            }).done(function(data) {
                var json = JSON.parse(data);
                if (json.res) {
                    notif(json.msg, 'ok');
                     $('#expcomment_container').load("experiments.php?mode=view&id=" + expId + " #expcomment");
                } else {
                    notif(json.msg, 'ko');
                }
            });
            } else {
                return false;
            }
        }
    };

    $('#commentsCreateButtonDiv').hide();
    $(document).on('focus', '#commentsCreateArea', function() {
        $('#commentsCreateButtonDiv').show();
    });
    $(document).on('click', '#commentsCreateButton', function() {
        Comments.create($(this).data('expid'));
    });
    $(document).on('mouseover', '.editable', function(){
        expId = $(this).data('expid');
        $('div#expcomment p.editable').editable(Comments.controller, {
            name: 'commentsUpdate',
            tooltip : 'Click to edit',
            indicator : $(this).data('indicator'),
            submit : $(this).data('submit'),
            cancel : $(this).data('cancel'),
            style : 'display:inline',
            callback : function() {
                // now we reload the comments part to show the comment we just submitted
                $('#expcomment_container').load('experiments.php?mode=view&id=' + expId + ' #expcomment');
            }
        });
    });
    $(document).on('click', '.commentsDestroy', function() {
        Comments.destroy($(this).data('id'), $(this).data('expid'), $(this).data('confirm'));
    });

    // TIMESTAMP
    $(document).on('click', '#confirmTimestamp', function() {
        expId = $(this).data('expid');
        console.log(expId);
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
                        id: expId
                    }).done(function (data) {
                        var json = JSON.parse(data);
                        if (json.res) {
                            window.location.replace("experiments.php?mode=view&id=" + expId);
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
});
