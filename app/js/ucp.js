// READY ? GO !!
$(document).ready(function() {


    var Templates = {
        controller: 'app/controllers/UcpController.php',
        saveToFile: function(id, name) {
        // we have the name of the template used for filename
        // and we have the id of the editor to get the content from
        // we don't use activeEditor because it requires a click inside the editing area
        var content = tinyMCE.get(id).getContent();
        var blob = new Blob([content], {type: "text/plain;charset=utf-8"});
        saveAs(blob, name + ".elabftw.tpl");
        },
        destroy: function(id) {
            if (confirm('Delete this ?')) {
                $.post(this.controller, {
                    templatesDestroy: true,
                    id: id
                }).done(function(data) {
                    var json = JSON.parse(data);
                    if (json.res) {
                        notif(json.msg, 'ok');
                        window.location.replace('ucp.php?tab=3');
                    } else {
                        notif(json.msg, 'ko');
                    }
                });
            }
        }
    };

    $(document).on('click', '.save-to-file', function() {
        Templates.saveToFile($(this).data('id'), $(this).data('name'));
    });
    $(document).on('click', '.destroy-template', function() {
        Templates.destroy($(this).data('id'));
    });

    // hide the file input
    $('#import_tpl').hide();
    $(document).on('click', '#import-from-file', function() {
        $('#import_tpl').toggle();
    });
    $('#import_tpl').on('change', function(e) {
        var title = document.getElementById('import_tpl').value.replace(".elabftw.tpl", "").replace("C:\\fakepath\\", "");
        readFile(this.files[0], function(e) {
            tinyMCE.get('new_tpl_txt').setContent(e.target.result);
            $('#new_tpl_name').val(title);
            $('#import_tpl').hide();
        });
    });

    $('.nav-pills').sortable({
        // limit to horizontal dragging
        axis : 'x',
        helper : 'clone',
        // we don't want the Create new pill to be sortable
        cancel: "#subtab_1",
        // do ajax request to update db with new order
        update: function(event, ui) {
            // send the orders as an array
            var ordering = $(".nav-pills").sortable("toArray");

            $.post("app/controllers/AdminController.php", {
                'updateOrdering': true,
                'table': 'experiments_templates',
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

    // SUB TABS
    var tab = 1;
    var initdiv = '#subtab_' + tab + 'div';
    var inittab = '#subtab_' + tab;
    // init
    $(".subdivhandle").hide();
    $(initdiv).show();
    $(inittab).addClass('selected');

    $(".subtabhandle" ).click(function(event) {
        var tabhandle = '#' + event.target.id;
        var divhandle = '#' + event.target.id + 'div';
        $(".subdivhandle").hide();
        $(divhandle).show();
        $(".subtabhandle").removeClass('badgetabactive');
        $(tabhandle).addClass('badgetabactive');
    });
    // END SUB TABS

    // TinyMCE
    tinymce.init({
        mode : "specific_textareas",
        editor_selector : "mceditable",
        content_css : "app/css/tinymce.css",
        plugins : "table textcolor searchreplace code lists advlist fullscreen insertdatetime paste charmap save image link",
        toolbar1: "undo redo | bold italic underline | fontsizeselect | alignleft aligncenter alignright alignjustify | superscript subscript | bullist numlist outdent indent | forecolor backcolor | charmap | link",
        removed_menuitems : "newdocument",
        language : $('#language').data('lang')
    });
});

