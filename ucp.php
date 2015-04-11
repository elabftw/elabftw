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
require_once 'inc/common.php';
require_once 'inc/locale.php';
$page_title = _('User Control Panel');
$selected_menu = null;
require_once('inc/head.php');
require_once('inc/info_box.php');

// SQL for UCP
$sql = "SELECT username, email, firstname, lastname, phone, cellphone, skype, website FROM users WHERE userid = " . $_SESSION['userid'];
$req = $pdo->prepare($sql);
$req->execute();
$users = $req->fetch();

// BEGIN UCP PAGE
?>
<script src="js/tinymce/tinymce.min.js"></script>
<script src="js/bootstrap/js/tab.js"></script>

<menu>
    <ul>
        <li class='tabhandle' id='tab1'><?php echo _('Account'); ?></li>
        <li class='tabhandle' id='tab2'><?php echo _('Preferences'); ?></li>
        <li class='tabhandle' id='tab3'><?php echo _('Templates'); ?></li>
    </ul>
</menu>

<!-- *********************** -->
<div class='divhandle' id='tab1div'>
    <div class='box'>

    <form method="post" action="app/ucp-exec.php">
        <div class='row'>
            <div class='col-md-5'>
                <h4><?php echo _('Modify your identity'); ?></h4>
                <br /><br />
                <div class="form-group">
                    <label class="col-sm-4 control-label txtright"><?php echo _('Firstname'); ?></label>
                    <div class="col-sm-8">
                        <input name="firstname" class="form-control" value='<?php echo $users['firstname']; ?>' />
                    </div>
                </div>
                <div class="form-group">
                    <label class="col-sm-4 control-label txtright"><?php echo _('Lastname'); ?></label>
                    <div class="col-sm-8">
                        <input name="lastname" class="form-control" value='<?php echo $users['lastname']; ?>' />
                    </div>
                </div>
                <div class="form-group">
                    <label class="col-sm-4 control-label txtright"><?php echo _('Username'); ?></label>
                    <div class="col-sm-8">
                        <input name="username" class="form-control" value='<?php echo $users['username']; ?>' />
                    </div>
                </div>
                <div class="form-group">
                    <label class="col-sm-4 control-label txtright"><?php echo _('Email'); ?></label>
                    <div class="col-sm-8">
                        <input name="email" type="email" class="form-control" value='<?php echo $users['email']; ?>' />
                    </div>
                </div>
                <br class="clearfloat" /><br /><br />
                <h4><?php echo _('Modify your contact information'); ?></h4>
                <br /><br />
                <div class="form-group">
                    <label class="col-sm-4 control-label txtright"><?php echo _('Phone'); ?></label>
                    <div class="col-sm-8">
                        <input name="phone" class="form-control" value='<?php echo $users['phone']; ?>' />
                    </div>
                </div>
                <div class="form-group">
                    <label class="col-sm-4 control-label txtright"><?php echo _('Mobile'); ?></label>
                    <div class="col-sm-8">
                        <input name="cellphone" class="form-control" value='<?php echo $users['cellphone']; ?>' />
                    </div>
                </div>
                <div class="form-group">
                    <label class="col-sm-4 control-label txtright"><?php echo _('Skype'); ?></label>
                    <div class="col-sm-8">
                        <input name="skype" class="form-control" value='<?php echo $users['skype']; ?>' placeholder="<?php echo _('Username'); ?>" />
                    </div>
                </div>
                <div class="form-group">
                    <label class="col-sm-4 control-label txtright"><?php echo _('Website'); ?></label>
                    <div class="col-sm-8">
                        <input name="website" type="url" class="form-control" value='<?php echo $users['website']; ?>' placeholder="http://" />
                    </div>
                </div>
            </div>
            <div class='col-md-5 col-md-offset-2'>
                <h4><?php echo _('Modify your password'); ?></h4>
                <br /><br />
                <div class="form-group">
                    <label class="col-sm-8 control-label txtright"><?php echo _('New password'); ?></label>
                    <div class="col-sm-4">
                        <input name="newpass" type="password" class="form-control" />
                    </div>
                </div>
                <div class="form-group">
                    <label class="col-sm-8 control-label txtright"><?php echo _('Confirm new password'); ?></label>
                    <div class="col-sm-4">
                        <input name="cnewpass" type="password" class="form-control" />
                    </div>
                </div>
            </div>
        </div>
        <div class="row">
            <div class="col-sm-4 col-sm-offset-4">
                <br /><br />
                <h4><?php echo _('Modify your personal informations'); ?></h4>
                <div class="form-group has-error">
                    <label class='control-label' for='currpass'><?php echo _('Enter your password to edit infos.'); ?></label><br />
                    <input name="currpass" type="password" class="form-control " required /><!--id='currpass'-->
                </div>
            </div>
        </div>
    </div>
        <div class='submitButtonDiv'>
            <button type="submit" name="Submit" class='btn btn-elab btn-lg'><?php echo _('Update profile'); ?></button>
        </div>
    </form>

</div>
<!-- *********************** -->
<div class='divhandle' id='tab2div'>

    <form action='app/ucp-exec.php' method='post'>
        <section class='box'>
            <h3><?php echo _('Language'); ?></h3>
            <hr>
            <div class="row">
                <div class="col-xs-2">
                    <select id='lang' name="lang" class="form-control">
                        <option
                        <?php
                        if ($_SESSION['prefs']['lang'] === 'en_GB') {
                            echo ' selected '; }?>value="en_GB">en_GB</option>
                        <option
                        <?php
                        if ($_SESSION['prefs']['lang'] === 'ca_ES') {
                            echo ' selected '; }?>value="ca_ES">ca_ES</option>
                        <option
                        <?php
                        if ($_SESSION['prefs']['lang'] === 'de_DE') {
                            echo ' selected '; }?>value="de_DE">de_DE</option>
                        <option
                        <?php
                        if ($_SESSION['prefs']['lang'] === 'es_ES') {
                            echo ' selected '; }?>value="es_ES">es_ES</option>
                        <option
                        <?php
                        if ($_SESSION['prefs']['lang'] === 'fr_FR') {
                            echo ' selected '; }?>value="fr_FR">fr_FR</option>
                        <option
                        <?php
                        if ($_SESSION['prefs']['lang'] === 'it_IT') {
                            echo ' selected '; }?>value="it_IT">it_IT</option>
                        <option
                        <?php
                        if ($_SESSION['prefs']['lang'] === 'pt_BR') {
                            echo ' selected '; }?>value="pt_BR">pt_BR</option>
                        <option
                        <?php
                        if ($_SESSION['prefs']['lang'] === 'zh_CN') {
                            echo ' selected '; }?>value="zh_CN">zh_CN</option>
                    </select>
                </div>
            </div>
        </section>

        <section class='box'>
            <h3><?php echo _('ALERT'); ?></h3>
            <hr>
            <p>
            <input id='close_warning' type='checkbox' name='close_warning' <?php
            if (isset($_SESSION['prefs']['close_warning']) && $_SESSION['prefs']['close_warning'] === 1) {
                echo "checked='checked'  ";
            };?> />
            <label for='close_warning'>&nbsp;<?php echo _('Display a warning before closing an edit window/tab ?'); ?></label>
            </p>
        </section>

        <section class='box'>

            <h3><?php echo _('DISPLAY'); ?></h3>
            <hr>
            <p id='display'>
                <input id='radio_view_default' type='radio' name='display' value='default' 
                <?php echo ($_SESSION['prefs']['display'] === 'default') ? "checked" : ""; ?>
                 />
                <label for='radio_view_default'><?php echo _('Default'); ?></label>
                <br />
                <input id='radio_view_compact' type='radio' name='display' value='compact' 
                <?php echo ($_SESSION['prefs']['display'] === 'compact') ? "checked" : ""; ?>
                 />
                <label for='radio_view_compact'><?php echo _('Compact'); ?></label>
            </p>

            <p style='margin-top:20px;'>
                <label class="boxlabel" for='order'><?php echo _('Order by:'); ?></label>
                <select id='order' name="order" class="form-control">
                    <option
                    <?php
                    if ($_SESSION['prefs']['order'] === 'date') {
                        echo ' selected '; }?>value="date"><?php echo _('Date'); ?></option>
                    <option
                    <?php
                    if ($_SESSION['prefs']['order'] === 'id') {
                        echo ' selected '; }?>value="id">ID</option>
                    <option
                    <?php
                    if ($_SESSION['prefs']['order'] === 'title') {
                        echo ' selected '; }?>value="title"><?php echo _('Title'); ?></option>
                </select>
                <label class="boxlabel" for='sort'><?php echo _('with'); ?></label>
                <select id="sort" name="sort" class="form-control">
                    <option
                    <?php
                    if ($_SESSION['prefs']['sort'] === 'desc') {
                        echo ' selected '; }?>value="desc"><?php echo _('newer first'); ?></option>
                    <option
                    <?php
                    if ($_SESSION['prefs']['sort'] === 'asc') {
                        echo ' selected '; }?>value="asc"><?php echo _('older first'); ?></option>
                </select>
                <br class="clearfloat" />
            </p>

            <p style='margin-top:20px;'>
            <label class="boxlabel" for='limit'><?php echo _('Items per page:'); ?></label>
            <input id='limit' type='text' size='3' maxlength='3' value='<?php echo $_SESSION['prefs']['limit']; ?>' name='limit' class="form-control">
            </p>
        </section>

        <section class='box'>
            <h3><?php echo _('KEYBOARD SHORTCUTS'); ?></h3>
            <hr>
            <p>
                <table>
                <tr><th><?php echo _('Action'); ?></th><th><?php echo _('Shortcut'); ?></th></tr>

                <tr><td><?php echo _('Create'); ?></td><td>
                    <input id='create' type='text' size='1' maxlength='1' value='<?php echo $_SESSION['prefs']['shortcuts']['create']; ?>' name='create' />
                    </td></tr>

                    <tr><td><?php echo _('Edit'); ?></td><td>
                    <input id='edit' type='text' size='1' maxlength='1' value='<?php echo $_SESSION['prefs']['shortcuts']['edit']; ?>' name='edit' />
                    </td></tr>

                    <tr><td><?php echo _('Submit'); ?></td><td>
                    <input id='key_submit' type='text' size='1' maxlength='1' value='<?php echo $_SESSION['prefs']['shortcuts']['submit']; ?>' name='submit' />
                    </td></tr>

                    <tr><td><?php echo _('TODO list'); ?></td><td>
                    <input id='todolist' type='text' size='1' maxlength='1' value='<?php echo $_SESSION['prefs']['shortcuts']['todo']; ?>' name='todo' />
                    </td></tr>
                </table>
            </p>
        </section>

        <div style='margin-top:30px;' class='center'>
        <button type="submit" name="Submit" class='btn btn-elab btn-lg'><?php echo _('Save'); ?></button>
        </div>
    </form>

</div>
<!-- *********************** -->
<div class='divhandle' id='tab3div'>

    <?php // SQL TO GET TEMPLATES
    $sql = "SELECT id, body, name FROM experiments_templates WHERE userid = " . $_SESSION['userid'];
    $req = $pdo->prepare($sql);
    $req->execute();

    echo "<ul class='nav nav-pills' role='tablist'>";
    // tabs titles
    echo "<li class='btn btn-success btn-xs' style='margin-right:10px;' id='subtab1'>" . _('Create new') . "</li>";
    $i = 2;
    while ($exp_tpl = $req->fetch()) {
        echo "<li class='subtabhandle badge badgetab' id='subtab" . $i . "'>" . stripslashes($exp_tpl['name']) . "</li>";
        $i++;
    }
    echo "</ul>";
    ?>
    <!-- create new tpl tab -->
    <div class='subdivhandle' id='subtab1div'>
        <br />
        <form action='app/ucp-exec.php' method='post'>
            <input type='hidden' name='new_tpl_form' />
            <div class="row">
                <div class="col-md-4">
                    <input class="form-control" type='text' name='new_tpl_name' placeholder='<?php echo _('Name of the template'); ?>' required />
                </div>
            </div>
            <textarea name='new_tpl_body' id='new_tpl_txt' style='height:500px;' class='mceditable' rows='50' cols='60'></textarea>
        <br />
            <div class='center'>
                <button type="submit" name="Submit" class='btn btn-elab btn-lg'><?php echo _('Add template'); ?></button>
            </div>
        </form>
    </div>

    <?php
    // tabs content
    $req->execute();
    $i = 2;
    while ($exp_tpl = $req->fetch()) {
    ?>
    <div class='subdivhandle' id='subtab<?php echo $i; ?>div'>
        <img class='align_right' src='img/small-trash.png' title='delete' alt='delete' onClick="deleteThis('<?php echo $exp_tpl['id']; ?>','tpl', 'ucp.php')" />
        <form action='app/ucp-exec.php' method='post'>
        <input type='hidden' name='tpl_form' />
        <?php
            echo "<input type='hidden' name='tpl_id[]' value='" . $exp_tpl['id'] . "' />";
            echo "<input name='tpl_name[]' value='" . stripslashes($exp_tpl['name']) . "' /><br />";
            echo "<textarea name='tpl_body[]' class='mceditable' style='height:500px;'>" . stripslashes($exp_tpl['body']) . "</textarea><br />";
            echo "<div class='center'>";
            echo "<button type='submit' name='Submit' class='button'>" . _('Edit template') . "</button>";
            echo "</div>";
            echo "</form>";
            echo "</div>";
            $i++;
        }
        ?>
    </div>
</div>
<!-- *********************** -->

<?php require_once('inc/footer.php'); ?>

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
    // SUB TABS
    var tab = 1;
    var initdiv = '#subtab' + tab + 'div';
    var inittab = '#subtab' + tab;
    // init
    $(".subdivhandle").hide();
    $(initdiv).show();
    $(inittab).addClass('selected');

    $(".subtabhandle" ).click(function(event) {
        var tabhandle = '#' + event.target.id;
        var divhandle = '#' + event.target.id + 'div';
        $(".subdivhandle").hide();
        $(divhandle).show();
        $(".subtabhandle").removeClass('badgetabactive');
        $(tabhandle).addClass('badgetabactive');
    });
    // END SUB TABS
    // Give focus to password field
    document.getElementById('currpass').focus();
    // TinyMCE
    tinymce.init({
        mode : "specific_textareas",
        editor_selector : "mceditable",
        content_css : "css/tinymce.css",
        plugins : "table textcolor searchreplace code fullscreen insertdatetime paste charmap save image link",
        toolbar1: "undo redo | bold italic underline | fontsizeselect | alignleft aligncenter alignright alignjustify | superscript subscript | bullist numlist outdent indent | forecolor backcolor | charmap | link",
        removed_menuitems : "newdocument",
        language : '<?php echo $_SESSION['prefs']['lang']; ?>'
    });
});
</script>
