<script src='js/tinymce/tinymce.min.js'></script>
<script type="text/javascript">
//global variables that can be used by ALL the function son this page.
var inputs;
var imgFalse = 'img/checkbox.png';
var imgTrue = 'img/done.png';

//replace the checkbox with an image and setup events to handle it
function replaceChecks() {
  //get all the input fields on the page
  inputs = document.getElementsByTagName('input');

  //cycle trough the input fields
  for(var i=0; i<inputs.length; i++) {

    //check if the input is a checkbox
    if(inputs[i].getAttribute('type') == 'checkbox') {

      //create a new image
      var img = document.createElement('img');

      //check if the checkbox is checked
      if(inputs[i].checked) {
        img.src = imgTrue;
      } else {
        img.src = imgFalse;
      }

      //set image ID and onclick action
      img.id = 'checkImage'+i;
      //set image
      img.onclick = new Function('checkClick('+i+')');

      //place image in front of the checkbox
      inputs[i].parentNode.insertBefore(img, inputs[i]);

      //hide the checkbox
      inputs[i].style.display='none';
    }
  }
}

//change the checkbox status and the replacement image
function checkClick(i) {
  if(inputs[i].checked) {
    inputs[i].checked = '';
    document.getElementById('checkImage'+i).src=imgFalse;
  } else {
    inputs[i].checked = 'checked';
    document.getElementById('checkImage'+i).src=imgTrue;
  }
}

</script>
<?php
// ID
if (isset($_GET['id']) && !empty($_GET['id']) && is_pos_int($_GET['id'])) {
  $id = $_GET['id'];
} else {
  display_message('error', _("The id parameter is not valid!"));
  require_once 'inc/footer.php';
  exit;
}
// GET CONTENT
$sql = "SELECT * FROM tasks WHERE id = :id";
$req = $pdo->prepare($sql);
$req->bindParam(':id', $id);
$req->execute();
$data = $req->fetch();

$sql = "SELECT * FROM users WHERE team = :team ORDER BY firstname DESC";
$req = $pdo->prepare($sql);
$req->bindParam(':team', $_SESSION['team_id']);
$req->execute();

$sql = "SELECT * FROM users WHERE userid = :userid";
$req_group = $pdo->prepare($sql);
$req_group->bindParam(':userid', $_SESSION['userid']);
$req_group->execute();
$usergroup = $req_group->fetch();

// BEGIN CONTENT
echo "<section class='box'";
if($data['status'] === '0')
{
  echo " style='border-left: 6px solid red'";
}
echo ">";
?>

  <form method="post" action="editTask-exec.php" enctype='multipart/form-data'>
  <!-- TRASH - Only for creator-->
  <?php



  if($data['creator'] === $_SESSION['userid'] || $usergroup['usergroup'] < '4')
  {
    echo "<p>";
  }
  if($data['status'] === '0')
  {
    echo "<span style='align:left'><input type='checkbox' name='done' id='done' title='done' value='done' /></span>";
  } else {
    echo "<span><input type='checkbox' name='done' id='done' title='done' value='done' checked /></span>";
  }
  echo "<span style='text-align:right'><img src='img/big-trash.png' align='right' title='delete' alt='delete' onClick=\"deleteThis('<?php echo $id;?>','task', 'tasks.php')\" /></span>";

  echo "</p>";
  ?>

  <!-- BEGIN FORM -->


    <input name='task_id' type='hidden' value='<?php echo $id;?>' />
    <img src='img/calendar.png' class='bot5px' title='date' alt='Date :' />
    <label for='datepicker'><?php echo _('Date');?></label>
    <!-- _('_('TODO list') list') if firefox has support for it: type = date -->
    <input name='date' id='datepicker' size='8' type='text' value='<?php echo $data['datetime'];?>' />
    <span class='align_right'><label for='assignTo'><?php // echo _('assign to');?></label>
      <select name='assignedUser' title='assign Task to'>
      <?php
      while ($team = $req->fetch())
      {

        if($usergroup['usergroup'] < $team['usergroup'] || $team['userid'] === $_SESSION['userid'])
        {
          echo "<option value='".$team['userid']."'";
          if($team['userid'] === $_SESSION['userid'])
          {
            echo " selected";
          }
          echo ">".$team['firstname']." ".$team['lastname']."</option>";
        }
      }
      ?>
      </select></span>
    <label class='block' for='title_txtarea'><?php echo _('Title');?></label>
    <input id='title_input' name='title' rows="1" value='<?php if (empty($_SESSION['errors'])) {
      echo stripslashes($data['title']);

    } else {
      echo stripslashes($_SESSION['new_title']);
    } ?>' required />
    <label for='body_area' class='block'><?php echo _('Description');?></label>
    <textarea id='body_area' class='mceditable' name='body' rows="15" cols="80">
      <?php echo stripslashes($data['description']);?>
    </textarea>
    <!-- _('Submit') BUTTON -->
    <div class='center' id='saveButton'>
      <button type="submit" name="Submit" class='button'><?php echo _('Save and go back');?></button>
    </div>
  </form>
  <!-- end edit items form -->
</section>


<script>
// JAVASCRIPT
// _('Tags') AUTOCOMPLETE LIST
$(function() {
  var availableTags = [
  <?php // get all user's tag for autocomplete
  $sql = "SELECT DISTINCT tag FROM items_tags ORDER BY id DESC LIMIT 500";
  $getalltags = $pdo->prepare($sql);
  $getalltags->execute();
  while ($tag = $getalltags->fetch()) {
    echo "'".$tag[0]."',";
  }?>
  ];
  $( "#addtaginput" ).autocomplete({
    source: availableTags
  });
});
// DELETE TAG
function delete_tag(tag_id,item_id){
  var you_sure = confirm('<?php echo _('Delete this?');?>');
  if (you_sure == true) {
    $.post('delete.php', {
      id:tag_id,
      item_id:item_id,
      type:'itemtag'
    })
    .success(function() {$("#tags_div").load("database.php?mode=edit&id="+item_id+" #tags_div");})
  }
  return false;
}
// ADD TAG
function addTagOnEnter(e){ // the argument here is the event (needed to detect which key is pressed)
  var keynum;
  if(e.which)
  { keynum = e.which;}
  if(keynum == 13){  // if the key that was pressed was Enter (ascii code 13)
    // get tag
    var tag = $('#addtaginput').val();
    // POST request
    $.post('add.php', {
      tag: tag,
      item_id: <?php echo $id; ?>,
      type: 'itemtag'
    })
    // reload the tags list
    .success(function() {$("#tags_div").load("database.php?mode=edit&id=<?php echo $id;?> #tags_div");
    // clear input field
    $("#addtaginput").val("");
    return false;
  })
} // end if key is enter
}


// READY ? GO !
$(document).ready(function() {
  // ADD TAG JS
  // listen keypress, add tag when it's enter
  $('#addtaginput').keypress(function (e) {
    addTagOnEnter(e);
  });
  // _('Edit')OR
  tinymce.init({
    mode : "specific_textareas",
    editor_selector : "mceditable",
    content_css : "css/tinymce.css",
    plugins : "table textcolor searchreplace code fullscreen insertdatetime paste charmap save image link",
    toolbar1: "undo redo | bold italic underline | fontsizeselect | alignleft aligncenter alignright alignjustify | superscript subscript | bullist numlist outdent indent | forecolor backcolor | charmap | image link | save",
    removed_menuitems : "newdocument",
    // save button :
    save_onsavecallback: function() {
      $.ajax({
        type: "POST",
        url: "quicksave.php",
        data: {
          id : <?php echo $id;?>,
          type : 'items',
          // we need this to get the updated content
          title : document.getElementById('title_txtarea').value,
          date : document.getElementById('datepicker').value,
          body : tinymce.activeEditor.getContent()
        }
      }).done(showSaved());
    },
    // keyboard shortcut to insert today's date at cursor in editor
    setup : function(editor) {
      editor.addShortcut("ctrl+shift+d", "add date at cursor", function() { addDateOnCursor(); });
    },
    language : '<?php echo $_SESSION['prefs']['lang'];?>'
  });
  // DATEPICKER
  $( "#datepicker" ).datepicker({dateFormat: 'yymmdd'});
  // STARS
  $('input.star').rating();
  $('#star1').click(function() {
    updateRating(1);
  });
  $('#star2').click(function() {
    updateRating(2);
  });
  $('#star3').click(function() {
    updateRating(3);
  });
  $('#star4').click(function() {
    updateRating(4);
  });
  $('#star5').click(function() {
    updateRating(5);
  });
  // SELECT ALL TXT WHEN FOCUS ON TITLE INPUT
  $("#title").focus(function(){
    $("#title").select();
  });
  // fix for the ' and "
  title = "<?php echo $data['title']; ?>".replace(/\&#39;/g, "'").replace(/\&#34;/g, "\"");
  document.title = title;

  // ask the user if he really wants to navigate out of the page
  <?php
  if (isset($_SESSION['prefs']['close_warning']) && $_SESSION['prefs']['close_warning'] === 1) {
    echo "
    window.onbeforeunload = function (e) {
      e = e || window.event;
      return '"._('Do you want to navigate away from this page? Unsaved changes will be lost!')."';
    };";
  }
  ?>
});
</script>
<script type="text/javascript">replaceChecks();</script>
