/**
 * change-pass.js - for the change password page
 *
 * @author Nicolas CARPi <nicolas.carpi@curie.fr>
 * @copyright 2012 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */
(function() {
    'use strict';

    $(document).ready(function() {
        // check if passwords match
        $("#cpassword").keyup(function() {
            var password = $("#password").val();
            var confirmPassword = $("#cpassword").val();

            if (password !== confirmPassword) {
                $("#checkPasswordMatchDiv").html("<p>" + $('#passwordMatch').data('not') + "</p>");
            } else {
                $("#checkPasswordMatchDiv").html("<button class='button' type='submit' name='Submit'>" + $('#passwordMatch').data('save') + "</button>");
            }
        });
    });
}());
