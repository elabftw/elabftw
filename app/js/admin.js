$(document).ready(function() {
    // TEAMGROUPS
    var TeamGroups = {
        controller: 'app/controllers/TeamGroupsController.php',
        create: function() {
            var name = $('#teamGroupCreate').val();
            if (name.length > 0) {
                $.post(this.controller, {
                    teamGroupCreate: name
                }).done(function() {
                    $('#team_groups_div').load('admin.php #team_groups_div');
                    $('#teamGroupCreate').val('');
                    notif('Saved', 'ok');
                });
            }
        },
        update: function(action) {
            if (action === 'add') {
                user = $('#teamGroupUserAdd').val();
                group = $('#teamGroupGroupAdd').val();
            } else {
                user = $('#teamGroupUserRm').val();
                group = $('#teamGroupGroupRm').val();
            }
            $.post(this.controller, {
                teamGroupUpdate: true,
                action: action,
                teamGroupUser: user,
                teamGroupGroup: group
            }).done(function() {
                $('#team_groups_div').load('admin.php #team_groups_div');
            });
        },
        destroy: function(id, confirmText) {
            if (confirm(confirmText)) {
                $.post(this.controller, {
                    teamGroupDestroy: true,
                    teamGroupGroup: id
                }).done(function() {
                    $("#team_groups_div").load("admin.php #team_groups_div");
                });
            }
            return false;
        }
    };
    $(document).on('click', '#teamGroupCreateBtn', function() {
        TeamGroups.create();
    });

    $(document).on('click', '#teamGroupGroupAddBtn', function() {
        TeamGroups.update('add');
    });

    $(document).on('click', '#teamGroupGroupRmBtn', function() {
        TeamGroups.update('rm');
    });

    $(document).on('click', '.teamGroupDelete', function() {
        TeamGroups.destroy($(this).data('id'), $(this).data('confirm'));
    });

    // STATUS
    var Status = {
        controller: 'app/controllers/StatusController.php',
        create: function() {
            var name = $('#statusName').val();
            var color = $('#statusColor').val();
            var isTimestampable = +$('#statusTimestamp').is(':checked');

            $.post(this.controller, {
                statusCreate: true,
                name: name,
                color: color,
                isTimestampable: isTimestampable
            }).done(function(data) {
                if (data.res) {
                    notif(data.msg, 'ok');
                    window.location.replace('admin.php?tab=4');
                } else {
                    notif(data.msg, 'ko');
                }
            });
        },
        update: function(id) {
            var name = $('#statusName_' + id).val();
            var color = $('#statusColor_' + id).val();
            var isTimestampable = +$('#statusTimestamp_'+ id).is(':checked');
            var isDefault = $('#statusDefault_' + id).is(':checked');

            $.post(this.controller, {
                statusUpdate: true,
                id: id,
                name: name,
                color: color,
                isTimestampable: isTimestampable,
                isDefault: isDefault
            }).done(function(data) {
                if (data.res) {
                    notif(data.msg, 'ok');
                } else {
                    notif(data.msg, 'ko');
                }
            });
        },
        destroy: function(id) {
            $.post(this.controller, {
                statusDestroy: true,
                id: id
            }).done(function(data) {
                if (data.res) {
                    notif(data.msg, 'ok');
                    $('#status_' + id).hide();
                } else {
                    notif(data.msg, 'ko');
                }
            });
        }
    };
    $(document).on('click', '#statusCreate', function() {
        Status.create();
    });

    $(document).on('click', '.statusSave', function() {
        Status.update($(this).data('id'));
    });

    $(document).on('click', '.statusDestroy', function() {
        Status.destroy($(this).data('id'));
    });

    // ITEMSTYPES
    var ItemsTypes = {
        controller: 'app/controllers/ItemsTypesController.php',
        create: function() {
            var name = $('#itemsTypesName').val();
            var color = $('#itemsTypesColor').val();
            var checkbox = $('#itemsTypesBookable').is(":checked");
            var bookable = 0;
            if (checkbox) {
                bookable = 1;
            }
            template = tinymce.get('itemsTypesTemplate').getContent();
            $.post(this.controller, {
                itemsTypesCreate: true,
                name: name,
                color: color,
                bookable: bookable,
                template: template
            }).done(function(data) {
                if (data.res) {
                    notif(data.msg, 'ok');
                    window.location.replace('admin.php?tab=5');
                } else {
                    notif(data.msg, 'ko');
                }
            });
        },
        showEditor: function(id) {
            $('#itemsTypesEditor_' + id).toggle();
        },
        update: function(id) {
            var name = $('#itemsTypesName_' + id).val();
            var color = $('#itemsTypesColor_' + id).val();
            var checkbox = $('#itemsTypesBookable_' + id).is(":checked");
            var bookable = 0;
            if (checkbox) {
                bookable = 1;
            }
            var template = tinymce.get('itemsTypesTemplate_' + id).getContent();

            $.post(this.controller, {
                itemsTypesUpdate: true,
                id: id,
                name: name,
                color: color,
                bookable: bookable,
                template: template
            }).done(function(data) {
                if (data.res) {
                    notif(data.msg, 'ok');
                } else {
                    notif(data.msg, 'ko');
                }
            });
        },
        destroy: function(id) {
            $.post(this.controller, {
                itemsTypesDestroy: true,
                id: id
            }).done(function(data) {
                if (data.res) {
                    notif(data.msg, 'ok');
                    $('#itemstypes_' + id).hide();
                } else {
                    notif(data.msg, 'ko');
                }
            });
        }
    };
    $('.itemsTypesEditor').hide();
    $(document).on('click', '#itemsTypesCreate', function() {
        ItemsTypes.create();
    });
    $(document).on('click', '.itemsTypesShowEditor', function() {
        ItemsTypes.showEditor($(this).data('id'));
    });
    $(document).on('click', '.itemsTypesUpdate', function() {
        ItemsTypes.update($(this).data('id'));
    });
    $(document).on('click', '.itemsTypesDestroy', function() {
        ItemsTypes.destroy($(this).data('id'));
    });

    // COMMON TEMPLATE
    $('#commonTplTemplate').closest('div').find('.button').click(function() {
        var template = tinymce.get('commonTplTemplate').getContent();
        $.post('app/controllers/AdminController.php', {
            commonTplUpdate: template
        }).done(function(data) {
            if (data.res) {
                notif(data.msg, 'ok');
            } else {
                notif(data.msg, 'ko');
            }
        });
    });

    // COMMON
    $('.item-selector').on('change', function() {
        document.cookie = 'itemType=' + this.value;
        $('.import_block').show();
    });

    // validate on enter
    $('#create_teamgroup').keypress(function (e) {
        var keynum;
        if (e.which) {
            keynum = e.which;
        }
        if (keynum === 13) { // if the key that was pressed was Enter (ascii code 13)
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
                if (data.res) {
                    notif(data.msg, 'ok');
                } else {
                    notif(data.msg, 'ko');
                }
            });
        }
    });

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
                if (data.res) {
                    notif(data.msg, 'ok');
                } else {
                    notif(data.msg, 'ko');
                }
            });
        }
    });
    // IMPORT
    $('.import_block').hide();

    // COLORPICKER
    $('.colorpicker').colorpicker({
        hsv: false,
        okOnEnter: true,
        rgb: false
    });

    // randomize the input of the color picker so even if user doesn't change the color it's a different one!
    // from https://www.paulirish.com/2009/random-hex-color-code-snippets/
    var colorInput = Math.floor(Math.random()*16777215).toString(16);
    $('#itemsTypesColor').val(colorInput);
    $('#statusColor').val(colorInput);

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
