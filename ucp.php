<?php
/**
 * ucp.php
 *
 * @author Nicolas CARPi <nicolas.carpi@curie.fr>
 * @copyright 2012 Nicolas CARPi
 * @see http://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */
namespace Elabftw\Elabftw;

/**
 * User Control Panel
 *
 */
?>
<script src="js/tinymce/tinymce.js"></script>
<?php
require_once 'app/init.inc.php';
$page_title = _('User Control Panel');
$selected_menu = null;
require_once('app/head.inc.php');

$Users = new Users();
$user = $Users->read($_SESSION['userid']);

// BEGIN UCP PAGE
?>

<menu>
    <ul>
        <li class='tabhandle' id='tab1'><?= _('Preferences') ?></li>
        <li class='tabhandle' id='tab2'><?= _('Account') ?></li>
        <li class='tabhandle' id='tab3'><?= _('Templates') ?></li>
    </ul>
</menu>

<!-- *********************** -->
<div class='divhandle' id='tab1div'>

    <form action='app/ucp-exec.php' method='post'>
        <section class='box'>
            <h3><?= _('Language') ?></h3>
            <hr>
            <p>
            <select id='lang' name="lang">
<?php
$langsArr = Tools::getLangsArr();
foreach ($langsArr as $lang => $text) {
    echo "<option ";
    if ($_SESSION['prefs']['lang'] === $lang) {
        echo ' selected ';
    }
    echo "value='" . $lang . "'>" . $text . "</option>";
}
?>
            </select>
        </section>
        <section class='box'>

            <h3><?= _('Display') ?></h3>
            <hr>
            <p>
            <input id='radio_view_default' type='radio' name='display' value='default' 
            <?= ($_SESSION['prefs']['display'] === 'default') ? "checked" : "" ?>
             />
            <label for='radio_view_default'><?= _('Default') ?></label>

            <input id='radio_view_compact' type='radio' name='display' value='compact' 
            <?= ($_SESSION['prefs']['display'] === 'compact') ? "checked" : "" ?>
             />
            <label for='radio_view_compact'><?= _('Compact') ?></label>
            </p>

            <p style='margin-top:20px;'>
            <label for='limit'><?= _('Items per page:') ?></label>
            <input id='limit' type='text' size='2' maxlength='2' value='<?= $_SESSION['prefs']['limit'] ?>' name='limit'>
            </p>
        </section>

        <section class='box'>
            <h3><?= _('Keyboard Shortcuts') ?></h3>
            <p><?= _('Only lowercase letters are allowed.') ?></p>
            <hr>
            <p>
                <table>
                <tr><th><?= _('Action') ?></th>
                    <th><?= _('Shortcut') ?></th></tr>

                <tr><td><?= _('Create') ?></td><td>
                    <input type='text' pattern='[a-z]' size='1' maxlength='1' value='<?= $_SESSION['prefs']['shortcuts']['create'] ?>' name='key_create' />
                    </td></tr>

                    <tr><td><?= _('Edit') ?></td><td>
                    <input type='text' pattern='[a-z]' size='1' maxlength='1' value='<?= $_SESSION['prefs']['shortcuts']['edit'] ?>' name='key_edit' />
                    </td></tr>

                    <tr><td><?= _('Submit') ?></td><td>
                    <input type='text' pattern='[a-z]' size='1' maxlength='1' value='<?= $_SESSION['prefs']['shortcuts']['submit'] ?>' name='key_submit' />
                    </td></tr>

                    <tr><td><?= _('TODO list') ?></td><td>
                    <input type='text' pattern='[a-z]' size='1' maxlength='1' value='<?= $_SESSION['prefs']['shortcuts']['todo'] ?>' name='key_todo' />
                    </td></tr>
                </table>
            </p>
        </section>

        <section class='box'>
            <h3><?= _('Miscellaneous') ?></h3>
            <hr>
            <p>
            <input id='show_team' type='checkbox' name='show_team'
<?php
if (isset($_SESSION['prefs']['show_team']) && $_SESSION['prefs']['show_team'] === 1) {
    echo "checked='checked'  ";
};?>
            />
            <label for='show_team'><?= _('Show experiments from the team on the Experiments page?'); ?></label>
            <br>
            <input id='close_warning' type='checkbox' name='close_warning'
<?php
if (isset($_SESSION['prefs']['close_warning']) && $_SESSION['prefs']['close_warning'] === 1) {
    echo "checked='checked'  ";
};?>
            />
            <label for='close_warning'><?= _('Display a warning before closing an edit window/tab?') ?></label>
            <br>
            <input id='chem_editor' type='checkbox' name='chem_editor'
<?php
if (isset($_SESSION['prefs']['chem_editor']) && $_SESSION['prefs']['chem_editor'] === 1) {
    echo "checked='checked'  ";
};?>
            />
            <label for='chem_editor'><?= _('Display the molecule drawer in edit mode?') ?></label>
            </p>
        </section>

        <div style='margin-top:30px;' class='center'>
        <button type="submit" name="Submit" class='button'><?= _('Save') ?></button>
        </div>
            </p>
    </form>

</div>
<!-- *********************** -->
<div class='divhandle' id='tab2div'>
    <div class='box'>

    <form method="post" action="app/ucp-exec.php">
        <div class='row'>
            <div class='col-md-6'>
                <h4><?= _('Modify your personal informations') ?></h4>
                <label class='block' for='currpass'><?= _('Enter your password to edit infos.') ?></label>
                <input id='currpass' name="currpass" type="password" required />
            </div>
            <div class='col-md-6'>
                <h4><?= _('Modify your password') ?></h4>
                <label class='block' for='newpass'><?= _('New password') ?></label>
                <input name="newpass" type="password" />
                <label class='block' for='cnewpass'><?= _('Confirm new password') ?></label>
                <input name="cnewpass" type="password" />
            </div>
        </div>

        <hr><br>

        <h4><?= _('Modify your identity') ?></h4>

        <div class='row'>
            <div class='col-md-6'>
                <label class='block' for='firstname'><?= _('Firstname') ?></label>
                <input name="firstname" value='<?= $user['firstname'] ?>' cols='20' rows='1' />
            </div>
        </div>

        <div class='row'>
            <div class='col-md-6'>
                <label class='block' for='lastname'><?= _('Lastname') ?></label>
                <input name="lastname" value='<?= $user['lastname'] ?>' cols='20' rows='1' />
            </div>
            <div class='col-md-6'>
                <label class='block' for='email'><?= _('Email') ?></label>
                <input name="email" type="email" value='<?= $user['email'] ?>' cols='20' rows='1' />
            </div>
        </div>

        <hr><br>
        <h4><?= _('Modify your contact information') ?></h4>
        <div class='row'>
            <div class='col-md-6'>
                <label class='block' for='phone'><?= _('Phone') ?> </label>
                <input name="phone" value='<?= $user['phone'] ?>' cols='20' rows='1' />
            </div>
            <div class='col-md-6'>
                <label class='block' for='cellphone'><?= _('Mobile') ?></label>
                <input name="cellphone" value='<?= $user['cellphone'] ?>' cols='20' rows='1' />
            </div>
        </div>
        <div class='row'>
            <div class='col-md-6'>
                <label class='block' for='skype'><?= _('Skype') ?></label>
                <input name="skype" value='<?= $user['skype'] ?>' cols='20' rows='1' />
            </div>
            <div class='col-md-6'>
                <label class='block' for='website'><?= _('Website') ?></label>
                <input name="website" type="url" value='<?= $user['website'] ?>' cols='20' rows='1' />
            </div>
        </div>

    </div>
        <div class='submitButtonDiv'>
            <button type="submit" name="Submit" class='button'><?= _('Update profile') ?></button>
        </div>
    </form>

</div>
<!-- *********************** -->
<div class='divhandle' id='tab3div'>

<?php // SQL TO GET TEMPLATES
$Templates = new Templates($_SESSION['team_id']);
$templatesArr = $Templates->readFromUserid($_SESSION['userid']);


echo "<div class='box new-tpl-box'>";
echo "<h3>" . _('Experiments Templates') . "</h3>";
echo "<ul class='nav nav-pills' role='tablist'>";
// tabs titles
echo "<li class='subtabhandle badge badgetab badgetabactive' id='subtab_1'>" . _('Create New') . "</li>";
foreach ($templatesArr as $template) {
    echo "<li class='sortable subtabhandle badge badgetab' id='subtab_" . $template['id'] . "'>" . stripslashes($template['name']) . "</li>";
}
echo "</ul>";
?>
    <!-- CREATE NEW TPL TAB -->
    <div class='subdivhandle' id='subtab_1div'>
    <p onClick="$('#import_tpl').toggle()" style='cursor:pointer'><img src='img/add.png' title='import template' alt='import' /><?= _('Import from File') ?></p>
        <form action='app/ucp-exec.php' method='post'>
            <input type='hidden' name='new_tpl_form' />
            <input type='file' accept='.elabftw.tpl' id='import_tpl'>
            <input required type='text' name='new_tpl_name' id='new_tpl_name' pattern='.{3,}' placeholder='<?= _('Name of the Template') ?>' />
            <br>
            <textarea name='new_tpl_body' id='new_tpl_txt' style='height:500px;' class='mceditable' rows='50' cols='60'></textarea>
            <br>
            <div class='center'>
                <button type="submit" name="Submit" class='button'><?= _('Add template') ?></button>
            </div>
        </form>
    </div>

    <?php
    // tabs content

    foreach ($templatesArr as $template) {
        echo "<div class='subdivhandle' id='subtab_" . $template['id'] . "div'>";
        echo "<img class='align_right' src='img/download.png' title='Export Template' alt='Export' ";
        echo "onClick=\"exportTpl('" . $template['name'] . "', " . $template['id'] . ")\" />";
        echo "<img class='align_right' src='img/small-trash.png' title='Delete' alt='Delete' ";
        echo "onClick=\"templatesDestroy(" . $template['id'] . ")\" />";
        echo "<form action='app/ucp-exec.php' method='post'>";
        echo "<input type='hidden' name='tpl_form' />";
        echo "<input type='hidden' name='tpl_id[]' value='" . $template['id'] . "' />";
        echo "<input name='tpl_name[]' value='" . stripslashes($template['name']) . "' style='width:90%' /><br />";
        echo "<textarea id='" . $template['id'] . "' name='tpl_body[]' class='mceditable' style='height:500px;'>" .
            stripslashes($template['body']) . "</textarea><br />";
        echo "<div class='center'>";
        echo "<button type='submit' name='Submit' class='button'>" . _('Edit template') . "</button>";
        echo "</div>";
        echo "</form>";
        echo "</div>";
    }
    ?>
    </div>
    </div>
<!-- *********************** -->

<!-- to export templates -->
<script src='js/file-saver.js/FileSaver.js'></script>
<script>
// READY ? GO !!
$(document).ready(function() {

    // hide the file input
    $('#import_tpl').hide();
    $('#import_tpl').on('change', function(e){
        var title = document.getElementById('import_tpl').value.replace(".elabftw.tpl", "");
        readFile(this.files[0], function(e) {
            tinyMCE.get('new_tpl_txt').setContent(e.target.result);
            $('#new_tpl_name').val(title);
            $('#import_tpl').hide();
        });
    });

    $('.nav-pills').sortable({
        // limit to horizontal dragging
        axis : 'x',
        helper : 'clone',
        // we don't want the Create new pill to be sortable
        cancel: "#subtab_1",
        // do ajax request to update db with new order
        update: function(event, ui) {
            // send the orders as an array
            var ordering = $(".nav-pills").sortable("toArray");

            $.post("app/controllers/AdminController.php", {
                'updateOrdering': true,
                'table': 'experiments_templates',
                'ordering': ordering
            }).done(function(data) {
                var json = JSON.parse(data);
                if (json.res) {
                    notif(json.msg, 'ok');
                } else {
                    notif(json.msg, 'ko');
                }
            });
        }
    });

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
    var initdiv = '#subtab_' + tab + 'div';
    var inittab = '#subtab_' + tab;
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

    // TinyMCE
    tinymce.init({
        mode : "specific_textareas",
        editor_selector : "mceditable",
        content_css : "app/css/tinymce.css",
        plugins : "table textcolor searchreplace code fullscreen insertdatetime paste charmap save image link",
        toolbar1: "undo redo | bold italic underline | fontsizeselect | alignleft aligncenter alignright alignjustify | superscript subscript | bullist numlist outdent indent | forecolor backcolor | charmap | link",
        removed_menuitems : "newdocument",
        language : '<?= $_SESSION['prefs']['lang'] ?>'
    });
});
</script>

<?php require_once('app/footer.inc.php');
