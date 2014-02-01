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
require_once('inc/common.php');
$page_title = 'User Control Panel';
require_once('inc/head.php');
require_once('inc/menu.php');
require_once('inc/info_box.php');
// SQL for UCP
$sql = "SELECT username, email, firstname, lastname, phone, cellphone, skype, website FROM users WHERE userid = ".$_SESSION['userid'];
$req = $bdd->prepare($sql);
$req->execute();
$users = $req->fetch();

// BEGIN UCP PAGE
?>
<script src="js/tinymce/tinymce.min.js"></script>

<div id='accordion'>
    <h3><a href='#infos'>PERSONNAL INFORMATIONS</a></h3>
    <div>

        <form name="profileForm" method="post" action="ucp-exec.php">
            <div>
                <p>Enter your current password to change personnal infos
                <input id='currpass' name="currpass" type="password" /></p>
            </div>
            <div class='innerinnerdiv'>
                  New password <input name="newpass" type="password" /><br />
                  Confirm new password <input name="cnewpass" type="password" /><br />
                  Change Email <input name="email" type="email" value='<?php echo $users['email'];?>' cols='20' rows='1' /><br />
                  Username <input name="username" value='<?php echo $users['username'];?>' cols='20' rows='1' /><br />
                  Firstname <input name="firstname" value='<?php echo $users['firstname'];?>' cols='20' rows='1' /><br />
                  Lastname <input name="lastname" value='<?php echo $users['lastname'];?>' cols='20' rows='1' /><br />
                  Phone <input name="phone" value='<?php echo $users['phone'];?>' cols='20' rows='1' /><br />
                  Cellphone <input name="cellphone" value='<?php echo $users['cellphone'];?>' cols='20' rows='1' /><br />
                  Skype <input name="skype" value='<?php echo $users['skype'];?>' cols='20' rows='1' /><br />
                  Website <input name="website" type="url" value='<?php echo $users['website'];?>' cols='20' rows='1' /><br />
            </div><!-- end innerdiv -->
            <br />
            <input type='hidden' name='main'>
            <div class='center'>
                <input type="submit" name="Submit" class='button' value="Update profile" />
            </div>
        </form>
    </div>

    <h3><a href='#display'>DISPLAY PREFERENCES</a></h3>
    <div>

        <form action='ucp-exec.php' method='post'>
            <h4>View mode :</h4>
            <label for='radio_view_default'>Default</label>
            <input id='radio_view_default' type='radio' name='display' value='default' 
            <?php echo ($_SESSION['prefs']['display'] === 'default') ? "checked" : "";?>
             />

            <label for='radio_view_compact'>Compact</label>
            <input id='radio_view_compact' type='radio' name='display' value='compact' 
            <?php echo ($_SESSION['prefs']['display'] === 'compact') ? "checked" : "";?>
             />

            <br /><br />

            <h4>Order by :</h4>
            <select name="order">
                <option
                <?php
                if ($_SESSION['prefs']['order'] === 'date'){
                    echo ' selected ';}?>value="date">Date</option>
                <option
                <?php
                if ($_SESSION['prefs']['order'] === 'id'){
                    echo ' selected ';}?>value="id">Item ID</option>
                <option
                <?php
                if ($_SESSION['prefs']['order'] === 'title'){
                    echo ' selected ';}?>value="title">Title</option>
            </select>

            <select name="sort">
                <option
                <?php
                if ($_SESSION['prefs']['sort'] === 'desc'){
                    echo ' selected ';}?>value="desc">Newer first</option>
                <option
                <?php
                if ($_SESSION['prefs']['sort'] === 'asc'){
                    echo ' selected ';}?>value="asc">Older first</option>
            </select>

            <br /><br />

            <h4>Number of items to show on each page :</h4>
            <input type='text' size='2' maxlength='2' value='<?php echo $_SESSION['prefs']['limit'];?>' name='limit'>

            <br /><br />

            <h4>Theme (hover to preview) :</h4>

            <br /><br />

            <div class='themes_div'>
                <input type='radio' name='theme' value='default' <?php if ($_SESSION['prefs']['theme'] === 'default'){ echo "checked='checked'";}?>>Default<br />
                <img onmouseover="setTmpTheme('default');" onmouseout="setTmpTheme('<?php echo $_SESSION['prefs']['theme'];?>')" src='themes/default/img/sample.png' alt='default theme'>
                <br />
                <input type='radio' name='theme' value='l33t' <?php if ($_SESSION['prefs']['theme'] === 'l33t'){ echo "checked='checked'";}?> class='inline'>l33t<br />
                <img onmouseover="setTmpTheme('l33t');" onmouseout="setTmpTheme('<?php echo $_SESSION['prefs']['theme'];?>')" src='themes/l33t/img/sample.png' alt='l33t theme'>
            </div>

            <br /><br />

            <!-- SUBMIT BUTTON -->
            <div class='center'>
                <input type="submit" name="Submit" class='button' value="Set preferences" />
            </div>
        </form>
    </div>

    <h3><a href='#experiments'>EXPERIMENTS TEMPLATES</a></h3>
    <div>

        <div id='tpl'>
        <?php // SQL TO GET TEMPLATES
        $sql = "SELECT id, body, name FROM experiments_templates WHERE userid = ".$_SESSION['userid'];
        $req = $bdd->prepare($sql);
        $req->execute();
        echo "<ul>";
        // tabs titles
        echo "<li><a href='#tpl-0'>Create new</a></li>";
        $i = 2;
        while ($users = $req->fetch()) {
            echo "<li><a href='#tpl-".$i."'>".stripslashes($users['name'])."</a></li>";
            $i++;
        }
        echo "</ul>";
        ?>
        <!-- create new tpl tab -->
        <div id="tpl-0">
            <form action='ucp-exec.php' method='post'>
                <input type='hidden' name='new_tpl_form' />
                <input type='text' name='new_tpl_name' placeholder='Name of the template' /><br />
                <textarea name='new_tpl_body' id='new_tpl_txt' style='height:500px;' class='mceditable' rows='50' cols='60'></textarea>
            <br />
                <div class='center'>
                    <input type="submit" name="Submit" class='button' value="Add template" />
                </div>
            </form>
        </div>

        <?php
        // tabs content
        $req->execute();
        $i=2;
        while ($users = $req->fetch()) {
        ?>
        <div id='tpl-<?php echo $i;?>'>
<img class='align_right' src='themes/<?php echo $_SESSION['prefs']['theme'];?>/img/trash.png' title='delete' alt='delete' onClick="deleteThis('<?php echo $users['id'];?>','tpl', 'ucp.php')" />
        <form action='ucp-exec.php' method='post'>
        <input type='hidden' name='tpl_form' />
        <?php
            echo "<input type='hidden' name='tpl_id[]' value='".$users['id']."' />";
            echo "<input name='tpl_name[]' value='".stripslashes($users['name'])."' /><br />";
            echo "<textarea name='tpl_body[]' class='mceditable' style='height:500px;'>".stripslashes($users['body'])."</textarea><br />";
            echo "<div class='center'>";
            echo "<input type='submit' name='Submit' class='button' value='Edit template' />";
            echo "</div>";
            echo "</form>";
            echo "</div>";
            $i++;
        }
        ?>
        </div><!-- end #tpl -->
    </div>

    <h3><a href='#keyboard'>KEYBOARD SHORTCUTS</a></h3>

    <div>
        <div class='align_left'>
            <form action='ucp-exec.php' method='post'>
                <br />
                <input type='hidden' name='shortcuts'>
                Create item : <span style='margin-left:59px'><input type='text' size='1' maxlength='1' value='<?php echo $_SESSION['prefs']['shortcuts']['create'];?>' name='create' /></span>

                <br />
                Edit item : <span style='margin-left:84px'><input type='text' size='1' maxlength='1' value='<?php echo $_SESSION['prefs']['shortcuts']['edit'];?>' name='edit' /></span>
                <br />
                Submit : <span style='margin-left:102px'><input type='text' size='1' maxlength='1' value='<?php echo $_SESSION['prefs']['shortcuts']['submit'];?>' name='submit' /></span>
                <br />
                Show TODOlist : <span style='margin-left:30px'><input type='text' size='1' maxlength='1' value='<?php echo $_SESSION['prefs']['shortcuts']['todo'];?>' name='todo' /></span>
                <!-- SUBMIT BUTTON -->
                <br />
                <br />
                <div class='center'>
                    <input type="submit" name="Submit" class='button' value="Change shortcuts" />
                </div>
            </form>
        </div>
    </div>
</div><!-- end accordion div -->

<?php require_once('inc/footer.php');?>

<script>
// hover to choose theme
function setTmpTheme(theme){
    document.getElementById('maincss').href = 'themes/'+theme+'/style.css';
}
// READY ? GO !!
$(document).ready(function() {
    // ACCORDION
    $( "#accordion" ).accordion({
        heightStyle: 'content',
        animate: 'easeOutExpo',
        collapsible: true,
        active: false
    });
    // Give focus to password field
    document.getElementById('currpass').focus();
    // add tabs to templates
    $( "#tpl" ).tabs();
    // TinyMCE
    tinymce.init({
        mode : "specific_textareas",
        editor_selector : "mceditable",
        content_css : "css/tinymce.css",
        plugins : "table textcolor searchreplace code fullscreen insertdatetime paste charmap save",
        toolbar1: "undo redo | bold italic underline | alignleft aligncenter alignright alignjustify | superscript subscript | bullist numlist outdent indent | forecolor backcolor | charmap",
        removed_menuitems : "newdocument"
    });
});
</script>

