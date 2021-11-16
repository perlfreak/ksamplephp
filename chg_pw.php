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
    $mysqli = connect_db("");
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
<meta charset="UTF-8">
<meta name="robots" content="noindex,nofollow">
<meta http-equiv="Pragma" content="no-cache">
<meta http-equiv="Cache-Control" content="no-cache">
<meta http-equiv="Expires" content="0">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= $msg_ary['00060'] ?></title>

<link rel="stylesheet" href="css/bootstrap.min.css">
</head>
<body >
<div class="container">

<?php
include_once 'header.php';
?>

<div class="row">
<div class="col">
<h3><?= $msg_ary['00070'] ?></h3>
</div>
</div>
<br>

<?php
if (count($errors) > 0) {
  /* ------- <Error message> ------- */
?>
<div class="row text-center">
<div class="col">
<p class="text-danger"><strong>
<?php
foreach ($errors as $value) {
	echo $value . '<br>';
}
?>
</strong></p>
</div>
</div>
<br>
<?php
  /* ------- </Error message> ------- */
}
elseif ($msg) {
  /* ------- <Message after normal processing> ------- */
?>
<div class="row text-center">
<div class="col">
<p class="text-success"><strong><?= $msg ?></strong></p>
</div>
</div>
<br>
<?php
  /* ------- </Message after normal processing> ------- */
}
else {
  /* ------- <Message before processing> ------- */
?>
<div class="row">
<div class="col">
<?= $msg_ary['00080'] ?>
</div>
</div>
<br>
<?php
  /* ------- </Message before processing> ------- */
}
?>

<form id="chg_pw-form" method="POST">

<div class="row d-flex justify-content-center">
<div class="col-4">

<div class="row mb-2">
<div class="col">
<input type="password" id="password" name="password" placeholder="<?= $msg_ary['00090'] ?>" maxlength="64" autocomplete="off" class="form-control" required>
</div>
</div>

<div class="row mb-2">
<div class="col">
<input type="password" id="new_password" name="new_password" placeholder="<?= $msg_ary['00100'] ?>" maxlength="64" autocomplete="off" class="form-control" required>
</div>
</div>

<div class="row mb-2">
<div class="col">
<input type="password" id="confirm_new_password" name="confirm_new_password" placeholder="<?= $msg_ary['00110'] ?>" maxlength="64" autocomplete="off" class="form-control" required>
</div>
</div>

<div class="row g-3">
<div class="col-auto">
<button type="reset" class="btn btn-light"><?= $msg_ary['00120'] ?></button>
<button type="submit" id="submit-btn" class="btn btn-primary"><?= $msg_ary['00130'] ?></button>
</div>
</div>

</div>
</div>

<input type="hidden" name="case" value="chg_pw" />
<input type="hidden" name="token" value="<?= sha1(session_id()) ?>" />
</form>
<br>
<br>

<?php
include_once 'footer.php';
?>

</div>

<!-- javascript -->
<script src="js/bootstrap.bundle.min.js"></script>
<script>
(function() {

"use strict";

document.addEventListener('DOMContentLoaded', function() {
  document.getElementById('submit-btn').addEventListener('click', function() {
    if (!document.getElementById('password').checkValidity()) {
      alert('<?= $msg_ary['00140'] ?>');
      return false;
    }
    else if (!document.getElementById('new_password').checkValidity()) {
      alert('<?= $msg_ary['00150'] ?>');
      return false;
    }
    else if (document.getElementById('password').value === document.getElementById('new_password').value) {
      alert('<?= $msg_ary['00160'] ?>');
      return false;
    }
    else if (document.getElementById('new_password').value !== document.getElementById('confirm_new_password').value) {
      alert('<?= $msg_ary['00170'] ?>');
      return false;
    }
    else if (!document.getElementById('new_password').value.match(/^<?= get_regexp('password') ?>$/)) {
      alert('<?= $msg_ary['00040'] ?>');
      return false;
    }
    else {
      document.getElementById('chg_pw-form').submit();
    }
  });
});

}).call(this);
</script>

</body>
</html>
