<?php
/********************************************************************************
*                                                                               *
*   Copyright 2012 Nicolas CARPi (nicolas.carpi@gmail.com)                      *
*   http://www.elabftw.net/                                                     *
*                                                                               *
********************************************************************************/

/********************************************************************************
*  This file is part of eLabFTW.                                                *
*                                                                               *
*    eLabFTW is free software: you can redistribute it and/or modify            *
*    it under the terms of the GNU Affero General Public License as             *
*    published by the Free Software Foundation, either version 3 of             *
*    the License, or (at your option) any later version.                        *
*                                                                               *
*    eLabFTW is distributed in the hope that it will be useful,                 *
*    but WITHOUT ANY WARRANTY; without even the implied                         *
*    warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR                    *
*    PURPOSE.  See the GNU Affero General Public License for more details.      *
*                                                                               *
*    You should have received a copy of the GNU Affero General Public           *
*    License along with eLabFTW.  If not, see <http://www.gnu.org/licenses/>.   *
*                                                                               *
********************************************************************************/
// where are we, on experiments or items (database) page ?
if (strpos($_SERVER['SCRIPT_FILENAME'], 'experiments')) {
    $type = 'experiments';
} else {
    $type = 'items';
}
?>
<section class='box'>
    <!-- FILE UPLOAD BLOCK -->
    <script src="js/dropzone/dist/min/dropzone.min.js"></script>
    <link rel="stylesheet" media="all" href="js/dropzone/dist/dropzone.css" />
    <!-- fix some css here -->
    <style>
    .dropzone {
        border: none;
    }
    </style>
    <img src='img/attached.png' class='bot5px'> <h3 style='display:inline'><?php echo _('Attach a file');?></h3>
    <!-- additionnal parameters are added as GET params -->
    <form action="app/upload.php?item_id=<?php echo $id;?>&type=<?php echo $type;?>"
        method="post"
        enctype="multipart/form-data"
        class="dropzone"
        id='elabftw-dropzone'>
    </form>
</section>
<script>
// we need this to reload the #filesdiv (div displaying uploaded files)
var type = '<?php echo $type;?>';
if (type == 'items') {
    type = 'database';
}
var item_id = '<?php echo $id;?>';

// config for dropzone, id is camelCased.
Dropzone.options.elabftwDropzone = {
    // i18n message to user
    dictDefaultMessage: '<?php echo _('Drop files here to upload');?>',
    maxFilesize: 2, // MB
    init: function() {
        this.on("complete", function() {
            // reload the #filesdiv once the file is uploaded
            if (this.getUploadingFiles().length === 0 && this.getQueuedFiles().length === 0) {
                $("#filesdiv").load(type + '.php?mode=edit&id=' + item_id + ' #filesdiv', function() {
                    // make the comment zone editable (fix issue #54)
                    $('.thumbnail p.editable').editable('app/editinplace.php', {
                     tooltip : 'Click to edit',
                     indicator : 'Saving...',
                     id   : 'id',
                     name : 'filecomment',
                     submit : 'Save',
                     cancel : 'Cancel',
                     style : 'display:inline'
                    });
                });
            }
        });
    }
};
</script>
