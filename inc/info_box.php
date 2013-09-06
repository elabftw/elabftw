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
require_once('inc/functions.php');
// INFO BOX

if (isset($_SESSION['errors']) && is_array($_SESSION['errors']) && count($_SESSION['errors']) >0 ) {
    foreach($_SESSION['errors'] as $msg) {
        echo display_message('error', $msg);
    }
    unset($_SESSION['errors']);
}

if (isset($_SESSION['infos']) && is_array($_SESSION['infos']) && count($_SESSION['infos']) >0 ) {
    foreach($_SESSION['infos'] as $msg) {
        echo display_message('info', $msg);
    }
    unset($_SESSION['infos']);
}
?>

<script>
// box disappear when user click on it
$(".ui-state-highlight").click(function(){
    $(this).hide(500);
});
</script>

