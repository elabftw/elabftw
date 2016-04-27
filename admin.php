<?php
/**
 * admin.php
 *
 * @author Nicolas CARPi <nicolas.carpi@curie.fr>
 * @copyright 2012 Nicolas CARPi
 * @see http://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */
namespace Elabftw\Elabftw;

use Exception;

/**
 * Administration of a team
 *
 */
require_once 'inc/common.php';
$page_title = _('Admin panel');
$selected_menu = null;
require_once 'inc/head.php';

try {
    if (!$_SESSION['is_admin']) {
        throw new Exception(_('This section is out of your reach.'));
    }

    $formKey = new FormKey();
    $crypto = new CryptoWrapper();
    $status = new Status();
    $statusView = new StatusView();
    $itemsTypesView = new ItemsTypesView(new ItemsTypes($_SESSION['team_id']));
    $templates = new Templates($_SESSION['team_id']);
    $teamGroups = new TeamGroups();
    $teamGroupsView = new TeamGroupsView();
    $Auth = new Auth();
    $Users = new Users();

    // VALIDATE USERS BLOCK
    $unvalidatedUsersArr = $Users->readAll(0);

    // only show the frame if there is some users to validate and there is an email config
    if (count($unvalidatedUsersArr) != 0 && get_config('mail_from') != 'notconfigured@example.com') {
        $message = _('There are users waiting for validation of their account:');
        $message .= "<form method='post' action='app/controllers/UsersController.php'>";
        $message .= "<input type='hidden' name='usersValidate' value='true' />";
        $message .= $formKey->getFormkey();
        $message .= "<ul>";
        foreach($unvalidatedUsersArr as $user) {
            $message .= "<li><label>
                <input type='checkbox' name='usersValidateIdArr[]'
                value='".$user['userid'] . "'> " . $user['firstname'] . " " . $user['lastname'] . " (" . $user['email'] . ")
                </label></li>";
        }
        $message .= "</ul><div class='submitButtonDiv'>
        <button class='button' type='submit'>"._('Submit') . "</button></div>";
        display_message('ko', $message);
        echo "</form>";
    }
    // END VALIDATE USERS BLOCK
    ?>

    <menu>
        <ul>
        <li class='tabhandle' id='tab1'><?= _('Team') ?></li>
            <li class='tabhandle' id='tab2'><?= _('Users') ?></li>
            <li class='tabhandle' id='tab3'><?= ngettext('Status', 'Status', 2) ?></li>
            <li class='tabhandle' id='tab4'><?= _('Types of items') ?></li>
            <li class='tabhandle' id='tab5'><?= _('Experiments template') ?></li>
            <li class='tabhandle' id='tab6'><?= _('Import CSV') ?></li>
            <li class='tabhandle' id='tab7'><?= _('Import ZIP') ?></li>
            <li class='tabhandle' id='tab8'><?= _('Groups') ?></li>
        </ul>
    </menu>

    <!-- TAB 1 TEAM CONFIG -->
    <?php
    $team = get_team_config();

    $stamppass = '';
    if (!empty($team['stamppass'])) {
        try {
            $stamppass = $crypto->decrypt($team['stamppass']);
        } catch (Exception $e) {
            $stamppass = '';
        }
    }
    ?>

    <div class='divhandle' id='tab1div'>

    <h3><?= _('Configure your team') ?></h3>
    <div class='box'>
        <form method='post' action='app/controllers/ConfigController.php' autocomplete='off'>
            <input type='hidden' value='true' name='teamsUpdateFull' />
            <p>
            <label for='deletable_xp'><?= _('Users can delete experiments:') ?></label>
            <select name='deletable_xp' id='deletable_xp'>
                <option value='1'<?php
                    if ($team['deletable_xp']) { echo " selected='selected'"; } ?>
                        ><?= _('Yes') ?></option>
                <option value='0'<?php
                        if (!$team['deletable_xp']) { echo " selected='selected'"; } ?>
                            ><?= _('No') ?></option>
            </select>
            <span class='smallgray'><?= _('An admin account will always be able to delete experiments.') ?></span>
            </p>
            <p>
            <label for='link_name'><?= _('Name of the link in the top menu:') ?></label>
            <input type='text' value='<?= $team['link_name'] ?>' name='link_name' id='link_name' />
            </p>
            <p>
            <label for='link_href'><?= _('Address where this link should point:') ?></label>
            <input type='text' value='<?= $team['link_href'] ?>' name='link_href' id='link_href' />
            </p>
            <p>
            <label for='stampprovider'><?= _('URL for external timestamping service:') ?></label>
            <input type='url' value='<?= $team['stampprovider'] ?>' name='stampprovider' id='stampprovider' />
            <span class='smallgray'><?= _('This should be the URL used for <a href="https://tools.ietf.org/html/rfc3161">RFC 3161</a>-compliant timestamping requests.') ?></span>
            </p>
            <p>
            <label for='stampcert'><?= _('Chain of certificates of the external timestamping service:') ?></label>
            <input type='text' placeholder='vendor/pki.dfn.pem' value='<?= $team['stampcert'] ?>' name='stampcert' id='stampcert' />
            <span class='smallgray'><?= _('This should point to the chain of certificates used by your external timestamping provider to sign the timestamps.<br /> Local path relative to eLabFTW installation directory. The file needs to be in <a href="https://en.wikipedia.org/wiki/Privacy-enhanced_Electronic_Mail">PEM-encoded (ASCII)</a> format!') ?></span>
            </p>
            <label for='stamplogin'><?= _('Login for external timestamping service:') ?></label>
            <input type='text' value='<?= $team['stamplogin'] ?>' name='stamplogin' id='stamplogin' />
            <span class='smallgray'><?= _('This should be the login associated with your timestamping service provider') ?></span>
            </p>
            <p>
            <label for='stamppass'><?= _('Password for external timestamping service:') ?></label>
            <input type='password' value='<?= $stamppass ?>' name='stamppass' id='stamppass' />
            <span class='smallgray'><?= _('Your timestamping service provider password') ?></span>
            </p>
            <div class='submitButtonDiv'>
                <button type='submit' name='submit_config' class='button'>Save</button>
            </div>
        </form>

    </div>
    </div>

    <!-- TAB 2 USERS -->
    <div class='divhandle' id='tab2div'>

        <h3><?= _('Edit users') ?></h3>
        <ul class='list-group'>
        <?php
        // get all validated users
        $usersArr = $Users->readAll();
        foreach($usersArr as $user) {
            ?>
                <li class='list-group-item'>
                    <form method='post' action='app/controllers/UsersController.php'>
                        <input type='hidden' value='true' name='usersUpdate' />
                        <input type='hidden' value='<?= $user['userid'] ?>' name='userid' />
                        <ul class='list-inline'>
                        <li><label class='block' for='usersUpdateFirstname'><?= _('Firstname') ?></label>
                        <input  id='usersUpdateFirstname' type='text' value='<?= $user['firstname'] ?>' name='firstname' /></li>
                        <li><label class='block' for='usersUpdateLastname'><?= _('Lastname') ?></label>
                        <input  id='usersUpdateLastname' type='text' value='<?= $user['lastname'] ?>' name='lastname' /></li>
                        <li><label class='block' for='usersUpdateEmail'><?= _('Email') ?></label>
                        <input id='usersUpdateEmail' type='email' value='<?= $user['email'] ?>' name='email' /></li>
                        <li>
                        <label class='block' for='usersUpdateValidated'><?= _('Has an active account?') ?></label>
                        <select name='validated' id='usersUpdateValidated'>
                            <option value='1' selected='selected'><?= _('Yes') ?></option>
                            <option value='0'><?= _('No') ?></option>
                        </select>
                        </li>
                        <li><label class='block' for='usersUpdateUsergroup'><?= _('Group') ?></label>
                        <select name='usergroup' id='usersUpdateUsergroup'>
                <?php
                            if ($_SESSION['is_sysadmin']) {
                ?>
                                <option value='1'<?php
                                        if ($user['usergroup'] == 1) { echo " selected='selected'"; } ?>
                                >Sysadmins</option>
                <?php
                            }
                ?>
                            <option value='2'<?php
                                    if ($user['usergroup'] == 2) { echo " selected='selected'"; } ?>
                            >Admins</option>
                            <option value='3'<?php
                                    if ($user['usergroup'] == 3) { echo " selected='selected'"; } ?>
                            >Admin + Lock power</option>
                            <option value='4'<?php
                                    if ($user['usergroup'] == 4) { echo " selected='selected'"; } ?>
                            >Users</option>
                        </select></li>
                        <li><label class='block' for='usersUpdatePassword'><?= _('Reset user password') .
                            " <span class='smallgray'>" . $Auth::MIN_PASSWORD_LENGTH . " " . _('characters minimum') ?></span></label>
                        <input id='usersUpdatePassword' type='password' pattern='.{0}|.{<?= $Auth::MIN_PASSWORD_LENGTH ?>,}' value='' name='password' /></li>
                        <li><button type='submit' class='button'><?= _('Save') ?></button></li>
                    </ul>
                </form>
            </li>
            <?php
        }
        ?>

        <!-- DELETE USER -->
        <ul class='list-group'>
            <li class='list-group-item' style='border-color:red;background-color:#FFC1B7;'>
                <h3><?= _('DANGER ZONE') ?></h3>
                <h4><strong><?= _('Delete an account') ?></strong></h4>
                <form action='app/controllers/UsersController.php' method='post'>
                    <!-- form key -->
                    <?= $formKey->getFormkey() ?>
                    <input type='hidden' name='usersDestroy' value='true'/>
                    <label for='usersDestroyEmail'><?= _('Type EMAIL ADDRESS of a member to delete this user and all his experiments/files forever:') ?></label>
                    <input type='email' name='usersDestroyEmail' id='usersDestroyEmail' required />
                    <br>
                    <br>
                    <label for='usersDestroyPassword'><?= _('Type your password:') ?></label>
                    <input type='password' name='usersDestroyPassword' id='usersDestroyPassword' required />
                    <div class='center'>
                        <button type='submitButtonDiv' class='button'><?= _('Delete this user!') ?></button>
                    </div>
                </form>
            </li>
        </ul>

    </div>

    <!-- TAB 3 STATUS -->
    <div class='divhandle' id='tab3div'>
        <?php
        echo $statusView->showCreate();
        echo $statusView->show($status->read($_SESSION['team_id']), $_SESSION['team_id']);
        ?>
    </div>

    <!-- TAB 4 ITEMS TYPES-->
    <div class='divhandle' id='tab4div'>
        <?php
        echo $itemsTypesView->showCreate();
        echo $itemsTypesView->show();
        ?>
    </div>

    <!-- TAB 5 COMMON EXPERIMENT TEMPLATE -->
    <div class='divhandle' id='tab5div'>
        <h3><?= _('Common experiment template') ?></h3>
        <p><?= _('This is the default text when someone creates an experiment.') ?></p>
        <textarea style='height:400px' class='mceditable' id='commonTplTemplate' />
    <?php
        $templatesArr = $templates->readCommon();
        echo $templatesArr['body']
    ?>
        </textarea>
        <div class='submitButtonDiv'>
            <button type='submit' class='button' onClick='commonTplUpdate()'><?= _('Save') ?></button>
        </div>
    </div>

    <!-- TAB 6 IMPORT CSV -->
    <?php $itemsTypesArr = $itemsTypesView->itemsTypes->readAll() ?>
    <div class='divhandle' id='tab6div'>
        <h3><?= _('Import a CSV file') ?></h3>
        <p style='text-align:justify'><?= _("This page will allow you to import a .csv (Excel spreadsheet) file into the database.<br>First you need to open your .xls/.xlsx file in Excel or Libreoffice and save it as .csv.<br>In order to have a good import, the first row should be the column's field names. You can make a tiny import of 3 lines to see if everything works before you import a big file.") ?>
        <span class='strong'><?= _('You should make a backup of your database before importing thousands of items!') ?></span></p>

        <label for='item_selector'><?= _('1. Select a type of item to import to:') ?></label>
        <select id='item_selector' onchange='goNext(this.value)'><option value=''>--------</option>
        <?php
        foreach ($itemsTypesArr as $items_types) {
            echo "<option value='" . $items_types['id'] . "' name='type' ";
            echo ">" . $items_types['name'] . "</option>";
        }
        ?>
        </select>
        <div class='import_block'>
            <form enctype="multipart/form-data" action="app/import.php" method="POST">
            <label for='uploader'><?= _('2. Select a CSV file to import:') ?></label>
                <input id='uploader' name="file" type="file" accept='.csv' />
                <input name='type' type='hidden' value='csv' />
                <div class='submitButtonDiv'>
                    <button type="submit" class='button' value="Upload"><?= _('Import CSV') ?></button>
                </div>
            </form>
        </div>
    </div>

    <!-- TAB 7 IMPORT ZIP -->
    <div class='divhandle' id='tab7div'>

        <h3><?= _('Import a ZIP file') ?></h3>
        <p><?= _("This page will allow you to import a .elabftw.zip archive.") ?>
    <br><span class='strong'><?= _('You should make a backup of your database before importing thousands of items!') ?></span></p>

            <label for='item_selector'><?= _('1. Select where to import:') ?></label>
            <select id='item_selector' onchange='goNext(this.value)'>
                <option value='' selected>-------</option>
                <option value='' disabled>Import items</option>
            <?php
            foreach ($itemsTypesArr as $items_types) {
                echo "<option value='" . $items_types['id'] . "' name='type' ";
                echo ">" . $items_types['name'] . "</option>";
            }
            echo "<option value='' disabled>Import experiments</option>";

            foreach($usersArr as $user) {
                echo "<option value='" . $user['userid'] . "' name='type' ";
                echo ">" . $user['firstname'] . " " . $user['lastname'] . "</option>";
            }
            ?>
            </select><br>
            <div class='import_block'>
            <form enctype="multipart/form-data" action="app/import.php" method="POST">
            <label for='uploader'><?= _('2. Select a ZIP file to import:') ?></label>
                <input id='uploader' name="file" type="file" accept='.elabftw.zip' />
                <input name='type' type='hidden' value='zip' />
                <div class='submitButtonDiv'>
                    <button type="submit" class='button' value="Upload"><?= _('Import ZIP') ?></button>
                </div>
            </form>
        </div>
    </div>

    <!-- TAB 8 TEAM GROUPS -->
    <?php $teamGroupsArr = $teamGroups->read($_SESSION['team_id']); ?>

    <div class='divhandle' id='tab8div'>
        <h3><?= _('Manage groups of users') ?></h3>
    <!-- CREATE A GROUP -->
    <label for='teamGroupCreate'><?= _('Create a group') ?></label>
        <input id='teamGroupCreate' type="text" />
        <button type='submit' onclick='teamGroupCreate()' class='button'><?= _('Create') ?></button>
    <!-- END CREATE GROUP -->

    <div id='team_groups_div'>
        <div class='well'>
        <section>
        <!-- ADD USER TO GROUP -->
            <label for='teamGroupUserAdd'><?= _('Add this user') ?></label>
            <select id='teamGroupUserAdd'>
            <?php
            foreach ($usersArr as $users) {
                echo "<option value='" . $users['userid'] . "'>";
                echo $users['firstname'] . " " . $users['lastname'] . "</option>";
            }
            ?>
            </select>

            <label for='teamGroupGroupAdd'><?= _('to this group') ?></label>
            <select id='teamGroupGroupAdd'>
            <?php
            foreach ($teamGroupsArr as $team_groups) {
                echo "<option value='" . $team_groups['id'] . "'>";
                echo $team_groups['name'] . "</option>";
            }
            ?>
            </select>
            <button type="submit" onclick="teamGroupUpdate('add')" class='button'><?= _('Go') ?></button>

        </section>
        <section>
        <!-- RM USER FROM GROUP -->
            <label for='teamGroupUserRm'><?= _('Remove this user') ?></label>
            <select id='teamGroupUserRm'>
            <?php
            foreach ($usersArr as $users) {
                echo "<option value='" . $users['userid'] . "'>";
                echo $users['firstname'] . " " . $users['lastname'] . "</option>";
            }
            ?>
            </select>

            <label for='teamGroupGroupRm'><?= _('from this group') ?></label>
            <select id='teamGroupGroupRm'>
            <?php
            foreach ($teamGroupsArr as $team_groups) {
                echo "<option value='" . $team_groups['id'] . "'>";
                echo $team_groups['name'] . "</option>";
            }
            ?>
            </select>
            <button type="submit" onclick="teamGroupUpdate('rm')" class='button'><?= _('Go') ?></button>
        </section>
        </div>

        <!-- SHOW -->
        <h3><?= _('Existing groups') ?></h3>
        <?= $teamGroupsView->show($teamGroupsArr) ?>

        </div>
    </div>
    <!-- END TEAM GROUPS -->

    <script src="js/tinymce/tinymce.min.js"></script>
    <script>
    $(document).ready(function() {
        // validate on enter
        $('#create_teamgroup').keypress(function (e) {
            var keynum;
            if (e.which) {
                keynum = e.which;
            }
            if (keynum == 13) { // if the key that was pressed was Enter (ascii code 13)
                teamGroupCreate();
            }
        });
        // edit the team group name
        $('h3.teamgroup_name').editable('app/controllers/TeamGroupsController.php', {
         tooltip : 'Click to edit',
         indicator : 'Saving...',
         name : 'teamGroupUpdateName',
         submit : 'Save',
         cancel : 'Cancel',
         style : 'display:inline'

        });
        // SORTABLE for STATUS
        $('.sortable_status').sortable({
            // limit to horizontal dragging
            axis : 'y',
            helper : 'clone',
            // do ajax request to update db with new order
            update: function(event, ui) {
                // send the orders as an array
                var ordering = $(".sortable_status").sortable("toArray");
                console.log(ordering);

                $.post("app/order.php", {
                    'ordering_status' : ordering
                }).success(function(data) {
                    if (data == 1) {
                        notif("<?= _('Saved') ?>", "ok");
                    } else {
                        notif("<?= _('Something went wrong! :(') ?>", "ko");
                    }
                });
            }
        });

        $('.itemsTypesEditor').hide();

        // SORTABLE for ITEMS TYPES
        $('.sortable_itemstypes').sortable({
            // limit to horizontal dragging
            axis : 'y',
            helper : 'clone',
            // do ajax request to update db with new order
            update: function(event, ui) {
                // send the orders as an array
                var ordering = $(".sortable_itemstypes").sortable("toArray");

                $.post("app/order.php", {
                    'ordering_itemstypes' : ordering
                }).success(function(data) {
                    if (data == 1) {
                        notif("<?= _('Saved') ?>", "ok");
                    } else {
                        notif("<?= _('Something went wrong! :(') ?>", "ko");
                    }
                });
            }
        });
        // IMPORT
        $('.import_block').hide();

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
        // COLORPICKER
        $('.colorpicker').colorpicker({
            hsv: false,
            okOnEnter: true,
            rgb: false
        });
        // EDITOR
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
    <?php
} catch (Exception $e) {
    display_message('ko', $e->getMessage());
} finally {
    require_once 'inc/footer.php';
}
