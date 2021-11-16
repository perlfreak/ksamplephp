<?php
/**
 * ログイン処理を行う。
 * Perform login processing.
 * Perform password reminder processing.
 *
 * @license https://opensource.org/licenses/mit-license.html MIT License
 * @author
 */

// DB name
$dbname = 'ksamplephp';

session_start();
require_once './lib/utils.php';

/* セッション情報がある場合はマイページに遷移
   Transit to My Page if there is session information. */
if (!empty($_SESSION['user_id'])
    and $_SESSION['dbname'] == $dbname) {
  header("Location: mypage.php");
  exit();
}

/* ロケール別の表示文字列
   Get locale-specific display string. */
$msg_ary = get_msg("");
if (is_null($msg_ary)) {
  $msg_ary = get_msg('ja');
}

$errors = [];
$msg = "";
$session_lifetime = intval(ini_get('session.gc_maxlifetime'));
$login_failure_max_count = 5;

//$ary = filter_input( INPUT_POST, ‘array’, FILTER_DEFAULT, FILTER_REQUIRE_ARRAY );

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
      case "login":
        /*** ログイン処理
             Login processing ***/

        if (!empty(filter_input(INPUT_POST, 'user_id'))
            and !empty(filter_input(INPUT_POST, 'password'))) {
          // Check id, password
          $user_id = $mysqli->real_escape_string(filter_input(INPUT_POST, 'user_id'));
          $password = $mysqli->real_escape_string(filter_input(INPUT_POST, 'password'));
          $remid_pw_flg = 0;

          $stmt = $mysqli->prepare("
            SELECT user_id, password, locale, lastname, firstname, UNIX_TIMESTAMP(NOW()) - UNIX_TIMESTAMP(login_failure_date) AS last_login_failure_time, login_failure_count, remind_pw_flg
            FROM users
            WHERE user_id = ?
             AND del_flg != 1
          ");
          $stmt->bind_param('s', $user_id);
          $stmt->execute();
          $result = $stmt->get_result();

          $got_user_id = "";
          $got_password = "";
          $locale = "";
          $lastname = "";
          $firstname = "";
          $last_login_failure_time = "";
          $login_failure_count = 0;
          $remind_pw_flg = 0;
          while ($row = $result->fetch_array()) {
            $got_user_id = $row['user_id'];
            $got_password = $row['password'];
            $locale = $row['locale'];
            $lastname = $row['lastname'];
            $firstname = $row['firstname'];
            $last_login_failure_time = $row['last_login_failure_time'];
            $login_failure_count = intval($row['login_failure_count']);
            $remind_pw_flg = intval($row['remind_pw_flg']);
          }

          /* ログイン失敗回数がある場合
             When there are login failure times */
          if (!empty($login_failure_count)) {
            /* 最終ログイン失敗時がセッション有効時間以上前の場合失敗情報リセット
               When the last login failure fails before the session valid time or more Failure information reset. */
            if ($last_login_failure_time > $session_lifetime) {
              $stmt = $mysqli->prepare("
                UPDATE users
                SET login_failure_date = NULL, login_failure_count = 0
                WHERE user_id = ?
              ");
              $stmt->bind_param('s', $user_id);
              $stmt->execute();
            }
            /* 最終ログイン失敗時がセッション有効時間前より後で失敗回数が上限値の場合アカウントロック
               When the last login failure fails after the session valid time and the failure count is the upper limit Account Lock. */
            elseif ($login_failure_count == $login_failure_max_count) {
              $errors[] = $msg_ary['00030'];
              log_error($dbname . ' ' . $user_id . ' ' . filter_input(INPUT_SERVER, 'PHP_SELF') . ' ' . $msg_ary['00030']);
              $stmt->close();
              $mysqli->close();
            }
          }

          if (count($errors) == 0
              and (!$got_user_id
                   or !password_verify($password, $got_password))) {
            $errors[] = $msg_ary['00040'];
            log_error($dbname . ' ' . $user_id . ' ' . filter_input(INPUT_SERVER, 'PHP_SELF') . ' ' . $msg_ary['00040']);

            /* パスワードを間違えた場合
               Incorrect password */
            if ($got_user_id
                and !password_verify($password, $got_password)) {
              $login_failure_count++;
              $stmt = $mysqli->prepare("
                UPDATE users
                SET login_failure_date = now(), login_failure_count = ?
                WHERE user_id = ?
              ");
              $stmt->bind_param('is', $login_failure_count, $user_id);
              $stmt->execute();
            }

            $stmt->close();
            $mysqli->close();
          }

          if (count($errors) == 0) {
            /* 権限情報取得
               Acquire authority information. */
            $stmt = $mysqli->prepare("
              SELECT rp.permission_id
              FROM user_role ur, role_permission rp
              WHERE ur.role_id = rp.role_id
               AND ur.user_id = ?
            ");
            $stmt->bind_param('s', $user_id);
            $stmt->execute();
            $result = $stmt->get_result();

            $permissions = [];
            while ($row = $result->fetch_array(MYSQLI_NUM)) {
              foreach ($row as $r) {
                array_push($permissions, $r);
              }
            }

            $stmt->close();
            $mysqli->close();

            /* セッション情報をセットしてマイページに遷移
               Transit to my page by setting session information. */
            session_regenerate_id(true);
            $_SESSION['user_id'] = $user_id;
            $_SESSION['locale'] = $locale;
            $_SESSION['lastname'] = $lastname;
            $_SESSION['firstname'] = $firstname;
            $_SESSION['permissions'] = $permissions;
            $_SESSION['dbname'] = $dbname;
            log_info('Login');
            if ($remind_pw_flg) {
              header("Location: chg_pw.php");
            }
            else {
              header("Location: mypage.php");
            }
            exit();
          }
        }

        break;  // login

      case "passwd_reminder":
        /*** パスワードリマインダー処理
             Password reminder processing ***/

        if (!empty(filter_input(INPUT_POST, 'user_id'))
            and !empty(filter_input(INPUT_POST, 'email'))) {
          $user_id = $mysqli->real_escape_string(filter_input(INPUT_POST, 'user_id'));
          $email = $mysqli->real_escape_string(filter_input(INPUT_POST, 'email'));

          if (!check_format('email', $email)) {
            $errors[] = $msg_ary['00050'];
            log_error($dbname . ' ' . $user_id . ' ' . filter_input(INPUT_SERVER, 'PHP_SELF') . ' ' . $msg_ary['00050']);
            $mysqli->close();
          }

          if (count($errors) == 0) {
            $stmt = $mysqli->prepare("
              SELECT user_id, email, locale
              FROM users
              WHERE user_id = ?
               AND email = ?
               AND del_flg != 1
            ");
            $stmt->bind_param('ss', $user_id, $email);
            $stmt->execute();
            $result = $stmt->get_result();

            while ($row = $result->fetch_array()) {
              $got_user_id = $row['user_id'];
              $got_email =  $row['email'];
              $locale = $row['locale'];
            }

            if (!empty($got_user_id)) {
              /* 新パスワード発行
                 Issue a new password. */
              $pw_length = 10;
              $password = substr(base_convert(md5(uniqid()), 16, 36), 0, $pw_length);
              $enc_pw = password_hash($password, PASSWORD_DEFAULT);
              $stmt = $mysqli->prepare("
                UPDATE users
                SET password = ?, remind_pw_flg = 1
                WHERE user_id = ?
              ");
              $stmt->bind_param('ss', $enc_pw, $got_user_id);
              $stmt->execute();

              /* メール送信
                 Send mail. */
              mb_language("uni");
              mb_internal_encoding("UTF-8");

              $to      = $got_email;
              $subject = '[KSamplePHP] ' . $msg_ary['00060'];
              $message = $msg_ary['00070'] . "\n\n" . $password;
              $headers = 'From: system@mydomain' . "\n";

              mb_send_mail($to, $subject, $message, $headers);

              $msg = $msg_ary['00080'];
              log_info($dbname . ' ' . $got_user_id . ' ' . filter_input(INPUT_SERVER, 'PHP_SELF') . ' ' . $msg_ary['00080']);
            }
            else {
              $errors[] = $msg_ary['00090'];
              log_error($dbname . ' ' . $user_id . ' ' . filter_input(INPUT_SERVER, 'PHP_SELF') . ' ' . $msg_ary['00090']);
            }

            $stmt->close();
            $mysqli->close();
          }
        }

        break;  // passwd_reminder
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
<title><?= $msg_ary['00100'] ?></title>

<link rel="stylesheet" href="css/bootstrap.min.css">
</head>
<body >
<div class="container">
<div class="row d-flex justify-content-center">
<div class="col-6">

<div class="row">
<div class="col">
<h3><?= $msg_ary['00110'] ?></h3>
</div>
</div>

<?php
if (count($errors) > 0) {
  /* ------- <Error message> ------- */
?>
<div class="row">
<div class="col">
<p class="text-danger"><strong>
<?php
foreach($errors as $value){
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
<div class="row">
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
<?= $msg_ary['00120'] ?>
</div>
</div>
<br>
<?php
  /* ------- </Message before processing> ------- */
}
?>

<form id="logon-form" method="POST">

<div class="row">
<div class="col align-self-center">

<div class="row mb-2">
<div class="col-8">
<input type="text" id="user_id" name="user_id" placeholder="<?= $msg_ary['00130'] ?>" maxlength="128" class="form-control" required autofocus>
</div>
</div>

<div class="row mb-2">
<div class="col-8">
<input type="password" id="password" name="password" placeholder="<?= $msg_ary['00140'] ?>" maxlength="64" autocomplete="off" class="form-control" required>
</div>
</div>

<div class="row g-3">
<div class="col-auto">
<button type="reset" class="btn btn-light"><?= $msg_ary['00150'] ?></button>
<button type="submit" id="submit-btn" class="btn btn-primary"><?= $msg_ary['00160'] ?></button>
</div>
</div>

</div>
</div>

<input type="hidden" name="case" value="login">
<input type="hidden" name="token" value="<?= sha1(session_id()) ?>">
</form>
<br>

<div class="row">
<div class="col">
<button type="button" class="btn btn-link" data-bs-toggle="modal" data-bs-target="#passwd-reminder-modal">
<?= $msg_ary['00170'] ?>
</button>
</div>
</div>

<!-- Password reminder Modal -->
<div class="modal fade" id="passwd-reminder-modal" tabindex="-1" aria-labelledby="passwd-reminder-modal-label" aria-hidden="true">
<div class="modal-dialog modal-lg">
<div class="modal-content">
<form id="passwd-reminder-form" method="POST">

<div class="modal-header">
<h5 class="modal-title" id="passwd-reminder-modal-label"><?= $msg_ary['00180'] ?></h5>
<button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
</div>
<div class="modal-body">
<div class="row d-flex justify-content-center">
<div class="col-6">

<div class="row mb-2">
<div class="col">
<input type="text" id="remind_user_id" name="user_id" placeholder="<?= $msg_ary['00130'] ?>" maxlength="128" class="form-control" required autofocus>
</div>
</div>

<div class="row mb-2">
<div class="col">
<input type="email" id="remind_email" name="email" placeholder="<?= $msg_ary['00190'] ?>" maxlength="64" class="form-control" required>
</div>
</div>

<div class="row g-3">
<div class="col-auto">
<button type="reset" class="btn btn-light"><?= $msg_ary['00150'] ?></button>
<button type="submit" id="passwd-reminder-submit-btn" class="btn btn-primary"><?= $msg_ary['00200'] ?></button>
</div>
</div>

</div>
</div>
</div>

<input type="hidden" name="case" value="passwd_reminder">
<input type="hidden" name="token" value="<?= sha1(session_id()) ?>">
</form>
</div>
</div>
</div>
<!-- /Password reminder Modal -->

</div>
</div>
</div>

<!-- javascript -->
<script src="js/bootstrap.bundle.min.js"></script>
<script>
(function() {

"use strict";

const setCookie = function(cookieName, value) {
  const cookie = cookieName + "=" + value + ";";
  document.cookie = cookie;
};

const getCookie = function(cookieName){
  let l = cookieName.length + 1 ;
  const cookieAry = document.cookie.split(";");
  let str = "" ;
  for (let i = 0; i < cookieAry.length; i++) {
    if (cookieAry[i].substr(0, l) === cookieName + "=") {
      str = cookieAry[i].substr(l, cookieAry[i].length) ;
      break ;
    }
  }
  return str;
};

document.addEventListener('DOMContentLoaded', function() {
  document.getElementById('submit-btn').addEventListener('click', function() {
    setCookie('check_cookie', true);
    const val = getCookie('check_cookie');

    if (val) {
      //cookie valid
    }
    else {
      //cookie invalid
      alert('<?= $msg_ary['00210'] ?>');
      return false;
    }
  });

  document.getElementById('passwd-reminder-submit-btn').addEventListener('click', function() {
    if (!document.getElementById('remind_user_id').checkValidity()) {
      alert('<?= $msg_ary['00220'] ?>');
      return false;
    }
    if (!document.getElementById('remind_email').checkValidity()) {
      alert('<?= $msg_ary['00050'] ?>');
      return false;
    }
  });
});

}).call(this);
</script>

</body>
</html>
