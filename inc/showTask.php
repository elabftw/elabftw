<?php

if (isset($_SESSION['prefs']['display'])) {
  $display = $_SESSION['prefs']['display'];
} else {
  $display = 'default';
}


// First use - Create mySQL-Table 'tasks' and 'tasks_status'
//  status: 0 -> pending, status 1 -> done
$sql = "CREATE TABLE IF NOT EXISTS tasks (id INT AUTO_INCREMENT PRIMARY KEY,
team         INT(10),
creator      INT(10),
assignedUser INT(10),
datetime     INT(10),
title        TEXT,
description  TEXT,
status       INT(10)
)";
try {
  $pdo->query($sql);
} catch(PDOException $ex) {
  echo "An Error occured!";
  echo $ex->getMessage();
}



if (isset($_GET['type']))
  $type = $_GET['type'];
else
  $type = "";




?>

<menu class='border'>
  <a href="create_item.php?type=task"><img src="img/add.png" class='bot5px' alt="" /> <?php echo _('Create task');?></a>

  <!-- 'FILTER _('Status')' dropdown menu -->
  <span class='align_right'>
    <select onchange='if (this.value) window.location.href=this.value'>
      <option value='tasks.php'>Own Tasks</option>
      <option value='tasks.php?type=team' <?php if($type === 'team') { echo ' selected'; } ?>>Tasks for Team</option>

    </select></span>
  </menu>

  <?php
  // Tasks here
if($type === 'team')
{
  $sql = "SELECT * FROM tasks WHERE team = :team ORDER BY status ASC, datetime DESC LIMIT 10";
  $req = $pdo->prepare($sql);
  $req->execute(array(
    'team' => $_SESSION['team_id']
  ));
} else {
  $sql = "SELECT * FROM tasks WHERE assignedUser = :assignedUser ORDER BY status ASC, datetime DESC LIMIT 500";
  $req = $pdo->prepare($sql);
  $req->execute(array(
    'assignedUser' => $_SESSION['userid']
  ));
}


  $count = $req->rowCount();
  if ($count == 0) {
    display_message('info', _('<strong>Welcome to eLabFTW.</strong> This is the place to create tasks for you or members of your team.'));
  } else {
    $results_arr = array();
    while ($final_query = $req->fetch()) {
      $results_arr[] = $final_query['id'];
    }
    // loop the results array and display results
    echo "<p>"._('Showing last 500 tasks:')."</p>";
    foreach ($results_arr as $result_id) {
      showTask($result_id, $display);
    }
  }
