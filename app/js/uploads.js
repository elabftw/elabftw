$(document).ready(function() {
    $(document).on('click', '.uploadsDestroy', function() {
        var itemid = $(this).data('itemid');
        if (confirm($(this).data('msg'))) {
            $.post('app/controllers/EntityController.php', {
                uploadsDestroy: true,
                upload_id: $(this).data('id'),
                id: itemid,
                type: $(this).data('type')
            }).done(function(data) {
                if (data.res) {
                    notif(data.msg, 'ok');
                    $("#filesdiv").load("?mode=edit&id=" + itemid + " #filesdiv");
                } else {
                    notif(data.msg, 'ko');
                }
            });
        }
    });
});
