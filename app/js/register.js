$(document).ready(function() {
    // PASSWORD COMPLEXITY
    $("#password").complexify({}, function (valid, complexity){
        if (complexity < 20) {
            $('#complexity').css({'background-color':'red'});
            $('#complexity').css({'color':'white'});
            $('#complexity').val($('#complexityDiv').data('weak'));
            $('#complexity').css({'border-color' : '#e3e3e3'});
            $('#complexity').css({'box-shadow': '0 0  yellow'});
            $('#complexity').css({'-moz-box-shadow': '0 0 yellow'});
        } else if (complexity < 30) {
            $('#complexity').css({'color':'#white'});
            $('#complexity').css({'background-color':'orange'});
            $('#complexity').val($('#complexityDiv').data('average'));
            $('#complexity').css({'box-shadow': '0 0  yellow'});
            $('#complexity').css({'border-color' : '#e3e3e3'});
            $('#complexity').css({'-moz-box-shadow': '0 0 yellow'});
        } else if (complexity < 50) {
            $('#complexity').css({'color':'white'});
            $('#complexity').val($('#complexityDiv').data('good'));
            $('#complexity').css({'background-color':'green'});
            $('#complexity').css({'box-shadow': '0 0  yellow'});
            $('#complexity').css({'-moz-box-shadow': '0 0 yellow'});
            $('#complexity').css({'border-color' : '#e3e3e3'});
        } else if (complexity < 99) {
            $('#complexity').css({'color':'black'});
            $('#complexity').val($('#complexityDiv').data('strong'));
            $('#complexity').css({'background-color':'#ffd700'});
            $('#complexity').css({'box-shadow': '0px 0px 15px 5px #ffd700'});
            $('#complexity').css({'border' : 'none'});
            $('#complexity').css({'-moz-box-shadow': '0px 0px 15px 5px #ffd700'});
        } else {
            $('#complexity').css({'color':'#797979'});
            $('#complexity').val($('#complexityDiv').data('wtf'));
            $('#complexity').css({'background-color':'#e3e3e3'});
            $('#complexity').css({'box-shadow': '0 0  yellow'});
            $('#complexity').css({'-moz-box-shadow': '0 0 yellow'});
            $('#complexity').css({'border-color' : '#e3e3e3'});
        }
    });
});
