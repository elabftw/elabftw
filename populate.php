<?php 
require_once('inc/common.php');
require_once('inc/head.php');
require_once('inc/menu.php');
?>
<h3>POPULATOR 3000</h3>
<div class='center'>
<p>Default = 200 experiments added</p>
<form name='loop' method='post' action='populate-exec.php'>
<input name='loop' placeholder='number of iterations'></input>
<input type="submit" id='submit' name="Submit" value="Populate !" onclick="this.form.submit(); this.disabled = 1;" />
</form>
</div>
<br />
<br />
<br />
<br />
<?php require_once('inc/footer.php');?>
