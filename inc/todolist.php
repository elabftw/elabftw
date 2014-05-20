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
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
?>
    <script>
        console.log($this);
        var data = '<?php echo $_POST['value'];?>';
        localStorage.setItem($this.parent().attr("id"), data.value);
    </script>
<?php
    exit();
}
?>
<!DOCTYPE HTML>
<html>
    <head>
        <meta charset="utf-8" />
        <link rel="stylesheet" href="../css/todolist.css" type="text/css" />
    </head>
    <body>
        <div id="container">
            <form id="todo-form">
                <input id="todo" type="text" />
                <input id="submit" type="submit" value="TODOfy">
            </form>
            <ul id="show-items"></ul>
            <a href="#" id="clear-all">Clear All</a>
        </div>
        <script src="../bower_components/jquery/dist/jquery.min.js"></script>
        <script src="../bower_components/jquery-ui/ui/minified/jquery-ui.min.js"></script>
        <script src="../bower_components/jquery_jeditable/jquery.jeditable.js"></script>
        <script src="../js/todolist.js"></script>
    </body>
</html>

