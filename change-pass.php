<?php
/**
 * change-pass.php
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
 * Form to reset the password
 *
 */
require_once 'inc/common.php';
$page_title = _('Reset password');
$selected_menu = null;
require_once 'inc/head.php';

$Auth = new Auth();

try {

    // get the unique key
    if (!isset($_GET['key']) ||
        !isset($_GET['userid']) ||
        Tools::checkId($_GET['userid']) === false) {

        throw new Exception('Bad parameters in url.');
    }
    ?>

    <section class='center'>
        <form method="post" class='loginform' action="app/reset.php">
            <p>
                <!-- output the key and userid as hidden fields -->
                <input type="hidden" name="key" value="<?= filter_var($_GET['key'], FILTER_SANITIZE_STRING) ?>" />
                <input type="hidden" name="userid" value="<?= $_GET['userid'] ?>" />

                <label class='block' for='passwordtxt'><?= _('New password') ?></label>
                <span class='smallgray'><?= $Auth::MIN_PASSWORD_LENGTH . " " . _('characters minimum') ?></span></label>
                <input id='password' type='password' pattern='.{0}|.{<?= $Auth::MIN_PASSWORD_LENGTH ?>,}' value='' name='password' required />

                <label class='block' for='cpasswordtxt'><?= _('Type it again') ?></label>
                <input id='cpassword' type='password' pattern='.{0}|.{<?= $Auth::MIN_PASSWORD_LENGTH ?>,}' value='' name='cpassword' required />
                <label class='block' for='complexity'><?= _('Complexity') ?></label>
                <input id='complexity' disabled />
                <div id="checkPasswordMatchDiv"><p><?= _('The passwords do not match!') ?></p></div>
            </p>
        </form>
    </section>

    <script>
    function checkPasswordMatch() {
        password = $("#password").val();
        confirmPassword = $("#cpassword").val();

        if (password != confirmPassword)
            $("#checkPasswordMatchDiv").html("<p><?= _('The passwords do not match!') ?></p>");
        else
            $("#checkPasswordMatchDiv").html("<button class='button' type='submit' name='Submit'><?= _('Save new password') ?></button>");
    }

    $(document).ready(function () {
        // check if passwords match
       $("#cpassword").keyup(checkPasswordMatch);
        // give focus to the first input field
        document.getElementById("password").focus();
        // password complexity
        $("#password").complexify({}, function (valid, complexity){
            if (complexity < 20) {
                $('#complexity').css({'background-color':'red'});
                $('#complexity').css({'color':'white'});
                $('#complexity').val('<?= _('Weak password') ?>');
                $('#complexity').css({'border-color' : '#e3e3e3'});
                $('#complexity').css({'box-shadow': '0 0  yellow'});
                $('#complexity').css({'-moz-box-shadow': '0 0 yellow'});
            } else if (complexity < 30) {
                $('#complexity').css({'color':'#white'});
                $('#complexity').css({'background-color':'orange'});
                $('#complexity').val('<?= _('Average password') ?>');
                $('#complexity').css({'box-shadow': '0 0  yellow'});
                $('#complexity').css({'border-color' : '#e3e3e3'});
                $('#complexity').css({'-moz-box-shadow': '0 0 yellow'});
            } else if (complexity < 50) {
                $('#complexity').css({'color':'white'});
                $('#complexity').val('<?= _('Good password') ?>');
                $('#complexity').css({'background-color':'green'});
                $('#complexity').css({'box-shadow': '0 0  yellow'});
                $('#complexity').css({'-moz-box-shadow': '0 0 yellow'});
                $('#complexity').css({'border-color' : '#e3e3e3'});
            } else if (complexity < 99) {
                $('#complexity').css({'color':'black'});
                $('#complexity').val('<?= _('Strong password') ?>');
                $('#complexity').css({'background-color':'#ffd700'});
                $('#complexity').css({'box-shadow': '0px 0px 15px 5px #ffd700'});
                $('#complexity').css({'border' : 'none'});
                $('#complexity').css({'-moz-box-shadow': '0px 0px 15px 5px #ffd700'});
            } else {
                $('#complexity').css({'color':'#797979'});
                $('#complexity').val('<?= _('No way that is your real password!') ?>');
                $('#complexity').css({'background-color':'#e3e3e3'});
                $('#complexity').css({'box-shadow': '0 0  yellow'});
                $('#complexity').css({'-moz-box-shadow': '0 0 yellow'});
                $('#complexity').css({'border-color' : '#e3e3e3'});
            }
        });
    });
    </script>
    <?php
} catch (Exception $e) {
    display_message('ko', $e->getMessage());
} finally {
    require_once 'inc/footer.php';
}
