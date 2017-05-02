// CHANGE PASS
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
