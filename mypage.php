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
<meta charset="UTF-8" />
<meta name="robots" content="noindex,nofollow" />
<meta http-equiv="Pragma" content="no-cache" />
<meta http-equiv="Cache-Control" content="no-cache" />
<meta http-equiv="Expires" content="0" />
<meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no" />
<title><?= $msg_ary['00040'] ?></title>

<link rel="stylesheet" href="//stackpath.bootstrapcdn.com/bootstrap/4.3.1/css/bootstrap.min.css" integrity="sha384-ggOyR0iXCbMQv3Xipma34MD+dH/1fQ784/j6cY/iJTQUOhcWr7x9JvoRxT2MZw1T" crossorigin="anonymous" />
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
?>

<div class="row">
<div class="col-sm-12">

<?php
if (check_permission('manage_users')) {
?>
<a href="user.php" class="btn btn-secondary"><?= $msg_ary['00050'] ?></a><br />
<br />
<?php
}
?>

<?php
if (check_permission('change_locale')) {
?>
<form id="chg-locale-form" method="POST">
<select name="locale">
<option value=""></option>
<option value="ja">ja</option>
<option value="en">en</option>
</select>
<button type="button" id="chg-locale-submit-btn" class="btn btn-primary btn-sm"><?= $msg_ary['00060'] ?></button>
<input type="hidden" name="case" value="chg_locale" />
<input type="hidden" name="token" value="<?= sha1(session_id()) ?>" />
</form>
<br />
<?php
}
?>

</div>
</div>
<br />

<?php
include_once 'footer.php';
?>

</div>

<script src="//code.jquery.com/jquery-3.3.1.min.js" integrity="sha256-FgpCb/KJQlLNfOu91ta32o/NMZxltwRo8QtmkMRdAu8=" crossorigin="anonymous"></script>
<script src="//cdnjs.cloudflare.com/ajax/libs/popper.js/1.14.7/umd/popper.min.js" integrity="sha384-UO2eT0CpHqdSJQ6hJty5KVphtPhzWj9WO1clHTMGa3JDZwrnQq4sF86dIHNDz0W1" crossorigin="anonymous"></script>
<script src="//stackpath.bootstrapcdn.com/bootstrap/4.3.1/js/bootstrap.min.js" integrity="sha384-JjSmVgyd0p3pXB1rRibZUAYoIIy6OrQ6VrjIEaFf/nJGzIxFDsf4x0xIM+B07jRM" crossorigin="anonymous"></script>
<script>
(function() {

"use strict";

const root = this,
      $    = root.jQuery;

$(function() {
<?php
if (check_permission('manage_users')) {
?>
  $('#chg-locale-form [name=locale]').val('<?= $_SESSION['locale'] ?>');

  $('#chg-locale-submit-btn').on('click', function() {
    if (!$('#chg-locale-form [name=locale]').val()) {
      alert('<?= $msg_ary['00070'] ?>');
      return false;
    }
    $('#chg-locale-form').submit();
  });
<?php
}
?>
});

}).call(this);
</script>

</body>
</html>
