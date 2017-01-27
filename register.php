<?php
/**
 * register.php
 *
 * @author Nicolas CARPi <nicolas.carpi@curie.fr>
 * @copyright 2012 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */
namespace Elabftw\Elabftw;

/**
 * Create an account
 *
 */
require_once 'app/init.inc.php';
$pageTitle = _('Register');
$selectedMenu = null;
require_once 'app/head.inc.php';

// Check if we're logged in
if (isset($_SESSION['auth']) && $_SESSION['auth'] == 1) {
    echo Tools::displayMessage(sprintf(_('Please %slogout%s before you register another account.'), "<a style='alert-link' href='app/logout.php'>", "</a>"), 'ko');
    require_once 'app/footer.inc.php';
    exit;
}
?>

<menu class='border'><a href='login.php'><img src='app/img/arrow-left-blue.png' alt='' /> <?php echo _('go back to login page'); ?></a></menu>
<section class='center'>
    <h2><?php echo _('Create your account'); ?></h2><br><br>
    <!-- Register form -->
    <form id='regform' method="post" class='loginform' autocomplete="off" action="app/register-exec.php">

        <input style='display:none' type='text' name='bot' value=''>

        <div class='row'>
            <div class='col-md-4'>
                <label class='block' for="team"><?php echo _('Team'); ?></label>
                <select name='team' id='team' required>
                    <option value=''><?php echo _('------ Select a team ------'); ?></option>
                        <?php
                        $sql = "SELECT team_id, team_name FROM teams ORDER by team_name";
                        $req = $pdo->prepare($sql);
                        $req->execute();
                        while ($teams = $req->fetch()) {
                            echo "<option value = '" . $teams['team_id'] . "'>" . $teams['team_name'] . "</option>";
                        }
                    ?>
                </select>
            </div>
            <div class='col-md-4'>
                <label class='block' for="email"><?php echo _('Email'); ?></label>
                <input name="email" type="email" id="email" required />
            </div>
            <div class='col-md-4'>
                <label class='block' for="password"><?php echo _('Password'); ?></label>
                <input name="password" type="password" title='8 characters minimum' id="password" pattern=".{8,}" required />
            </div>
        </div>

        <div class='row'>
            <div class='col-md-4'>
                <label class='block' for="firstname"><?php echo _('Firstname'); ?></label>
                <input name="firstname" type="text" id="firstname" required />
            </div>

            <div class='col-md-4'>
                <label class='block' for="lastname"><?php echo _('Lastname'); ?></label>
                <input name="lastname" type="text" id="lastname" required />
            </div>

            <div class='col-md-4'>
                <label class='block' for='complexity'><?php echo _('Password complexity'); ?></label>
                <input id="complexity" disabled />
            </div>
        </div>

        <div class='submitButtonDiv'>
            <button type="submit" name="Submit" class='button'><?php echo _('create'); ?></button>
        </div>
    </form>
    <!-- end register form -->
</section>

<script>
$(document).ready(function() {
    // give focus to the first field on page load
    document.getElementById("team").focus();
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
<?php require_once 'app/footer.inc.php';
