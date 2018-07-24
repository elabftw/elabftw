/**
 * view.js - for the ?mode=view
 *
 * @author Nicolas CARPi <nicolas.carpi@curie.fr>
 * @copyright 2012 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */
(function() {
    'use strict';

    $(document).ready(function() {
        // add the title in the page name (see #324)
        document.title = $('.title_view').text() + ' - eLabFTW';

        var type = $('#entityInfos').data('type');
        var id = $('#entityInfos').data('id');
        var confirmText = $('#entityInfos').data('confirm');

        // EDIT
        key($('#shortcuts').data('edit'), function() {
            window.location.href = '?mode=edit&id=' + $('#entityInfos').data('id');
        });

        // TOGGLE LOCK
        $(document).on('click', '#lock', function() {
            $.post("app/controllers/EntityController.php", {
                lock: true,
                type: type,
                id: id
            }).done(function(data) {
                if (data.res) {
                    notif(data.msg, 'ok');
                    // change the lock icon
                    $('#lock').toggleClass('fa-lock-open').toggleClass('fa-lock');
                } else {
                    notif(data.msg, 'ko');
                }
            });
        });

        // CLICK TITLE TO GO IN EDIT MODE
        $(document).on('click', '.click2Edit', function() {
            window.location.href = '?mode=edit&id=' + id;
        });

        // DECODE ASN1
        $(document).on('click', '.decode-asn1', function() {
            $.post('app/controllers/ExperimentsController.php', {
                asn1: $(this).data('token'),
                id: $(this).data('id')
            }).done(function(data) {
                $('#decodedDiv').html(data.msg);
            });
        });

        // COMMENTS
        var Comments = {
            controller: 'app/controllers/CommentsController.php',
            create: function() {
                document.getElementById('commentsCreateButton').disabled = true;
                const comment = $('#commentsCreateArea').val();
                // check length
                if (comment.length < 2) {
                    notif('Comment too short!');
                    document.getElementById('commentsCreateButton').disabled = false;
                    return false;
                }

                $.post(this.controller, {
                    create: true,
                    comment: comment,
                    type: $('#entityInfos').data('type'),
                    id: id
                }).done(function(data) {
                    if (data.res) {
                        $('#comment_container').load("?mode=view&id=" + id + " #comment", function() {
                            makeEditableComment($('#comment_' + data.msg));
                            relativeMoment();
                        });
                    } else {
                        notif(data.msg, 'ko');
                    }
                });
            },
            destroy: function(comment) {
                if (confirm(confirmText)) {
                    $.post(this.controller, {
                    destroy: true,
                    type: $('#entityInfos').data('type'),
                    id: comment
                }).done(function(data) {
                    if (data.res) {
                        notif(data.msg, 'ok');
                        $('#comment_container').load("?mode=view&id=" + id + " #comment", function() {
                            relativeMoment();
                        });
                    } else {
                        notif(data.msg, 'ko');
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

        // DESTROY COMMENTS
        $(document).on('click', '.commentsDestroy', function() {
            Comments.destroy($(this).data('id'));
        });

        relativeMoment();

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
                            if (data.res) {
                                window.location.replace("experiments.php?mode=view&id=" + id);
                            } else {
                                notif(data.msg, 'ko');
                            }
                        });
                    },
                    Cancel: function() {
                        $(this).dialog('close');
                    }
                }
            });
        });
        $('#confirmTimestampDiv').hide();

        // ACTIVATE FANCYBOX
        // doesn't work if the src js is loaded
        // because only the .min.js has the fancybox lib bundled
        $('[data-fancybox]').fancybox();

    });
}());
