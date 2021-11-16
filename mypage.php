<?php
/**
 * My page
 *
 * @license https://opensource.org/licenses/mit-license.html MIT License
 * @author 
 */

session_start();
require_once './lib/utils.php';
check_session();
log_info(filter_input(INPUT_SERVER, 'PHP_SELF'));

/* ロケール別の表示文字列
   Get locale-specific display string */
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
      log_fatal(filter_input(INPUT_SERVER, 'PHP_SELF') . ' ' . $msg_ary['00020']);
    }
  }

  if (count($errors) == 0) {
    $case = filter_input(INPUT_POST, 'case');

    switch ($case) {
      case "chg_locale":
        /*** ロケール変更処理
             Locale change processing ***/

        if (!empty(filter_input(INPUT_POST, 'locale'))) {
          $locale = $mysqli->real_escape_string(filter_input(INPUT_POST, 'locale'));

          $stmt = $mysqli->prepare("
            UPDATE users
            SET locale = ?, update_user = '" . $_SESSION['user_id'] . "'
            WHERE user_id = '" . $_SESSION['user_id'] . "'
             AND del_flg != 1
          ");
          $stmt->bind_param('s', $locale);
          $stmt->execute();
          $stmt->close();
          $mysqli->close();
          
          $_SESSION['locale'] = $locale;

          $msg = $msg_ary['00030'];
          log_info(filter_input(INPUT_SERVER, 'PHP_SELF') . ' ' . $msg_ary['00030']);
        }

        break;  // chg_locale
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
<title><?= $msg_ary['00040'] ?></title>

<link rel="stylesheet" href="css/bootstrap.min.css">
</head>
<body >
<div class="container">

<?php
include_once 'header.php';
?>

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
?>

<?php
if (check_permission('manage_users')) {
?>
<div class="row">
<div class="col">
<a href="user.php" class="btn btn-outline-secondary"><?= $msg_ary['00050'] ?></a>
</div>
</div>
<br>
<?php
}
?>

<?php
if (check_permission('change_locale')) {
?>
<form id="chg-locale-form" name="chg-locale-form" method="POST">

<div class="row">
<div class="col">

<div class="row mb-2 g-3">
<div class="col-1">
<select name="locale" class="form-select form-select-sm">
<option value=""></option>
<option value="ja">ja</option>
<option value="en">en</option>
</select>
</div>
<div class="col-auto">
<button type="button" id="chg-locale-submit-btn" class="btn btn-primary btn-sm"><?= $msg_ary['00060'] ?></button>
</div>
</div>

</div>
</div>

<input type="hidden" name="case" value="chg_locale">
<input type="hidden" name="token" value="<?= sha1(session_id()) ?>">
</form>
<?php
}
?>
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
<?php
if (check_permission('manage_users')) {
?>
  const select_tag = document.querySelector('select[name="locale"]');
  for (let i = 0; i < select_tag.options.length; i++){
    if (select_tag.options[i].value === '<?= $_SESSION['locale'] ?>') {
      select_tag.selectedIndex = i;
    }
  }

  document.getElementById('chg-locale-submit-btn').addEventListener('click', function() {
    if (!document.querySelector('select[name="locale"]').value) {
      alert('<?= $msg_ary['00070'] ?>');
      return false;
    }
    document.getElementById('chg-locale-form').submit();
  });
<?php
}
?>
});

}).call(this);
</script>

</body>
</html>
