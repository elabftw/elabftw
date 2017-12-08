/**
 * login.js - for the login page
 *
 * @author Nicolas CARPi <nicolas.carpi@curie.fr>
 * @copyright 2012 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */
(function() {
    'use strict';

    $(document).ready(function(){
        $(".toggle_container").hide();
        $("a.trigger").click(function(){
            $('.toggle_container').slideToggle("slow");
        });
    });
}());
