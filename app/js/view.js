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
