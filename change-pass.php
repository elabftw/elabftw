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

/**
 * Form to reset the password.
 *
 */
require_once 'inc/common.php';
$page_title = _('Reset password');
$selected_menu = null;

// get the unique key
if (isset($_GET['key']) && isset($_GET['userid'])) {
    $key = filter_var($_GET['key'], FILTER_SANITIZE_STRING);
    $userid = filter_var($_GET['userid'], FILTER_VALIDATE_INT);
} else {
    header('Location:login.php');
    exit;
}
require_once 'inc/head.php';
?>

<section class='center'>
    <form method="post" class='loginform' action="app/reset.php">
        <p>
            <!-- output the key and userid as hidden fields -->
            <input type="hidden" name="key" value="<?php echo $key; ?>" />
            <input type="hidden" name="userid" value="<?php echo $userid; ?>" />

            <label class='block' for='passwordtxt'><?php echo _('New password'); ?></label>
            <input name="password" type="password" title='<?php echo _('8 characters minimum'); ?>' id="password" pattern=".{8,}" required />
            <label class='block' for='cpasswordtxt'><?php echo _('Type it again'); ?></label>
            <input name="cpassword" type="password" title='<?php echo _('8 characters minimum'); ?>' id="cpassword" pattern=".{8,}" required />
            <label class='block' for='complexity'><?php echo _('Complexity'); ?></label>
            <input id='complexity' disabled />
            <div id="checkPasswordMatchDiv"><p><?php echo _('The passwords do not match!'); ?></p></div>

        </p>
    </form>
</section>

<!-- Password complexity visualizer -->
<script src="js/jquery.complexify.js/jquery.complexify.js"></script>
<script src="js/jquery.complexify.js/jquery.complexify.banlist.js"></script>
<script>
// we check for password match here
function checkPasswordMatch() {
    var password = $("#password").val();
    var confirmPassword = $("#cpassword").val();

    if (password != confirmPassword)
        $("#checkPasswordMatchDiv").html("<p><?php echo _('The passwords do not match!'); ?></p>");
    else
        $("#checkPasswordMatchDiv").html("<button class='button' type='submit' name='Submit'><?php echo _('Save new password'); ?></button>");
}

$(document).ready(function () {
   $("#cpassword").keyup(checkPasswordMatch);
    // give focus to the first field on page load
    document.getElementById("password").focus();
    // password complexity
    $("#password").complexify({}, function (valid, complexity){
        if (complexity < 20) {
            $('#complexity').css({'background-color':'red'});
            $('#complexity').css({'color':'white'});
            $('#complexity').val('<?php echo _('Weak password'); ?>');
            $('#complexity').css({'border-color' : '#e3e3e3'});
            $('#complexity').css({'box-shadow': '0 0  yellow'});
            $('#complexity').css({'-moz-box-shadow': '0 0 yellow'});
        } else if (complexity < 30) {
            $('#complexity').css({'color':'#white'});
            $('#complexity').css({'background-color':'orange'});
            $('#complexity').val('<?php echo _('Average password'); ?>');
            $('#complexity').css({'box-shadow': '0 0  yellow'});
            $('#complexity').css({'border-color' : '#e3e3e3'});
            $('#complexity').css({'-moz-box-shadow': '0 0 yellow'});
        } else if (complexity < 50) {
            $('#complexity').css({'color':'white'});
            $('#complexity').val('<?php echo _('Good password'); ?>');
            $('#complexity').css({'background-color':'green'});
            $('#complexity').css({'box-shadow': '0 0  yellow'});
            $('#complexity').css({'-moz-box-shadow': '0 0 yellow'});
            $('#complexity').css({'border-color' : '#e3e3e3'});
        } else if (complexity < 99) {
            $('#complexity').css({'color':'black'});
            $('#complexity').val('<?php echo _('Strong password'); ?>');
            $('#complexity').css({'background-color':'#ffd700'});
            $('#complexity').css({'box-shadow': '0px 0px 15px 5px #ffd700'});
            $('#complexity').css({'border' : 'none'});
            $('#complexity').css({'-moz-box-shadow': '0px 0px 15px 5px #ffd700'});
        } else {
            $('#complexity').css({'color':'#797979'});
            $('#complexity').val('<?php echo _('No way that is your real password!'); ?>');
            $('#complexity').css({'background-color':'#e3e3e3'});
            $('#complexity').css({'box-shadow': '0 0  yellow'});
            $('#complexity').css({'-moz-box-shadow': '0 0 yellow'});
            $('#complexity').css({'border-color' : '#e3e3e3'});
        }
        //$("#complexity").html(Math.round(complexity) + '%');
    });
});
</script>
<?php require_once 'inc/footer.php';
