$(document).ready(function() {
    $('.togglable-next').click(function() {
        $(this).next().toggle();
    });
    $('.togglable-hidden').hide();

    $('#commonTplTemplate').closest('div').find('.button').click(function() {
        commonTplUpdate();
    });

    $('.item-selector').on('change', function() {
        document.cookie = 'itemType=' + this.value;
        $('.import_block').show();
    });

    $('#teamGroupCreate').next('.button').click(function() {
        teamGroupCreate();
    });

    $('#teamGroupGroupAdd').next('.button').click(function() {
        teamGroupUpdate('add');
    });

    $('#teamGroupGroupRm').next('.button').click(function() {
        teamGroupUpdate('rm');
    });

    $('.teamGroupDelete').click(function() {
        teamGroupDestroy($(this).data('id'), $(this).data('confirm'));
    });

    $('#statusCreate').click(function() {
        statusCreate();
    });

    $('.statusSave').click(function() {
        statusUpdate($(this).data('id'));
    });
    $('.statusDestroy').click(function() {
        statusDestroy($(this).data('id'));
    });

    $('#itemsTypesCreate').click(function() {
        itemsTypesCreate();
    });

    $('.itemsTypesShowEditor').click(function() {
        itemsTypesShowEditor($(this).data('id'));
    });

    $('.itemsTypesUpdate').click(function() {
        itemsTypesUpdate($(this).data('id'));
    });

    $('.itemsTypesDestroy').click(function() {
        itemsTypesDestroy($(this).data('id'));
    });


    // validate on enter
    $('#create_teamgroup').keypress(function (e) {
        var keynum;
        if (e.which) {
            keynum = e.which;
        }
        if (keynum == 13) { // if the key that was pressed was Enter (ascii code 13)
            teamGroupCreate();
        }
    });
    // edit the team group name
    $('h3.teamgroup_name').editable('app/controllers/TeamGroupsController.php', {
        indicator : 'Saving...',
        name : 'teamGroupUpdateName',
        submit : 'Save',
        cancel : 'Cancel',
        style : 'display:inline'

    });
    // SORTABLE for STATUS
    $('.sortable_status').sortable({
        // limit to vertical dragging
        axis : 'y',
        helper : 'clone',
        // do ajax request to update db with new order
        update: function(event, ui) {
            // send the orders as an array
            var ordering = $(".sortable_status").sortable("toArray");

            $.post("app/controllers/AdminController.php", {
                'updateOrdering': true,
                'table': 'status',
                'ordering': ordering
            }).done(function(data) {
                var json = JSON.parse(data);
                if (json.res) {
                    notif(json.msg, 'ok');
                } else {
                    notif(json.msg, 'ko');
                }
            });
        }
    });

    $('.itemsTypesEditor').hide();

    // SORTABLE for ITEMS TYPES
    $('.sortable_itemstypes').sortable({
        // limit to horizontal dragging
        axis : 'y',
        helper : 'clone',
        // do ajax request to update db with new order
        update: function(event, ui) {
            // send the orders as an array
            var ordering = $(".sortable_itemstypes").sortable("toArray");

            $.post("app/controllers/AdminController.php", {
                'updateOrdering': true,
                'table': 'items_types',
                'ordering': ordering
            }).done(function(data) {
                var json = JSON.parse(data);
                if (json.res) {
                    notif(json.msg, 'ok');
                } else {
                    notif(json.msg, 'ko');
                }
            });
        }
    });
    // IMPORT
    $('.import_block').hide();

    // TABS
    // get the tab=X parameter in the url
    var params = getGetParameters();
    var tab = parseInt(params.tab, 10);
    if (!isInt(tab)) {
        tab = 1;
    }
    var initdiv = '#tab' + tab + 'div';
    var inittab = '#tab' + tab;
    // init
    $(".divhandle").hide();
    $(initdiv).show();
    $(inittab).addClass('selected');

    $(".tabhandle" ).click(function(event) {
        var tabhandle = '#' + event.target.id;
        var divhandle = '#' + event.target.id + 'div';
        $(".divhandle").hide();
        $(divhandle).show();
        $(".tabhandle").removeClass('selected');
        $(tabhandle).addClass('selected');
    });
    // END TABS
    // COLORPICKER
    $('.colorpicker').colorpicker({
        hsv: false,
        okOnEnter: true,
        rgb: false
    });
    // EDITOR
    tinymce.init({
        mode : "specific_textareas",
        editor_selector : "mceditable",
        content_css : "app/css/tinymce.css",
        plugins : "table textcolor searchreplace lists advlist code fullscreen insertdatetime paste charmap save image link",
        toolbar1: "undo redo | bold italic underline | fontsizeselect | alignleft aligncenter alignright alignjustify | superscript subscript | bullist numlist outdent indent | forecolor backcolor | charmap | link",
        removed_menuitems : "newdocument",
        language : $('#commonTplTemplate').data('lang')
    });
});
