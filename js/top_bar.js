<script type="text/javascript">
function placeIt() {
    if (!document.all) {
        document.getElementById("top_bar").style.top = window.pageYOffset +"px"; // For Mozilla etc.
    } else {
        document.getElementById("top_bar").style.top = document.documentElement.scrollTop +"px"; // For the IE...
    }
    window.setTimeout("placeIt()", 100);
}
</script>
