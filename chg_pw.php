<?php
/**
 * パスワード変更処理を行う。
 * Perform password change processing.
 *
 * @license https://opensource.org/licenses/mit-license.html MIT License
 * @author 
 */

session_start();
require_once './lib/utils.php';
check_session();
log_info(filter_input(INPUT_SERVER, 'PHP_SELF'));

/* ロケール別の表示文字列
   Get locale-specific display string. */
$msg_ary = get_msg("");

$errors = [];
$msg = "";

if (!empty(filter_input(INPUT_POST, 'case'))) {
  // CSRF check
  if (filter_input(INPUT_POST, 'token') != sha1(session_id())) {
    $errors[] = $msg_ary['00010'];
    log_fatal(filter_input(INPUT_SERVER, 'PHP_SELF') . ' ' . $msg_ary['00010']);
  }

  // DB connect
  if (count($errors) == 0) {
    $mysqli = connect_db($dbname);
    if (!$mysqli) {
      $errors[] = $msg_ary['00020'];
      log_fatal($dbname . ' ' . filter_input(INPUT_SERVER, 'PHP_SELF') . ' ' . $msg_ary['00020']);
    }
  }

  if (count($errors) == 0) {
    $case = filter_input(INPUT_POST, 'case');

    switch ($case) {
      case "chg_pw":
        /*** パスワード変更処理
             Password change processing ***/

        if (!empty(filter_input(INPUT_POST, 'password'))
            and !empty(filter_input(INPUT_POST, 'new_password'))) {
          $password = $mysqli->real_escape_string(filter_input(INPUT_POST, 'password'));
        
          $sql = "
            SELECT password
            FROM users
            WHERE user_id = '" . $_SESSION['user_id'] . "'
             AND del_flg != 1
          ";
          if ($result = $mysqli->query($sql)) {
           while ($row = $result->fetch_array(MYSQLI_ASSOC)) {
             $got_password = $row['password'];
        	  }
            $result->close();
          }
        
          /* 現パスワード存在チェック
             Check current password existence. */
         if (!password_verify($password, $got_password)) {
            $errors[] = $msg_ary['00030'];
            log_error(filter_input(INPUT_SERVER, 'PHP_SELF') . ' ' . $msg_ary['00030']);
          	$mysqli->close();
        	}
        
          if (count($errors) == 0) {
            $new_password = $mysqli->real_escape_string(filter_input(INPUT_POST, 'new_password'));
        
            /* 新パスワード形式チェック
               Check new password format. */
            if (!check_format('password', $new_password)) {
              $errors[] = $msg_ary['00040'];
              log_error(filter_input(INPUT_SERVER, 'PHP_SELF') . ' ' . $msg_ary['00040']);
            	$mysqli->close();
            }
          }
        
          if (count($errors) == 0) {
            /* パスワード、更新ユーザ、更新日時更新
               Update password, update user, update date. */
            $stmt = $mysqli->prepare("
              UPDATE users
              SET password = ?, remind_pw_flg = 0, update_user = '" . $_SESSION['user_id'] . "'
              WHERE user_id = '" . $_SESSION['user_id'] . "'
               AND del_flg != 1
            ");
            $new_password = password_hash($new_password, PASSWORD_DEFAULT);
            $stmt->bind_param('s', $new_password);
            $stmt->execute();
          	$stmt->close();
          	$mysqli->close();
          	$msg = $msg_ary['00050'];
            log_info(filter_input(INPUT_SERVER, 'PHP_SELF') . ' ' . $msg_ary['00050']);
        	}
        }

        break;  // chg_pw
    }
  }
}
?>
<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8" />
<meta name="robots" content="noindex,nofollow" />
<meta http-equiv="Pragma" content="no-cache" />
<meta http-equiv="Cache-Control" content="no-cache" />
<meta http-equiv="Expires" content="0" />
<meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no" />
<title><?= $msg_ary['00060'] ?></title>

<link rel="stylesheet" href="//stackpath.bootstrapcdn.com/bootstrap/4.1.1/css/bootstrap.min.css" integrity="sha384-WskhaSGFgHYWDcbwN70/dfYBj47jz9qbsMId/iRN3ewGhXQFZCSftd1LZCfmhktB" crossorigin="anonymous" />
</head>
<body >
<div class="container">

<?php
include_once 'header.php';
?>

<div class="row">
<div class="col-sm-12">
<h3><?= $msg_ary['00070'] ?></h3>
</div>
</div>
<br />

<?php
if (count($errors) > 0) {
  /* ------- <Error message> ------- */
?>

<div class="row">
<div class="col-sm-12">
<div class="text-sm-center">
<p class="text-danger"><strong>
<?php
foreach ($errors as $value) {
	echo $value . '</br>';
}
?>
</strong></p>
</div>
</div>
</div>
<br />

<?php
  /* ------- </Error message> ------- */
}
elseif ($msg) {
  /* ------- <Message after normal processing> ------- */
?>

<div class="row">
<div class="col-sm-12">
<div class="text-sm-center">
<p class="text-success"><strong><?= $msg ?></strong></p>
</div>
</div>
</div>
<br />

<?php
  /* ------- </Message after normal processing> ------- */
}
else {
  /* ------- <Message before processing> ------- */
?>

<div class="row">
<div class="col-sm-12">
<div class="text-sm-center">
<?= $msg_ary['00080'] ?>
</div>
</div>
</div>
<br />

<?php
  /* ------- </Message before processing> ------- */
}
?>

<div class="row">
<div class="col-sm-12">
<form id="chg_pw-form" method="POST">
<div class="form-group row">
<label for="password" class="col-sm-3 ml-sm-auto col-form-label"><?= $msg_ary['00090'] ?></label>
<div class="col-sm-4 mr-sm-auto">
<input type="password" id="password" name="password" maxlength="64" autocomplete="off" class="form-control" required autofocus />
</div>
</div>
<div class="form-group row">
<label for="new_password" class="col-sm-3 ml-sm-auto col-form-label"><?= $msg_ary['00100'] ?></label>
<div class="col-sm-4 mr-sm-auto">
<input type="password" id="new_password" name="new_password" maxlength="64" autocomplete="off" class="form-control" required />
</div>
</div>
<div class="form-group row">
<label for="confirm_new_password" class="col-sm-3 ml-sm-auto col-form-label"><?= $msg_ary['00110'] ?></label>
<div class="col-sm-4 mr-sm-auto">
<input type="password" id="confirm_new_password" name="confirm_new_password" maxlength="64" autocomplete="off" class="form-control" required />
</div>
</div>
<div class="form-group row">
<div class="col-sm-7 ml-sm-auto">
<button type="reset" class="btn btn-outline-secondary"><?= $msg_ary['00120'] ?></button>
<button type="submit" id="submit-btn" class="btn btn-primary"><?= $msg_ary['00130'] ?></button>
</div>
</div>
<input type="hidden" name="case" value="chg_pw" />
<input type="hidden" name="token" value="<?= sha1(session_id()) ?>" />
</form>
</div>
</div>
<br />

<?php
include_once 'footer.php';
?>

</div>
</div>

</div>

<script src="//code.jquery.com/jquery-3.3.1.min.js" integrity="sha256-FgpCb/KJQlLNfOu91ta32o/NMZxltwRo8QtmkMRdAu8=" crossorigin="anonymous"></script>
<script src="//cdnjs.cloudflare.com/ajax/libs/popper.js/1.14.3/umd/popper.min.js" integrity="sha384-ZMP7rVo3mIykV+2+9J3UJ46jBk0WLaUAdn689aCwoqbBJiSnjAK/l8WvCWPIPm49" crossorigin="anonymous"></script>
<script src="//stackpath.bootstrapcdn.com/bootstrap/4.1.1/js/bootstrap.min.js" integrity="sha384-smHYKdLADwkXOn1EmN1qk/HfnUcbVRZyYmZ4qpPea6sjB/pTJ0euyQp0Mk8ck+5T" crossorigin="anonymous"></script>
<script>
(function() {

"use strict";

const root = this,
      $    = root.jQuery;

$(function() {
  $('#submit-btn').on('click', function() {
    if (!$('#password')[0].checkValidity()) {
      alert('<?= $msg_ary['00140'] ?>');
      return false;
    }
    else if (!$('#new_password')[0].checkValidity()) {
      alert('<?= $msg_ary['00150'] ?>');
      return false;
    }
    else if ($('#password').val() === $('#new_password').val()) {
      alert('<?= $msg_ary['00160'] ?>');
      return false;
    }
    else if ($('#new_password').val() !== $('#confirm_new_password').val()) {
      alert('<?= $msg_ary['00170'] ?>');
      return false;
    }
    else if (!$('#new_password').val().match(/^<?= get_regexp('password') ?>$/)) {
      alert('<?= $msg_ary['00040'] ?>');
      return false;
    }
    else {
      $('#chg_pw-form').submit();
    }
  });
});

}).call(this);
</script>

</body>
</html>
