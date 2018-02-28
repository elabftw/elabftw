$(document).ready(function() {
    // hide the install button
    $('#final_section').hide();

    // show last part if form is submitted directly
    $('#install-form').submit(function() {
        $('#final_section').show();
    });

    // sql test button
    $('#test_sql_button').click(function() {
        var mysql_host = $('#db_host').val();
        var mysql_name = $('#db_name').val();
        var mysql_user = $('#db_user').val();
        var mysql_password = $('#db_password').val();

        $.post('test.php', {
            mysql: 1,
            db_host: mysql_host,
            db_name: mysql_name,
            db_user: mysql_user,
            db_password: mysql_password
        }).done(function(test_result) {
            if (test_result == 1) {
                alert('MySQL connection was successful ! :)');
                $('#test_sql_button').hide();
                $('#final_section').show();
            } else {
                alert('The connection failed with this error : ' + test_result);
            }
        });
    });

    // reload page button
    $('.click2reload').click(function() {
        document.location.reload();
    });
});
