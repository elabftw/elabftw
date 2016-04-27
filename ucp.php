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
require_once 'inc/common.php';
$page_title = _('User Control Panel');
$selected_menu = null;
require_once('inc/head.php');

$Users = new Users();
$user = $Users->read($_SESSION['userid']);

// BEGIN UCP PAGE
?>
<script src="js/tinymce/tinymce.min.js"></script>
<script src="js/bootstrap/js/tab.js"></script>

<menu>
    <ul>
        <li class='tabhandle' id='tab1'><?php echo _('Preferences'); ?></li>
        <li class='tabhandle' id='tab2'><?php echo _('Account'); ?></li>
        <li class='tabhandle' id='tab3'><?php echo _('Templates'); ?></li>
    </ul>
</menu>

<!-- *********************** -->
<div class='divhandle' id='tab1div'>

    <form action='app/ucp-exec.php' method='post'>
        <section class='box'>
            <h3><?php echo _('Language'); ?></h3>
            <hr>
            <p>
            <select id='lang' name="lang">
<?php
$lang_array = array('en_GB', 'ca_ES', 'de_DE', 'es_ES', 'fr_FR', 'it_IT', 'pt_BR', 'zh_CN');
foreach ($lang_array as $lang) {
    echo "<option ";
    if ($_SESSION['prefs']['lang'] === $lang) {
        echo ' selected ';
    }
    echo "value='" . $lang . "'>" . $lang . "</option>";
}
?>
            </select>
        </section>
        <section class='box'>

            <h3><?php echo _('DISPLAY'); ?></h3>
            <hr>
            <p>
            <input id='radio_view_default' type='radio' name='display' value='default' 
            <?php echo ($_SESSION['prefs']['display'] === 'default') ? "checked" : ""; ?>
             />
            <label for='radio_view_default'><?php echo _('Default'); ?></label>

            <input id='radio_view_compact' type='radio' name='display' value='compact' 
            <?php echo ($_SESSION['prefs']['display'] === 'compact') ? "checked" : ""; ?>
             />
            <label for='radio_view_compact'><?php echo _('Compact'); ?></label>
            </p>

            <p style='margin-top:20px;'>
            <label for='order'><?php echo _('Order by:'); ?></label>
            <select id='order' name="order">
                <option
                <?php
                if ($_SESSION['prefs']['order'] === 'date') {
                    echo ' selected ';
                }?>value="date"><?php echo _('Date'); ?></option>
                <option
                <?php
                if ($_SESSION['prefs']['order'] === 'id') {
                    echo ' selected ';
                }?>value="id">ID</option>
                <option
                <?php
                if ($_SESSION['prefs']['order'] === 'title') {
                    echo ' selected ';
                }?>value="title"><?php echo _('Title'); ?></option>
            </select>

            <?php echo _('with'); ?>
            <select name="sort">
                <option
                <?php
                if ($_SESSION['prefs']['sort'] === 'desc') {
                    echo ' selected ';
                }?>value="desc"><?php echo _('newer first'); ?></option>
                <option
                <?php
                if ($_SESSION['prefs']['sort'] === 'asc') {
                    echo ' selected ';
                }?>value="asc"><?php echo _('older first'); ?></option>
            </select>

            <p style='margin-top:20px;'>
            <label for='limit'><?php echo _('Items per page:'); ?></label>
            <input id='limit' type='text' size='2' maxlength='2' value='<?php echo $_SESSION['prefs']['limit']; ?>' name='limit'>
            </p>
        </section>

        <section class='box'>
            <h3><?php echo _('KEYBOARD SHORTCUTS'); ?></h3>
            <hr>
            <p>
                <table>
                <tr><th><?php echo _('Action'); ?></th>
                    <th><?php echo _('Shortcut'); ?></th></tr>

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

        <section class='box'>
            <h3><?php echo _('Miscellaneous'); ?></h3>
            <hr>
            <p>
            <input id='close_warning' type='checkbox' name='close_warning' <?php
            if (isset($_SESSION['prefs']['close_warning']) && $_SESSION['prefs']['close_warning'] === 1) {
                echo "checked='checked'  ";
            };?> />
            <label for='close_warning'><?php echo _('Display a warning before closing an edit window/tab ?'); ?></label>
            <br>
            <input id='chem_editor' type='checkbox' name='chem_editor' <?php
            if (isset($_SESSION['prefs']['chem_editor']) && $_SESSION['prefs']['chem_editor'] === 1) {
                echo "checked='checked'  ";
            };?> />
            <label for='chem_editor'><?php echo _('Display the molecule drawer in edit mode?'); ?></label>
            </p>
        </section>

        <div style='margin-top:30px;' class='center'>
        <button type="submit" name="Submit" class='button'><?php echo _('Save'); ?></button>
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
                <h4><?php echo _('Modify your personal informations'); ?></h4>
                <label class='block' for='currpass'><?php echo _('Enter your password to edit infos.'); ?></label>
                <input id='currpass' name="currpass" type="password" required />
            </div>
            <div class='col-md-6'>
                <h4><?php echo _('Modify your password'); ?></h4>
                <label class='block' for='newpass'><?php echo _('New password'); ?></label>
                <input name="newpass" type="password" />
                <label class='block' for='cnewpass'><?php echo _('Confirm new password'); ?></label>
                <input name="cnewpass" type="password" />
            </div>
        </div>

        <h4><?php echo _('Modify your identity'); ?></h4>

        <div class='row'>
            <div class='col-md-6'>
                <label class='block' for='firstname'><?php echo _('Firstname'); ?></label>
                <input name="firstname" value='<?php echo $user['firstname']; ?>' cols='20' rows='1' />
            </div>
        </div>

        <div class='row'>
            <div class='col-md-6'>
                <label class='block' for='lastname'><?php echo _('Lastname'); ?></label>
                <input name="lastname" value='<?php echo $user['lastname']; ?>' cols='20' rows='1' />
            </div>
            <div class='col-md-6'>
                <label class='block' for='email'><?php echo _('Email'); ?></label>
                <input name="email" type="email" value='<?php echo $user['email']; ?>' cols='20' rows='1' />
            </div>
        </div>

<br>
        <h4><?php echo _('Modify your contact information'); ?></h4>
        <div class='row'>
            <div class='col-md-6'>
                <label class='block' for='phone'><?php echo _('Phone'); ?> </label>
                <input name="phone" value='<?php echo $user['phone']; ?>' cols='20' rows='1' />
            </div>
            <div class='col-md-6'>
                <label class='block' for='cellphone'><?php echo _('Mobile'); ?></label>
                <input name="cellphone" value='<?php echo $user['cellphone']; ?>' cols='20' rows='1' />
            </div>
        </div>
        <div class='row'>
            <div class='col-md-6'>
                <label class='block' for='skype'><?php echo _('Skype'); ?></label>
                <input name="skype" value='<?php echo $user['skype']; ?>' cols='20' rows='1' />
            </div>
            <div class='col-md-6'>
                <label class='block' for='website'><?php echo _('Website'); ?></label>
                <input name="website" type="url" value='<?php echo $user['website']; ?>' cols='20' rows='1' />
            </div>
        </div>

    </div>
        <div class='submitButtonDiv'>
            <button type="submit" name="Submit" class='button'><?php echo _('Update profile'); ?></button>
        </div>
    </form>

</div>
<!-- *********************** -->
<div class='divhandle' id='tab3div'>

<?php // SQL TO GET TEMPLATES
$Templates = new Templates($_SESSION['team_id']);
$templatesArr = $Templates->readFromUserid($_SESSION['userid']);

echo "<h3>" . _('Experiments templates') . "</h3>";
echo "<div class='box'>";
echo "<ul class='nav nav-pills' role='tablist'>";
// tabs titles
echo "<li class='subtabhandle badge badgetab badgetabactive' id='subtab_1'>" . _('Create new') . "</li>";
foreach ($templatesArr as $template) {
    echo "<li class='sortable subtabhandle badge badgetab' id='subtab_" . $template['id'] . "'>" . stripslashes($template['name']) . "</li>";
}
echo "</ul>";
?>
    <!-- CREATE NEW TPL TAB -->
    <div class='subdivhandle' id='subtab_1div'>
    <p onClick="$('#import_tpl').toggle()"><img src='img/add.png' title='import template' alt='import' /><?php echo _('Import from file'); ?></p>
        <form action='app/ucp-exec.php' method='post'>
            <input type='hidden' name='new_tpl_form' />
            <input type='file' accept='.elabftw.tpl' id='import_tpl'>
            <input required type='text' name='new_tpl_name' id='new_tpl_name' pattern='.{3,}' placeholder='<?php echo _('Name of the template'); ?>' />
            <br>
            <textarea name='new_tpl_body' id='new_tpl_txt' style='height:500px;' class='mceditable' rows='50' cols='60'></textarea>
            <br>
            <div class='center'>
                <button type="submit" name="Submit" class='button'><?php echo _('Add template'); ?></button>
            </div>
        </form>
    </div>

    <?php
    // tabs content

    foreach ($templatesArr as $template) {
        echo "<div class='subdivhandle' id='subtab_" . $template['id'] . "div'>";
        echo "<img class='align_right' src='img/download.png' title='export template' alt='export' ";
        echo "onClick=\"exportTpl('" . $template['name'] . "', " . $template['id'] . ")\" />";
        echo "<img class='align_right' src='img/small-trash.png' title='delete' alt='delete' ";
        echo "onClick=\"deleteThis(" . $template['id'] . ",'tpl', 'ucp.php')\" />";
        echo "<form action='app/ucp-exec.php' method='post'>";
        echo "<input type='hidden' name='tpl_form' />";
        echo "<input type='hidden' name='tpl_id[]' value='" . $template['id'] . "' />";
        echo "<input name='tpl_name[]' value='" . stripslashes($template['name']) . "' /><br />";
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

<?php require_once('inc/footer.php'); ?>

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

            $.post("app/order.php", {
                'ordering_templates' : ordering
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
        content_css : "css/tinymce.css",
        plugins : "table textcolor searchreplace code fullscreen insertdatetime paste charmap save image link",
        toolbar1: "undo redo | bold italic underline | fontsizeselect | alignleft aligncenter alignright alignjustify | superscript subscript | bullist numlist outdent indent | forecolor backcolor | charmap | link",
        removed_menuitems : "newdocument",
        language : '<?php echo $_SESSION['prefs']['lang']; ?>'
    });
});
</script>
