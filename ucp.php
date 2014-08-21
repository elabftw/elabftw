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
require_once('inc/info_box.php');
// SQL for UCP
$sql = "SELECT username, email, firstname, lastname, phone, cellphone, skype, website FROM users WHERE userid = ".$_SESSION['userid'];
$req = $pdo->prepare($sql);
$req->execute();
$users = $req->fetch();

// BEGIN UCP PAGE
?>
<script src="js/tinymce/tinymce.min.js"></script>

<div class='menu'>
    <ul>
        <li class='tabhandle' id='tab1'>Account</li>
        <li class='tabhandle' id='tab2'>Preferences</li>
        <li class='tabhandle' id='tab3'>Templates</li>
    </ul>
</div>

<div id='ucp'>
    <!-- *********************** -->
    <div class='box divhandle' id='tab1div'>

        <form method="post" action="ucp-exec.php">
            <label class='block' for='currpass'>Enter your current password to change personnal infos</label>
            <input id='currpass' name="currpass" type="password" required />
            <label class='block' for='newpass'>New password</label>
            <input name="newpass" type="password" />
            <label class='block' for='cnewpass'>Confirm new password</label>
            <input name="cnewpass" type="password" />
            <label class='block' for='email'>Change Email</label>
            <input name="email" type="email" value='<?php echo $users['email'];?>' cols='20' rows='1' />
            <label class='block' for='username'>Username</label>
            <input name="username" value='<?php echo $users['username'];?>' cols='20' rows='1' />
            <label class='block' for='firstname'>Firstname</label>
            <input name="firstname" value='<?php echo $users['firstname'];?>' cols='20' rows='1' />
            <label class='block' for='lastname'>Lastname</label>
            <input name="lastname" value='<?php echo $users['lastname'];?>' cols='20' rows='1' />
            <label class='block' for='phone'>Phone</label>
            <input name="phone" value='<?php echo $users['phone'];?>' cols='20' rows='1' />
            <label class='block' for='cellphone'>Cellphone</label>
            <input name="cellphone" value='<?php echo $users['cellphone'];?>' cols='20' rows='1' />
            <label class='block' for='skype'>Skype</label>
            <input name="skype" value='<?php echo $users['skype'];?>' cols='20' rows='1' />
            <label class='block' for='website'>Website</label>
            <input name="website" type="url" value='<?php echo $users['website'];?>' cols='20' rows='1' />
            <div class='center'>
                <button type="submit" name="Submit" class='button'>Update profile</button>
            </div>
        </form>

    </div>
    <!-- *********************** -->
    <div class='divhandle' id='tab2div'>

        <form action='ucp-exec.php' method='post'>
            <section class='box'>

                <h3>DISPLAY</h3>
                <hr>
                <p style='margin-top:20px;'>
                <label for='radio_view_default'>Default</label>
                <input id='radio_view_default' type='radio' name='display' value='default' 
                <?php echo ($_SESSION['prefs']['display'] === 'default') ? "checked" : "";?>
                 />

                <label for='radio_view_compact'>Compact</label>
                <input id='radio_view_compact' type='radio' name='display' value='compact' 
                <?php echo ($_SESSION['prefs']['display'] === 'compact') ? "checked" : "";?>
                 />
                </p>

                <p style='margin-top:20px;'>
                <label for='order'>Order by :</label>
                <select id='order' name="order">
                    <option
                    <?php
                    if ($_SESSION['prefs']['order'] === 'date'){
                        echo ' selected ';}?>value="date">date</option>
                    <option
                    <?php
                    if ($_SESSION['prefs']['order'] === 'id'){
                        echo ' selected ';}?>value="id">item ID</option>
                    <option
                    <?php
                    if ($_SESSION['prefs']['order'] === 'title'){
                        echo ' selected ';}?>value="title">title</option>
                </select>

                with
                <select name="sort">
                    <option
                    <?php
                    if ($_SESSION['prefs']['sort'] === 'desc'){
                        echo ' selected ';}?>value="desc">newer first</option>
                    <option
                    <?php
                    if ($_SESSION['prefs']['sort'] === 'asc'){
                        echo ' selected ';}?>value="asc">older first</option>
                </select>

                <p style='margin-top:20px;'>
                <label for='limit'>Items per page :</label>
                <input id='limit' type='text' size='2' maxlength='2' value='<?php echo $_SESSION['prefs']['limit'];?>' name='limit'>
                </p>
            </section>

            <section class='box'>
                <h3>KEYBOARD SHORTCUTS</h3>
                <hr>
                <p>
                    <table>
                        <tr><th>Action</th><th>Shortcut</th></tr>

                        <tr><td>Create</td><td>
                        <input id='create' type='text' size='1' maxlength='1' value='<?php echo $_SESSION['prefs']['shortcuts']['create'];?>' name='create' />
                        </td></tr>

                        <tr><td>Edit</td><td>
                        <input id='edit' type='text' size='1' maxlength='1' value='<?php echo $_SESSION['prefs']['shortcuts']['edit'];?>' name='edit' />
                        </td></tr>

                        <tr><td>Submit</td><td>
                        <input id='key_submit' type='text' size='1' maxlength='1' value='<?php echo $_SESSION['prefs']['shortcuts']['submit'];?>' name='submit' />
                        </td></tr>

                        <tr><td>TODO list</td><td>
                        <input id='todolist' type='text' size='1' maxlength='1' value='<?php echo $_SESSION['prefs']['shortcuts']['todo'];?>' name='todo' />
                        </td></tr>
                    </table>
                </p>
            </section>

            <section class='box'>
                <h3>ALERT</h3>
                <hr>
                <p>
                <label for='close_warning'>Display a warning before closing an edit window/tab ?</label>
                <input id='close_warning' type='checkbox' name='close_warning' <?php
                if (isset($_SESSION['prefs']['close_warning']) && $_SESSION['prefs']['close_warning'] === 1) {
                    echo "checked='checked'  ";
                };?> />
            </section>
            <div style='margin-top:30px;' class='center'>
                <button type="submit" name="Submit" class='button'>Save preferences</button>
            </div>
                </p>

        </form>

    </div>
    <!-- *********************** -->
    <div class='divhandle' id='tab3div'>
        <div id='tpl'>
        <?php // SQL TO GET TEMPLATES
        $sql = "SELECT id, body, name FROM experiments_templates WHERE userid = ".$_SESSION['userid'];
        $req = $pdo->prepare($sql);
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
                    <button type="submit" name="Submit" class='button'>Add template</button>
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
            <img class='align_right' src='img/small-trash.png' title='delete' alt='delete' onClick="deleteThis('<?php echo $users['id'];?>','tpl', 'ucp.php')" />
            <form action='ucp-exec.php' method='post'>
            <input type='hidden' name='tpl_form' />
            <?php
                echo "<input type='hidden' name='tpl_id[]' value='".$users['id']."' />";
                echo "<input name='tpl_name[]' value='".stripslashes($users['name'])."' /><br />";
                echo "<textarea name='tpl_body[]' class='mceditable' style='height:500px;'>".stripslashes($users['body'])."</textarea><br />";
                echo "<div class='center'>";
                echo "<button type='submit' name='Submit' class='button'>Edit template</button>";
                echo "</div>";
                echo "</form>";
                echo "</div>";
                $i++;
            }
            ?>
        </div>
    </div>
    <!-- *********************** -->
</div>


<?php require_once('inc/footer.php');?>

<script>
// READY ? GO !!
$(document).ready(function() {
    // TABS

    // init
    $(".divhandle").hide();
    $("#tab1div").show();
    $("#tab1").addClass('selected');

    $(".tabhandle" ).click(function(event) {
        var tabhandle = '#' + event.target.id;
        var divhandle = '#' + event.target.id + 'div';
        $(".divhandle").hide();
        $(divhandle).show();
        $(".tabhandle").removeClass('selected');
        $(tabhandle).addClass('selected');
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
        plugins : "table textcolor searchreplace code fullscreen insertdatetime paste charmap save image link",
        toolbar1: "undo redo | bold italic underline | fontsizeselect | alignleft aligncenter alignright alignjustify | superscript subscript | bullist numlist outdent indent | forecolor backcolor | charmap | link",
        removed_menuitems : "newdocument"
    });
});
</script>

