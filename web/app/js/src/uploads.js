/**
 * uploads.js - for the uploaded files
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
        // DISPLAY 2D MOL FILES
        // loop all the mol files and display the molecule with ChemDoodle
        $.each($('.molFile'), function() {
            // id of the canvas to attach the viewer to
            var id = $(this).attr('id');
            // now get the file content and display it in the viewer
            ChemDoodle.io.file.content($(this).data('molpath'), function(fileContent){
                var mol = ChemDoodle.readMOL(fileContent);
                var viewer = new ChemDoodle.ViewerCanvas(id, 250, 250);
                // config some stuff in the viewer
                viewer.specs.bonds_width_2D = 0.6;
                viewer.specs.bonds_saturationWidth_2D = 0.18;
                viewer.specs.bonds_hashSpacing_2D = 2.5;
                viewer.specs.atoms_font_size_2D = 10;
                viewer.specs.atoms_font_families_2D = ['Helvetica', 'Arial', 'sans-serif'];
                viewer.specs.atoms_displayTerminalCarbonLabels_2D = true;
                // load it
                viewer.loadMolecule(mol);
            });
        });

        // REPLACE UPLOAD
        // append a file form to the upload with type, upload id, and item id
        $(document).on('click', '.replacer', function() {
            $(this).append("<div><form enctype='multipart/form-data' action='app/controllers/EntityController.php' method='POST'><input type='hidden' name='replace' /><input type='hidden' name='upload_id' value='" + $(this).data('id') + "' /><input type='hidden' name='id' value='" + $(this).data('itemid') + "' /><input type='hidden' name='type' value='" + $(this).data('type') + "'><input type='file' style='display:inline' name='file' /><button type='submit' class='button'>OK</button></form></div>");
            // prevent multi click
            $(this).removeClass('replacer');
        });

        // DESTROY UPLOAD
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
}());
