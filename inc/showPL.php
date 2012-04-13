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
require_once("themes/".$_SESSION['prefs']['theme']."/highlight.css");
?>
<div id='submenu'><a href="create_item.php?type=pla"><img src="themes/<?php echo $_SESSION['prefs']['theme'];?>/img/create.gif" alt="" /> Add a plasmid</a>
<!-- Quick Search Box (search tags)
<form id='quicksearch' method='get' action='plasmids.php'>
<input type='search' name='tag' placeholder='Search tag' />
</form> end quick search -->
</div><!-- end submenu -->
<?php
$sql = "SELECT * 
    FROM plasmids";
$req = $bdd->prepare($sql);
$req->execute();
while ($data = $req->fetch()) {
?>
    <section onClick="document.location='plasmids.php?mode=view&id=<?php echo $data['id'];?>'" class='item'>
    <?php
    echo "<span class='redo_compact'>".$data['date']."</span> ";
    echo stripslashes($data['title']);
    echo "</section>";
}
?>
<script type='text/javascript'>
<?php
// KEYBOARD SHORTCUTS
echo "key('".$_SESSION['prefs']['shortcuts']['create']."', function(){location.href = 'create_item.php?type=pla'});";
?>
</script>
