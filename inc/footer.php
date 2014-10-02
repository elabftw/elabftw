<?php
/******************************************************************************
*   Copyright 2012 Nicolas CARPi
*   This file is part of eLabFTW. 
*
*    eLabFTW is free software: you can redistribute it and/or modify
*    it under the terms of the GNU General Public License as published by
*    the Free Software Foundation, either version 3 of the License, or
*    (at your option) any later version.
*
*    eLabFTW is distributed in the hope that it will be useful,
*    but WITHOUT ANY WARRANTY; without even the implied warranty of
*    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
*    GNU General Public License for more details.
*
*    You should have received a copy of the GNU General Public License
*    along with eLabFTW.  If not, see <http://www.gnu.org/licenses/>.
*
********************************************************************************/
?>
<footer>

    <p class='footer_left'>
    <a href='https://twitter.com/elabftw'>
    <img src='img/twitter.png' alt='twitter' title='Follow eLabFTW on Twitter !'>
    </a>
     <a href='https://github.com/NicolasCARPi/elabftw'>
    <img src='img/github.png' alt='github' title='eLabFTW on GitHub'>
    </a>
    <span>
    <?php
    if (isset($_SESSION['auth']) && $_SESSION['is_sysadmin'] === '1') {
        ?>
        <!-- SYSADMIN MENU -->
        <span class='strong'>
        <a id='check_for_updates' href='#'><?php echo CHECK_FOR_UPDATES;?></a><a href='sysconfig.php'><?php echo SYSADMIN_PANEL;?></a>
        <script>
        $('#check_for_updates').click(function() {
            var jqxhr = $.post('check_for_updates.php', function(answer) {
                alert(answer);
            });
        });
        </script>
    <?php
    }
    if (isset($_SESSION['auth']) && $_SESSION['is_admin'] === '1') {
        echo "<a href='admin.php'>".ADMIN_PANEL."</a>";
    }
    echo "</span></p><p>";
    echo POWERED_BY." <a href='http://www.elabftw.net'>eLabFTW</a>";
    ?>
    </p>
    <p><?php echo PAGE_GENERATED.' ';?><span class='strong'><?php echo round((microtime(true) - $start), 5);?> seconds</span></p>
</footer>

<script src="js/jquery-pageslide/jquery.pageslide.min.js"></script>
<!-- konami code and unicorns -->
<script src="js/cornify.min.js"></script>
<!-- advanced search div -->
<script>
    $('#adv_search').hide();
$('#big_search_input').click(function() {
    $('#adv_search').show();
});
</script>
<?php
if (isset($_SESSION['auth'])) {
    // show debug info only to admins
    if (isset($_SESSION['auth']) && get_config('debug') == 1 && $_SESSION['is_admin'] == 1) {
        echo "Session array : ";
        echo '<pre>'.var_dump($_SESSION).'</pre>';
        echo "<br>";
        echo "Cookie : ";
        echo '<pre>'.var_dump($_COOKIE).'</pre>';
        echo "<br>";
    }
    // show TODOlist
    echo "<script>
    key('".$_SESSION['prefs']['shortcuts']['todo']."', function(){
        $.pageslide({href:'inc/todolist.php'});
    });
    </script>";
}
?>

</body>
</html>
