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
if( isset($_SESSION['errors']) && is_array($_SESSION['errors']) && count($_SESSION['errors']) >0 ) {
    echo "<ul class='err'><img src='img/error.png' alt='fail' /> ";
    foreach($_SESSION['errors'] as $msg) {
        echo "<li class='inline'>".$msg."</li>"; 
    }
    echo '</ul>';
    unset($_SESSION['errors']);
}
if( isset($_SESSION['infos']) && is_array($_SESSION['infos']) && count($_SESSION['infos']) >0 ) {
    echo "<ul class='infos'><img src='img/ok.png' alt='ok' /> ";
    foreach($_SESSION['infos'] as $msg) {
        echo "<li class='inline'>".$msg."</li>"; 
        echo "<a href='#' class='close_box'>X</a>";
    }
    echo "</ul>";
    unset($_SESSION['infos']);
?>
    <script type='text/javascript'>
$(document).ready(function(){
    $(".close_box").click(function(){
        $('ul.infos, ul.err').hide('explode', 'slow');
    });
});
    </script>
<?php
}
$msg_arr = array();
?>
