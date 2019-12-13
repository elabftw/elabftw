/**
 * @author Nicolas CARPi <nicolas.carpi@curie.fr>
 * @copyright 2012 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */
$(document).ready(function() {
  // check if passwords match
  $('#cpassword').keyup(function() {
    const password = $('#password').val();
    const confirmPassword = $('#cpassword').val();

    if (password !== confirmPassword) {
      $('#checkPasswordMatchDiv').html('<p>' + $('#passwordMatch').data('not') + '</p>');
    } else {
      $('#checkPasswordMatchDiv').html('<button class="button" type="submit" name="Submit">' + $('#passwordMatch').data('save') + '</button>');
    }
  });
});
