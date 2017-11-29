$(document).ready(function() {
    // GENERATE API KEY
    $(document).on('click', '.generateApiKey', function() {
        $.post('app/controllers/UsersController.php', {
            generateApiKey: true
        }).done(function() {
            $("#api_div").load("profile.php #api_div");
        });
    });
});
