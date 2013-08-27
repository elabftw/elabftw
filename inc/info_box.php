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
// INFO BOX
if (isset($_SESSION['errors']) && is_array($_SESSION['errors']) && count($_SESSION['errors']) >0 ) {
    echo "<ul class='errors'>";
    foreach($_SESSION['errors'] as $msg) {
        echo "<img src='img/error.png' alt='fail' /> <li class='inline'>".$msg."</li><br />"; 
    }
    echo "</ul>";
    unset($_SESSION['errors']);
}

if (isset($_SESSION['infos']) && is_array($_SESSION['infos']) && count($_SESSION['infos']) >0 ) {
    echo "<ul class='infos'>";
    foreach($_SESSION['infos'] as $msg) {
        echo "<img src='img/ok.png' alt='ok' /> <li class='inline'>".$msg."</li><br />"; 
    }
    echo "</ul>";
    unset($_SESSION['infos']);
}
?>

<script>
// box explode when user click on it
$(document).ready(function(){
    $("ul.errors, ul.infos").click(function(){
        $(this).hide('explode', 'slow');
    });
});
</script>

