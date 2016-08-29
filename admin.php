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
require_once 'app/init.inc.php';
$page_title = _('Admin panel');
$selected_menu = null;
require_once 'app/head.inc.php';

try {
    if (!$_SESSION['is_admin']) {
        throw new Exception(Tools::error(true));
    }

    $formKey = new FormKey();

    $StatusView = new StatusView(new Status($_SESSION['team_id']));
    $ItemsTypesView = new ItemsTypesView(new ItemsTypes($_SESSION['team_id']));
    $TeamGroupsView = new TeamGroupsView(new TeamGroups($_SESSION['team_id']));

    $templates = new Templates($_SESSION['team_id']);
    $Auth = new Auth();
    $Users = new Users();
    $Config = new Config();
    $Teams = new Teams($_SESSION['team_id']);

    // VALIDATE USERS BLOCK
    $unvalidatedUsersArr = $Users->readAll(0);

    // only show the frame if there is some users to validate and there is an email config
    if (count($unvalidatedUsersArr) != 0 && $Config->read('mail_from') != 'notconfigured@example.com') {
        $message = _('There are users waiting for validation of their account:');
        $message .= "<form method='post' action='app/controllers/UsersController.php'>";
        $message .= "<input type='hidden' name='usersValidate' value='true' />";
        $message .= $formKey->getFormkey();
        $message .= "<ul>";
        foreach ($unvalidatedUsersArr as $user) {
            $message .= "<li><label>
                <input type='checkbox' name='usersValidateIdArr[]'
                value='".$user['userid'] . "'> " . $user['firstname'] . " " . $user['lastname'] . " (" . $user['email'] . ")
                </label></li>";
        }
        $message .= "</ul><div class='submitButtonDiv'>
        <button class='button' type='submit'>". _('Validate') . "</button></div>";
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
    $teamConfigArr = $Teams->read();
    ?>

    <div class='divhandle' id='tab1div'>
    <div class='box'>
    <h3><?= _('Configure your Team') ?></h3>
    <hr>
        <form method='post' action='app/controllers/AdminController.php' autocomplete='off'>
            <input type='hidden' value='true' name='teamsUpdateFull' />
            <p>
            <label for='deletable_xp'><?= _('Users can delete experiments:') ?></label>
            <select class="clean-form" name='deletable_xp' id='deletable_xp'>
                <option value='1'<?php
                if ($teamConfigArr['deletable_xp']) { echo " selected='selected'"; } ?>
                ><?= _('Yes') ?></option>
                <option value='0'<?php
                if (!$teamConfigArr['deletable_xp']) { echo " selected='selected'"; } ?>
                ><?= _('No') ?></option>
            </select>
            <span class='smallgray'><?= _('An admin account will always be able to delete experiments.') ?></span>
            </p>
            <p>
            <label class="block" for='link_name'><?= _('Name of the link in the top menu:') ?></label>
            <input class="clean-form col-3-form" type='text' value='<?= $teamConfigArr['link_name'] ?>' name='link_name' id='link_name' />
            </p>
            <p>
            <label class="block" for='link_href'><?= _('Address where this link should point:') ?></label>
            <input class="clean-form col-3-form" type='text' value='<?= $teamConfigArr['link_href'] ?>' name='link_href' id='link_href' />
            </p>
            <br>
            <span class='button button-neutral' onClick='toggleTimestampInputs()'><?= _('Override general timestamping config') ?></span>
            <br><br>
            <div class='timestampInputs'>
                <p>
                    <br>
                <label class="block" for='stampprovider'><?= _('URL for external timestamping service:') ?></label>
                <input class="clean-form col-3-form" type='url' placeholder='http://zeitstempel.dfn.de/' value='<?= $teamConfigArr['stampprovider'] ?>' name='stampprovider' id='stampprovider' />
                <span class='smallgray'><?= sprintf(_('This should be the URL used for %sRFC 3161%s-compliant timestamping requests.'), "<a href='https://tools.ietf.org/html/rfc3161'>", "</a>") ?></span>
                </p>
                <p>
                <label class="block" for='stampcert'><?= _('Chain of certificates of the external timestamping service:') ?></label>
                <input class="clean-form col-3-form" type='text' placeholder='vendor/pki.dfn.pem' value='<?= $teamConfigArr['stampcert'] ?>' name='stampcert' id='stampcert' />
                <span class='smallgray'><?= sprintf(_('This should point to the chain of certificates used by your external timestamping provider to sign the timestamps.%sLocal path relative to eLabFTW installation directory. The file needs to be in %sPEM-encoded (ASCII)%s format!'), "<br>", "<a href='https://en.wikipedia.org/wiki/Privacy-enhanced_Electronic_Mail'>", "</a>") ?></span>
                </p>
                <label class="block" for='stamplogin'><?= _('Login for external timestamping service:') ?></label>
                <input class="clean-form col-3-form" type='text' value='<?= $teamConfigArr['stamplogin'] ?>' name='stamplogin' id='stamplogin' />
                <span class='smallgray'><?= _('This should be the login associated with your timestamping service provider') ?></span>
                </p>
                <p>
                <label class="block" for='stamppass'><?= _('Password for external timestamping service:') ?></label>
                <input class="clean-form col-3-form" type='password' name='stamppass' id='stamppass' />
                <span class='smallgray'><?= _('Your timestamping service provider password') ?></span>
                </p>
            </div>

            <div class='submitButtonDiv'>
                <button type='submit' class='button'>Save</button>
            </div>
        </form>

    </div>
    </div>

    <!-- TAB 2 USERS -->
    <div class='divhandle' id='tab2div'>
        <div class="box">
        <h3><?= _('Edit Users') ?></h3><hr>
        <ul class='list-group'>
        <?php
        // get all validated users
        $usersArr = $Users->readAll();
        foreach ($usersArr as $user) {
            ?>
                <li>
                    <form method='post' action='app/controllers/UsersController.php'>
                        <input type='hidden' value='true' name='usersUpdate' />
                        <input type='hidden' value='<?= $user['userid'] ?>' name='userid' />
                        <ul class='list-inline'>
                        <li><label class='block' for='usersUpdateFirstname'><?= _('Firstname') ?></label>
                        <input class="clean-form" id='usersUpdateFirstname' type='text' value='<?= $user['firstname'] ?>' name='firstname' /></li>
                        <li><label class='block' for='usersUpdateLastname'><?= _('Lastname') ?></label>
                        <input class="clean-form" id='usersUpdateLastname' type='text' value='<?= $user['lastname'] ?>' name='lastname' /></li>
                        <li><label class='block' for='usersUpdateEmail'><?= _('Email') ?></label>
                        <input class="clean-form" id='usersUpdateEmail' type='email' value='<?= $user['email'] ?>' name='email' /></li>
                        <li>
                        <label class='block' for='usersUpdateValidated'><?= _('Has an active account?') ?></label>
                        <select class="clean-form" name='validated' id='usersUpdateValidated'>
                            <option value='1' selected='selected'><?= _('Yes') ?></option>
                            <option value='0'><?= _('No') ?></option>
                        </select>
                        </li>
                        <li><label class='block' for='usersUpdateUsergroup'><?= _('Group') ?></label>
                        <select class="clean-form" name='usergroup' id='usersUpdateUsergroup'>
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
                        <li><label class='block' for='usersUpdatePassword'><?= _('Reset user password') ?></label>
                        <input class="clean-form" id='usersUpdatePassword' type='password' pattern='.{0}|.{<?= $Auth::MIN_PASSWORD_LENGTH ?>,}' value='' name='password' />
                         <span class='smallgray'><?= $Auth::MIN_PASSWORD_LENGTH . " " . _('characters minimum') ?></span></li>
                    </ul>
                        <button type='submit' class='button'><?= _('Save') ?></button>
                </form>
            </li>
            <hr>
            <?php
        }
        ?>
         </div>

        <!-- DELETE USER -->
        <ul class='list-group'>
            <li class='list-group-item danger-zone-area'>
                <p><?= _('DANGER ZONE') ?></p><hr>
                <p><strong><?= _('Delete an account') ?></strong></p>
                <form action='app/controllers/UsersController.php' method='post'>
                    <!-- form key -->
                    <?= $formKey->getFormkey() ?>
                    <input type='hidden' name='usersDestroy' value='true'/>
                    <label class="block" for='usersDestroyEmail'><?= _('Type EMAIL ADDRESS of a member to delete this user and all his experiments/files forever:') ?></label>
                    <input class="clean-form col-3-form" type='email' placeholder='Email Address' name='usersDestroyEmail' id='usersDestroyEmail' required />
                    <label class="block" for='usersDestroyPassword'><?= _('Type your password:') ?></label>
                    <input class="clean-form col-3-form" type='password' placeholder='Your Password' name='usersDestroyPassword' id='usersDestroyPassword' required />
                    <div class='center'>
                        <button type='submitButtonDiv' class='button button-delete'><?= _('Delete this user!') ?></button>
                    </div>
                </form>
            </li>
        </ul>
    </div>

    <!-- TAB 3 STATUS -->
    <div class='divhandle' id='tab3div'>
        <?php
        echo $StatusView->showCreate();
        echo $StatusView->show();
        ?>
    </div>

    <!-- TAB 4 ITEMS TYPES-->
    <div class='divhandle' id='tab4div'>
        <?php
        echo $ItemsTypesView->showCreate();
        echo $ItemsTypesView->show();
        ?>
    </div>

    <!-- TAB 5 COMMON EXPERIMENT TEMPLATE -->
    <div class='divhandle' id='tab5div'>
        <div class='box'>
            <h3><?= _('Common Experiment Template') ?></h3><hr>
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
    </div>

    <!-- TAB 6 IMPORT CSV -->
    <?php $itemsTypesArr = $ItemsTypesView->itemsTypes->readAll() ?>
    <div class='divhandle' id='tab6div'>
        <div class='box'>
            <h3><?= _('Import a CSV File') ?></h3>
            <hr>
            <p style='text-align:justify'><?= _("This page will allow you to import a .csv (Excel spreadsheet) file into the database.<br>First you need to open your .xls/.xlsx file in Excel or Libreoffice and save it as .csv.<br>In order to have a good import, the first row should be the column's field names. You can make a tiny import of 3 lines to see if everything works before you import a big file.") ?>
            <span class='strong'><?= _('You should make a backup of your database before importing thousands of items!') ?></span></p>

            <label class="block" for='item_selector'><?= _('1. Select a type of item to import to:') ?></label>
            <select class="clean-form col-3-form" id='item_selector' onchange='goNext(this.value)'><option value=''>--------</option>
            <?php
            foreach ($itemsTypesArr as $items_types) {
                echo "<option value='" . $items_types['id'] . "' name='type' ";
                echo ">" . $items_types['name'] . "</option>";
            }
            ?>
            </select>
            <div class='import_block'>
                <form enctype="multipart/form-data" action="app/controllers/ImportController.php" method="POST">
                <label class="block" for='uploader'><?= _('2. Select a CSV file to import:') ?></label>
                    <input id='uploader' name="file" type="file" accept='.csv' />
                    <input name='type' type='hidden' value='csv' />
                    <div class='submitButtonDiv'>
                        <button type="submit" class='button' value="Upload"><?= _('Import CSV') ?></button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- TAB 7 IMPORT ZIP -->
    <div class='divhandle' id='tab7div'>
        <div class='box'>
            <h3><?= _('Import a ZIP File') ?></h3><hr>
            <p><?= _("This page will allow you to import a .elabftw.zip archive.") ?>
        <br><span class='strong'><?= _('You should make a backup of your database before importing thousands of items!') ?></span></p>

                <label class="block" for='item_selector'><?= _('1. Select where to import:') ?></label>
                <select class="clean-form col-3-form" id='item_selector' onchange='goNext(this.value)'>
                    <option value='' selected>-------</option>
                    <option class='disabled-input' value='' disabled>Import items</option>
                <?php
                foreach ($itemsTypesArr as $items_types) {
                    echo "<option value='" . $items_types['id'] . "' name='type' ";
                    echo ">" . $items_types['name'] . "</option>";
                }
                echo "<option class='disabled-input' value='' disabled>Import experiments</option>";

                foreach ($usersArr as $user) {
                    echo "<option value='" . $user['userid'] . "' name='type' ";
                    echo ">" . $user['firstname'] . " " . $user['lastname'] . "</option>";
                }
                ?>
                </select><br>
                <div class='import_block'>
                <form enctype="multipart/form-data" action="app/controllers/ImportController.php" method="POST">
                <label class="block" for='uploader'><?= _('2. Select a ZIP file to import:') ?></label>
                    <input id='uploader' name="file" type="file" accept='.elabftw.zip' />
                    <input name='type' type='hidden' value='zip' />
                    <div class='submitButtonDiv'>
                        <button type="submit" class='button' value="Upload"><?= _('Import ZIP') ?></button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- TAB 8 TEAM GROUPS -->
    <?php $teamGroupsArr = $TeamGroupsView->TeamGroups->readAll(); ?>

    <div class='divhandle' id='tab8div'>
        <div class='box tooltip-box'>
            <h3><?= _('Manage Groups of Users') ?></h3><hr>
        <!-- CREATE A GROUP -->
        <label class="block" for='teamGroupCreate'><?= _('Create a group') ?></label>
            <input class="clean-form col-3-form" id='teamGroupCreate' type="text" />
            <button type='submit' onclick='teamGroupCreate()' class='button'><?= _('Create') ?></button>
        <!-- END CREATE GROUP -->

        <div id='team_groups_div'>
            <div>
                <hr>
            <section>
            <!-- ADD USER TO GROUP -->
                <label class="block" for='teamGroupUserAdd'><?= _('Add this user') ?></label>
                <select class="clean-form col-3-form" id='teamGroupUserAdd'>
                <?php
                foreach ($usersArr as $users) {
                    echo "<option value='" . $users['userid'] . "'>";
                    echo $users['firstname'] . " " . $users['lastname'] . "</option>";
                }
                ?>
                </select>

                <label class="block" for='teamGroupGroupAdd'><?= _('to this group') ?></label>
                <select class="clean-form col-3-form" id='teamGroupGroupAdd'>
                <?php
                foreach ($teamGroupsArr as $team_groups) {
                    echo "<option value='" . $team_groups['id'] . "'>";
                    echo $team_groups['name'] . "</option>";
                }
                ?>
                </select>
                <button type="submit" onclick="teamGroupUpdate('add')" class='button'><?= _('Add') ?></button>

            </section>
            <section>
                <hr>
            <!-- RM USER FROM GROUP -->
                <label class="block" for='teamGroupUserRm'><?= _('Remove this user') ?></label>
                <select class="clean-form col-3-form" id='teamGroupUserRm'>
                <?php
                foreach ($usersArr as $users) {
                    echo "<option value='" . $users['userid'] . "'>";
                    echo $users['firstname'] . " " . $users['lastname'] . "</option>";
                }
                ?>
                </select>

                <label class="block" for='teamGroupGroupRm'><?= _('from this group') ?></label>
                <select class="clean-form col-3-form" id='teamGroupGroupRm'>
                <?php
                foreach ($teamGroupsArr as $team_groups) {
                    echo "<option value='" . $team_groups['id'] . "'>";
                    echo $team_groups['name'] . "</option>";
                }
                ?>
                </select>
                <button type="submit" onclick="teamGroupUpdate('rm')" class='button button-delete'><?= _('Remove') ?></button>
            </section>
            </div>

            <!-- SHOW -->
            <hr>
            <h3><?= _('Existing groups') ?></h3>
            <?= $TeamGroupsView->show($teamGroupsArr) ?>

            </div>
        </div>
    </div>
    <!-- END TEAM GROUPS -->

    <script src="js/tinymce/tinymce.min.js"></script>
    <script>
    function toggleTimestampInputs() {
        $('.timestampInputs').toggle();
    }
    $(document).ready(function() {
        $('.timestampInputs').hide();
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

                $.post("app/controllers/AdminController.php", {
                    'updateOrdering': true,
                    'table': 'status',
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

                $.post("app/controllers/AdminController.php", {
                    'updateOrdering': true,
                    'table': 'items_types',
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
            content_css : "app/css/tinymce.css",
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
    require_once 'app/footer.inc.php';
}
