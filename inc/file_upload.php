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
?>
<!-- FILE UPLOAD -->
<script src="js/bigUpload.js"></script>
<script>
bigUpload = new bigUpload();
function upload() {
    bigUpload.fire();
}
function abort() {
    bigUpload.abortFileUpload();
}
</script>
<hr class='flourishes'><div class="bigUpload inline">
    <div class="bigUploadContainer">
        <h3>Attach a file</h3>
        <form action="inc/bigUpload.php?action=post-unsupported" method="post" enctype="multipart/form-data" id="bigUploadForm">
            <input type="file" id="bigUploadFile" name="bigUploadFile" />
            <input type="button" class="bigUploadButton" value="Start Upload" id="bigUploadSubmit" onclick="upload()" />
            <input type="button" class="bigUploadButton bigUploadAbort" value="Cancel" onclick="abort()" />
        </form>
        <div id="bigUploadProgressBarContainer">
            <div id="bigUploadProgressBarFilled">
            </div>
        </div>
        <div id="bigUploadTimeRemaining"></div>
        <div id="bigUploadResponse"></div>
    </div>
</div><!-- END FILE UPLOAD -->

