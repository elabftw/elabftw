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
require_once 'lang/'.$_SESSION['prefs']['lang'].'.php';
$page_title = UCP_TITLE;;
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

<menu>
    <ul>
        <li class='tabhandle' id='tab1'><?php echo UCP_ACCOUNT;?></li>
        <li class='tabhandle' id='tab2'><?php echo UCP_PREFERENCES;?></li>
        <li class='tabhandle' id='tab3'><?php echo UCP_TPL;?></li>
    </ul>
</menu>

<!-- *********************** -->
<div class='divhandle' id='tab1div'>
    <div class='box'>

    <form method="post" action="ucp-exec.php">
        <div class='two-columns'>
        <section style='height:150px'>
        <h4><?php echo UCP_H4_1;?></h4>
        <label class='block' for='currpass'><?php echo UCP_CURRPASS;?></label>
        <input id='currpass' name="currpass" type="password" required />
        </section>

        <section>
        <h4><?php echo UCP_H4_2;?></h4>
        <label class='block' for='firstname'><?php echo FIRSTNAME;?></label>
        <input name="firstname" value='<?php echo $users['firstname'];?>' cols='20' rows='1' />
        <label class='block' for='lastname'><?php echo LASTNAME;?></label>
        <input name="lastname" value='<?php echo $users['lastname'];?>' cols='20' rows='1' />
        <label class='block' for='username'><?php echo USERNAME;?></label>
        <input name="username" value='<?php echo $users['username'];?>' cols='20' rows='1' />
        <label class='block' for='email'><?php echo EMAIL;?></label>
        <input name="email" type="email" value='<?php echo $users['email'];?>' cols='20' rows='1' />
        </section>

        <section>
        <h4><?php echo UCP_H4_3;?></h4>
        <label class='block' for='newpass'><?php echo UCP_NEWPASS;?></label>
        <input name="newpass" type="password" />
        <label class='block' for='cnewpass'><?php echo UCP_CNEWPASS;?></label>
        <input name="cnewpass" type="password" />
        </section>

        <section>
        <h4><?php echo UCP_H4_4;?></h4>
        <label class='block' for='phone'><?php echo PHONE;?> </label>
        <input name="phone" value='<?php echo $users['phone'];?>' cols='20' rows='1' />
        <label class='block' for='cellphone'><?php echo MOBILE;?></label>
        <input name="cellphone" value='<?php echo $users['cellphone'];?>' cols='20' rows='1' />
        <label class='block' for='skype'><?php echo SKYPE;?></label>
        <input name="skype" value='<?php echo $users['skype'];?>' cols='20' rows='1' />
        <label class='block' for='website'><?php echo WEBSITE;?></label>
        <input name="website" type="url" value='<?php echo $users['website'];?>' cols='20' rows='1' />
        </section>
        </div>

    </div>
        <div class='submitButtonDiv'>
            <button type="submit" name="Submit" class='button'><?php echo UCP_BUTTON_1;?></button>
        </div>
    </form>

</div>
<!-- *********************** -->
<div class='divhandle' id='tab2div'>

    <form action='ucp-exec.php' method='post'>
        <section class='box'>

            <h3><?php echo UCP_H3_1;?></h3>
            <hr>
            <p id='display'>
            <label for='radio_view_default'><?php echo UCP_DEFAULT;?></label>
            <input id='radio_view_default' type='radio' name='display' value='default' 
            <?php echo ($_SESSION['prefs']['display'] === 'default') ? "checked" : "";?>
             />

                 <label for='radio_view_compact'><?php echo UCP_COMPACT;?></label>
            <input id='radio_view_compact' type='radio' name='display' value='compact' 
            <?php echo ($_SESSION['prefs']['display'] === 'compact') ? "checked" : "";?>
             />
            </p>

            <p style='margin-top:20px;'>
            <label for='order'><?php echo UCP_ORDER_BY;?></label>
            <select id='order' name="order">
                <option
                <?php
                if ($_SESSION['prefs']['order'] === 'date'){
                    echo ' selected ';}?>value="date"><?php echo DATE;?></option>
                <option
                <?php
                if ($_SESSION['prefs']['order'] === 'id'){
                    echo ' selected ';}?>value="id"><?php echo UCP_ITEM_ID;?></option>
                <option
                <?php
                if ($_SESSION['prefs']['order'] === 'title'){
                    echo ' selected ';}?>value="title"><?php echo TITLE;?></option>
            </select>

            <?php echo UCP_WITH;?>
            <select name="sort">
                <option
                <?php
                if ($_SESSION['prefs']['sort'] === 'desc'){
                    echo ' selected ';}?>value="desc"><?php echo UCP_NEWER;?></option>
                <option
                <?php
                if ($_SESSION['prefs']['sort'] === 'asc'){
                    echo ' selected ';}?>value="asc"><?php echo UCP_OLDER;?></option>
            </select>

            <p style='margin-top:20px;'>
            <label for='limit'><?php echo UCP_LIMIT;?></label>
            <input id='limit' type='text' size='2' maxlength='2' value='<?php echo $_SESSION['prefs']['limit'];?>' name='limit'>
            </p>
        </section>

        <section class='box'>
            <h3><?php echo UCP_H3_2;?></h3>
            <hr>
            <p>
                <table>
                <tr><th><?php echo ACTION;?></th><th><?php echo SHORTCUT;?></th></tr>

                <tr><td><?php echo CREATE;?></td><td>
                    <input id='create' type='text' size='1' maxlength='1' value='<?php echo $_SESSION['prefs']['shortcuts']['create'];?>' name='create' />
                    </td></tr>

                    <tr><td><?php echo EDIT;?></td><td>
                    <input id='edit' type='text' size='1' maxlength='1' value='<?php echo $_SESSION['prefs']['shortcuts']['edit'];?>' name='edit' />
                    </td></tr>

                    <tr><td><?php echo SUBMIT;?></td><td>
                    <input id='key_submit' type='text' size='1' maxlength='1' value='<?php echo $_SESSION['prefs']['shortcuts']['submit'];?>' name='submit' />
                    </td></tr>

                    <tr><td><?php echo TODO;?></td><td>
                    <input id='todolist' type='text' size='1' maxlength='1' value='<?php echo $_SESSION['prefs']['shortcuts']['todo'];?>' name='todo' />
                    </td></tr>
                </table>
            </p>
        </section>

        <section class='box'>
            <h3><?php echo UCP_H3_3;?></h3>
            <hr>
            <p>
            <label for='close_warning'><?php echo UCP_CLOSE_WARNING;?></label>
            <input id='close_warning' type='checkbox' name='close_warning' <?php
            if (isset($_SESSION['prefs']['close_warning']) && $_SESSION['prefs']['close_warning'] === 1) {
                echo "checked='checked'  ";
            };?> />
        </section>
        <div style='margin-top:30px;' class='center'>
        <button type="submit" name="Submit" class='button'><?php echo SAVE;?></button>
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
                <button type="submit" name="Submit" class='button'><?php echo UCP_ADD_TPL;?></button>
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
            echo "<button type='submit' name='Submit' class='button'>".UCP_EDIT_BUTTON."</button>";
            echo "</div>";
            echo "</form>";
            echo "</div>";
            $i++;
        }
        ?>
    </div>
</div>
<!-- *********************** -->

<?php require_once('inc/footer.php');?>

<script>
// READY ? GO !!
$(document).ready(function() {
    // TABS
    // get the tab=X parameter in the url
    var params = getGetParameters();
    var tab = parseInt(params['tab']);
    if (!isInt(tab)) {
        var tab = 1;
    }
    var initdiv = '#tab' + tab + 'div';
    var inittab = '#tab' + tab;
    // init
    $(".divhandle").hide();
    $(initdiv).show();
    $(inittab).addClass('selected');

    $(".tabhandle" ).click(function(event) {
        var tabhandle = '#' + event.target.id;
        var divhandle = '#' + event.target.id + 'div';
        $(".divhandle").hide();
        $(divhandle).show();
        $(".tabhandle").removeClass('selected');
        $(tabhandle).addClass('selected');
    });
    // END TABS
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
