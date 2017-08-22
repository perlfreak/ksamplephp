<?php
/**
 * ユーザ情報管理（検索参照、登録、変更、削除）を行う。
 * User information management (search reference, registration, change, deletion)
 *
 * パーミッションID: manage_users が必要です。
 * Permission ID: manage_users is required.
 *
 * @license https://opensource.org/licenses/mit-license.html MIT License
 * @author Masayoshi Kojima <masayoshi.kojima@gmail.com>
 */

session_start();
require_once './lib/utils.php';
check_session();
log_info(filter_input(INPUT_SERVER, 'PHP_SELF'));

/* ロケール別の表示文字列
   Get locale-specific display string */
$msg_ary = get_msg("");

/* 権限がない場合
   When there is no authority */
if (!check_permission('manage_users')) {
  log_error(filter_input(INPUT_SERVER, 'PHP_SELF') . ' ' . $msg_ary['00030']);
  header('Content-Type: text/html; charset=UTF-8');
  echo get_one_msg_html($msg_ary['00010'], $msg_ary['00020'], $msg_ary['00030']);
  exit();
}

$errors = [];
$roles = [];
$users = [];
$msg = "";
$total = 0;

$search_key_user_id = "";
$search_key_firstname = "";
$search_key_lastname = "";
$search_key_email = "";
$search_key_locale = "";

/** @var string[] ロケール
 *       string[] locale
 *
 * ロケールを追加する場合、この配列に要素を追加します。
 * To add a locale, add an element to this array.
 * ./msg 以下のJSONファイルにもロケールに対応したメッセージが必要です。
 * A message corresponding to the locale is also required for JSON files under ./msg.
 */
$locales = [
  'ja',
  'en'
];

/* ロールID取得
   Acquire role ID */
$mysqli = connect_db("");
if (!$mysqli) {
  $errors[] = $msg_ary['00040'];
  log_fatal(filter_input(INPUT_SERVER, 'PHP_SELF') . ' ' . $msg_ary['00040']);
}

if (count($errors) == 0) {
  $sql = "SELECT role_id FROM roles";
  if ($result = $mysqli->query($sql)) {
    while ($row = $result->fetch_array(MYSQLI_NUM)) {
      foreach ($row as $r) {
        array_push($roles, $r);
      }
    }
    $result->close();
  }
  else {
    $errors[] = $msg_ary['00050'];
    log_fatal(filter_input(INPUT_SERVER, 'PHP_SELF') . ' ' . $msg_ary['00050']);
  }

  $mysqli->close();
}

if (!empty(filter_input(INPUT_POST, 'case'))) {
  // CSRF check
  if ($_POST['token'] != sha1(session_id())) {
    $errors[] = $msg_ary['00060'];
    log_fatal(filter_input(INPUT_SERVER, 'PHP_SELF') . ' ' . $msg_ary['00060']);
  }

  // DB connect
  if (count($errors) == 0) {
    $mysqli = connect_db("");
    if (!$mysqli) {
      $errors[] = $msg_ary['00040'];
      log_fatal(filter_input(INPUT_SERVER, 'PHP_SELF') . ' ' . $msg_ary['00040']);
    }
  }

  if (count($errors) == 0) {
    $case = filter_input(INPUT_POST, 'case');

    switch ($case) {
      case "register_user":
        /*** ユーザ登録処理
             User registration processing ***/

        if (!empty(filter_input(INPUT_POST, 'user_id'))
            and !empty(filter_input(INPUT_POST, 'password'))
            and !empty(filter_input(INPUT_POST, 'firstname'))
            and !empty(filter_input(INPUT_POST, 'lastname'))
            and !empty(filter_input(INPUT_POST, 'email'))
            and !empty(filter_input(INPUT_POST, 'locale'))
            and !empty(filter_input(INPUT_POST, 'role_id'))) {
          log_info(filter_input(INPUT_SERVER, 'PHP_SELF') . ' register_user user_id=' . filter_input(INPUT_POST, 'user_id'));
          $user_id = $mysqli->real_escape_string(filter_input(INPUT_POST, 'user_id'));
          $password = $mysqli->real_escape_string(filter_input(INPUT_POST, 'password'));
          $firstname = $mysqli->real_escape_string(filter_input(INPUT_POST, 'firstname'));
          $lastname = $mysqli->real_escape_string(filter_input(INPUT_POST, 'lastname'));
          $email = $mysqli->real_escape_string(filter_input(INPUT_POST, 'email'));
          $locale = $mysqli->real_escape_string(filter_input(INPUT_POST, 'locale'));
          $role_id = $mysqli->real_escape_string(filter_input(INPUT_POST, 'role_id'));
        
          /* ユーザID形式チェック
             Check user_id format. */
          if (!check_format('user_id', $user_id)) {
            $errors[] = $msg_ary['00070'];
            log_error(filter_input(INPUT_SERVER, 'PHP_SELF') . ' ' . $msg_ary['00070']);
          }
        
          /* パスワード形式チェック
             Check password format. */
          if (!check_format('password', $password)) {
            $errors[] = $msg_ary['00080'];
            log_error(filter_input(INPUT_SERVER, 'PHP_SELF') . ' ' . $msg_ary['00080']);
          }
        
          /* メールアドレス形式チェック
             Check email format. */
          if (!check_format('email', $email)) {
            $errors[] = $msg_ary['00090'];
            log_error(filter_input(INPUT_SERVER, 'PHP_SELF') . ' ' . $msg_ary['00090']);
          }
        
          if (count($errors) > 0) {
           	$mysqli->close();
          }
        
          if (count($errors) == 0) {
            $password = password_hash($password, PASSWORD_DEFAULT);
        
            $stmt = $mysqli->prepare("
              INSERT INTO users
              (user_id, password, firstname, lastname, email, locale, create_user, update_user)
              VALUES (?, ?, ?, ?, ?, ?, '" . $_SESSION['user_id'] . "', '" . $_SESSION['user_id'] . "')
            ");
            $stmt->bind_param('ssssss', $user_id, $password, $firstname, $lastname, $email, $locale);
            $stmt2 = $mysqli->prepare("
              INSERT INTO user_role
              (user_id, role_id, create_user, update_user)
              VALUES (?, ?, '" . $_SESSION['user_id'] . "', '" . $_SESSION['user_id'] . "')
            ");
            $stmt2->bind_param('ss', $user_id, $role_id);
        
            $mysqli->autocommit(FALSE);
            $stmt->execute();
            $stmt2->execute();
        
            if ($mysqli->commit()) {
          	  $msg = $msg_ary['00100'];
              log_info(filter_input(INPUT_SERVER, 'PHP_SELF') . ' ' . $msg_ary['00100']);
            }
            else {
          	  $msg = $msg_ary['00110'];
              log_info(filter_input(INPUT_SERVER, 'PHP_SELF') . ' ' . $msg_ary['00110']);
            }
        
          	$mysqli->close();
          }
        }

        break;  // register_user

      case "search_user":
        /*** ユーザ検索処理
             User search process ***/

        log_info(filter_input(INPUT_SERVER, 'PHP_SELF') . ' search_user');

        $user_id = $mysqli->real_escape_string(filter_input(INPUT_POST, 'user_id'));
        $firstname = $mysqli->real_escape_string(filter_input(INPUT_POST, 'firstname'));
        $lastname = $mysqli->real_escape_string(filter_input(INPUT_POST, 'lastname'));
        $email = $mysqli->real_escape_string(filter_input(INPUT_POST, 'email'));
        $locale = $mysqli->real_escape_string(filter_input(INPUT_POST, 'locale'));
    
        // where
        $where = "
          WHERE del_flg != 1
        ";
        if ($user_id) {
          $where = $where . " AND user_id LIKE '%" . $user_id . "%'";
          $search_key_user_id = filter_input(INPUT_POST, 'user_id', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
        }
        if ($firstname) {
          $where = $where . " AND firstname LIKE '%" . $firstname . "%'";
          $search_key_firstname = filter_input(INPUT_POST, 'firstname', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
        }
        if ($lastname) {
          $where = $where . " AND lastname LIKE '%" . $lastname . "%'";
          $search_key_lastname = filter_input(INPUT_POST, 'lastname', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
        }
        if ($email) {
          $where = $where . " AND email LIKE '%" . $email . "%'";
          $search_key_email = filter_input(INPUT_POST, 'email', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
        }
        if ($locale) {
          $where = $where . " AND locale = '" . $locale . "'";
          $search_key_locale = filter_input(INPUT_POST, 'locale', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
        }
    
        $page_max_rows = 25;  // 最大表示件数 Maximum display number
        $page = $mysqli->real_escape_string(filter_input(INPUT_POST, 'page'));
        if ($page == "" ) {
          $page = 1;
        }
        $page = max($page, 1);
    
        /* 件数取得
           Number of cases */
        $sql = "
          SELECT COUNT(user_id) AS total
          FROM users
        " . $where;
        if ($result = $mysqli->query($sql)) {
          while ($row = $result->fetch_array(MYSQLI_ASSOC)) {
            $total = $row["total"];
          }
          $result->close();
        }
    
        $max_page = ceil($total / $page_max_rows);
        $page = min($page, $max_page);
        $start = ($page - 1) * $page_max_rows;
    
        $sql = "
          SELECT user_id, firstname, lastname, email, locale
          FROM users
        " . $where . "
          LIMIT " . $start . ", " . $page_max_rows;
    
        if ($result = $mysqli->query($sql)) {
          while ($row = $result->fetch_array(MYSQLI_ASSOC)) {
            $result_ary = [
              "user_id" => $row["user_id"],
              "firstname" => $row["firstname"],
              "lastname" => $row["lastname"],
              "email" => $row["email"],
              "locale" => $row["locale"]
            ];
            array_push($users, $result_ary);
          }
          $result->close();
        }
      	$mysqli->close();

        break;  // search_user

      case "search_user_solo":
        /*** ユーザ検索処理（Ajax用、user_id指定）
             User search process (ajax, with user_id) ***/

        if (!empty(filter_input(INPUT_POST, 'user_id'))) {
          log_info(filter_input(INPUT_SERVER, 'PHP_SELF') . ' search_user_solo user_id=' . filter_input(INPUT_POST, 'user_id'));
      
          $user_id = $mysqli->real_escape_string(filter_input(INPUT_POST, 'user_id'));
      
          $stmt = $mysqli->prepare("
            SELECT u.user_id, u.firstname, u.lastname, u.email, u.locale, ur.role_id
            FROM users u, user_role ur
            WHERE u.user_id = ?
             AND u.user_id = ur.user_id
             AND u.del_flg != 1
          ");
          $stmt->bind_param('s', $user_id);
      	  $stmt->execute();
          if ($result = $stmt->get_result()) {
            while ($row = $result->fetch_array(MYSQLI_ASSOC)) {
              $result_ary = [
                "user_id" => htmlspecialchars($row["user_id"]),
                "firstname" => htmlspecialchars($row["firstname"]),
                "lastname" => htmlspecialchars($row["lastname"]),
                "email" => htmlspecialchars($row["email"]),
                "locale" => htmlspecialchars($row["locale"]),
                "role_id" => htmlspecialchars($row["role_id"])
              ];
              array_push($users, $result_ary);
            }
            $result->close();
      
            header('Content-Type: application/json');
            echo json_encode($users, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT);
          }
          else {
            log_error(filter_input(INPUT_SERVER, 'PHP_SELF') . ' ' . $msg_ary['00120']);
          }
          $stmt->close();
          $mysqli->close();
      
          exit();
        }

        break;  // search_user_solo

      case "update_user":
        /*** ユーザ更新処理（Ajax用）
             User update processing (ajax) ***/

        if (!empty(filter_input(INPUT_POST, 'user_id'))
            and !empty(filter_input(INPUT_POST, 'firstname'))
            and !empty(filter_input(INPUT_POST, 'lastname'))
            and !empty(filter_input(INPUT_POST, 'email'))
            and !empty(filter_input(INPUT_POST, 'locale'))
            and !empty(filter_input(INPUT_POST, 'role_id'))) {
          log_info(filter_input(INPUT_SERVER, 'PHP_SELF') . ' update_user user_id=' . filter_input(INPUT_POST, 'user_id'));
          $user_id = $mysqli->real_escape_string(htmlspecialchars_decode(filter_input(INPUT_POST, 'user_id')));
          $firstname = $mysqli->real_escape_string(htmlspecialchars_decode(filter_input(INPUT_POST, 'firstname')));
          $lastname = $mysqli->real_escape_string(htmlspecialchars_decode(filter_input(INPUT_POST, 'lastname')));
          $email = $mysqli->real_escape_string(htmlspecialchars_decode(filter_input(INPUT_POST, 'email')));
          $locale = $mysqli->real_escape_string(htmlspecialchars_decode(filter_input(INPUT_POST, 'locale')));
          $role_id = $mysqli->real_escape_string(htmlspecialchars_decode(filter_input(INPUT_POST, 'role_id')));
        
          /* メールアドレス形式チェック
             Check email format. */
          if (!check_format('email', $email)) {
            $errors[] = $msg_ary['00090'];
            log_error(filter_input(INPUT_SERVER, 'PHP_SELF') . ' ' . $msg_ary['00090']);
          	$mysqli->close();
          }
        
          if (count($errors) == 0) {
            $stmt = $mysqli->prepare("
              UPDATE users
              SET firstname = ?, lastname = ?, email = ?, locale = ?, update_user = '" . $_SESSION['user_id'] . "'
              WHERE user_id = ?
               AND del_flg != 1
            ");
            $stmt->bind_param('sssss', $firstname, $lastname, $email, $locale, $user_id);
            $stmt2 = $mysqli->prepare("
              UPDATE user_role
              SET role_id = ?, update_user = '" . $_SESSION['user_id'] . "'
              WHERE user_id = ?
            ");
            $stmt2->bind_param('ss', $role_id, $user_id);
        
            $mysqli->autocommit(FALSE);
            $stmt->execute();
            $stmt2->execute();
        
            header("Content-type: text/plain; charset=UTF-8");
            if ($mysqli->commit()) {
              log_info(filter_input(INPUT_SERVER, 'PHP_SELF') . ' ' . $msg_ary['00130']);
              echo 'OK';
            }
            else {
              log_error(filter_input(INPUT_SERVER, 'PHP_SELF') . ' ' . $msg_ary['00140']);
              echo 'NG';
            }
        
          	$mysqli->close();
          }
          exit();
        }

        break;  // update_user

      case "del_user":
        /*** ユーザ削除処理
             User deletion processing ***/

        if (!empty(filter_input(INPUT_POST, 'user_id'))) {
          log_info(filter_input(INPUT_SERVER, 'PHP_SELF') . ' del_user user_id=' . filter_input(INPUT_POST, 'user_id'));
          $user_id = $mysqli->real_escape_string(filter_input(INPUT_POST, 'user_id'));
      
          $stmt = $mysqli->prepare("
            UPDATE users
            SET del_flg = 1, update_user = '" . $_SESSION['user_id'] . "'
            WHERE user_id = ?
             AND del_flg != 1
          ");
          $stmt->bind_param('s', $user_id);
      	  $stmt->execute();
        	$stmt->close();
        	$mysqli->close();
        	$msg = $msg_ary['00150'];
          log_info(filter_input(INPUT_SERVER, 'PHP_SELF') . ' ' . $msg_ary['00150']);
        }

        break;  // del_user
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
<title><?= $msg_ary['00010'] ?></title>

<link rel="stylesheet" href="//maxcdn.bootstrapcdn.com/bootstrap/4.0.0-beta/css/bootstrap.min.css" integrity="sha384-/Y6pD6FV/Vv2HJnA6t+vslU6fwYXjCFtcEpHbNJ0lyAFsXTsjBbfaDjzALeQsN6M" crossorigin="anonymous" />
</head>
<body >
<div class="container">

<?php
include_once 'header.php';
?>

<div class="row">
<div class="col-sm-12">
<h3><?= $msg_ary['00160'] ?></h3>
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
<?= $msg_ary['00170'] ?>
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

<!-- Button trigger modal -->
<button type="button" class="btn btn-primary" data-toggle="modal" data-target="#register-user-modal"><?= $msg_ary['00180'] ?></button>

<!-- New user registration Modal -->
<div class="modal fade" id="register-user-modal" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true">
<div class="modal-dialog modal-lg" role="document">
<div class="modal-content">
<div class="modal-header">
<button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
<h4 class="modal-title" id="myModalLabel"><?= $msg_ary['00190'] ?></h4>
</div>
<div class="modal-body">

<form id="register-user-form" method="POST">

<div class="form-group row">
<label for="user_id" class="col-sm-3 ml-sm-auto col-form-label"><?= $msg_ary['00200'] ?></label>
<div class="col-sm-4 mr-sm-auto">
<input type="text" name="user_id" maxlength="128" class="form-control" required />
</div>
</div>

<div class="form-group row">
<label for="password" class="col-sm-3 ml-sm-auto col-form-label"><?= $msg_ary['00210'] ?></label>
<div class="col-sm-4 mr-sm-auto">
<input type="password" name="password" maxlength="64" autocomplete="off" class="form-control" required />
</div>
</div>

<div class="form-group row">
<label for="lastname" class="col-sm-3 ml-sm-auto col-form-label"><?= $msg_ary['00220'] ?></label>
<div class="col-sm-4 mr-sm-auto">
<input type="text" name="lastname" maxlength="64" class="form-control" required />
</div>
</div>

<div class="form-group row">
<label for="firstname" class="col-sm-3 ml-sm-auto col-form-label"><?= $msg_ary['00230'] ?></label>
<div class="col-sm-4 mr-sm-auto">
<input type="text" name="firstname" maxlength="64" class="form-control" required />
</div>
</div>

<div class="form-group row">
<label for="email" class="col-sm-3 ml-sm-auto col-form-label"><?= $msg_ary['00240'] ?></label>
<div class="col-sm-4 mr-sm-auto">
<input type="email" name="email" maxlength="256" class="form-control" required />
</div>
</div>

<div class="form-group row">
<label for="locale" class="col-sm-3 ml-sm-auto col-form-label"><?= $msg_ary['00250'] ?></label>
<div class="col-sm-4 mr-sm-auto">
<select name="locale" class="form-control">

<?php
foreach ($locales as $value) {
  echo '<option value="' . $value . '">' . $value . '</option>';
}
?>

</select>
</div>
</div>

<div class="form-group row">
<label for="role_id" class="col-sm-3 ml-sm-auto col-form-label"><?= $msg_ary['00260'] ?></label>
<div class="col-sm-4 mr-sm-auto">
<select name="role_id" class="form-control" required>

<?php
foreach ($roles as $value) {
  echo '<option value="' . $value . '">' . $value . '</option>';
}
?>

</select>
</div>
</div>

<div class="form-group row">
<div class="col-sm-7 ml-sm-auto">
<button type="reset" class="btn btn-secondary"><?= $msg_ary['00270'] ?></button>
<button type="submit" id="register-user-submit-btn" class="btn btn-primary"><?= $msg_ary['00280'] ?></button>
</div>
</div>

<input type="hidden" name="case" value="register_user" />
<input type="hidden" name="token" value="<?= sha1(session_id()) ?>" />
</form>

</div>
</div>
</div>
</div>
<!-- /New user registration Modal -->

</div>
</div>
<br />

<div class="row">
<div class="col-sm-12">

<form id="search-user-form" method="POST">

<div class="form-group row">
<label for="user_id" class="col-sm-3 ml-sm-auto col-form-label"><?= $msg_ary['00200'] ?></label>
<div class="col-sm-4 mr-sm-auto">
<input type="text" name="user_id" maxlength="128" class="form-control" value="<?= $search_key_user_id ?>" />
</div>
</div>

<div class="form-group row">
<label for="email" class="col-sm-3 ml-sm-auto col-form-label"><?= $msg_ary['00240'] ?></label>
<div class="col-sm-4 mr-sm-auto">
<input type="text" name="email" maxlength="256" class="form-control" value="<?= $search_key_email ?>" />
</div>
</div>

<div class="form-group row">
<label for="lastname" class="col-sm-3 ml-sm-auto col-form-label"><?= $msg_ary['00220'] ?></label>
<div class="col-sm-4 mr-sm-auto">
<input type="text" name="lastname" maxlength="64" class="form-control" value="<?= $search_key_lastname ?>" />
</div>
</div>

<div class="form-group row">
<label for="firstname" class="col-sm-3 ml-sm-auto col-form-label"><?= $msg_ary['00230'] ?></label>
<div class="col-sm-4 mr-sm-auto">
<input type="text" name="firstname" maxlength="64" class="form-control" value="<?= $search_key_firstname ?>" />
</div>
</div>

<div class="form-group row">
<label for="locale" class="col-sm-3 ml-sm-auto col-form-label"><?= $msg_ary['00250'] ?></label>
<div class="col-sm-4 mr-sm-auto">
<select name="locale" class="form-control">
<option value=""></option>

<?php
foreach ($locales as $value) {
  if ($search_key_locale == $value) {
    echo '<option value="' . $value . '" selected>' . $value . '</option>';
  }
  else {
    echo '<option value="' . $value . '">' . $value . '</option>';
  }
}
?>

</select>
</div>
</div>

<div class="form-group row">
<div class="col-sm-7 ml-sm-auto">
<button type="button" id="search-user-reset-btn" class="btn btn-secondary"><?= $msg_ary['00270'] ?></button>
<button type="submit" id="search-user-submit-btn" class="btn btn-primary"><?= $msg_ary['00290'] ?></button>
</div>
</div>

<input type="hidden" name="case" value="search_user" />
<input type="hidden" name="page" value="" />
<input type="hidden" name="token" value="<?= sha1(session_id()) ?>" />
</form>

</div>
</div>
<br />

<div class="row">
<div class="col-sm-12">

<?php
if (count($users) > 0) {
  /* ------- <When there is a search result> ------- */
?>
(<?= $msg_ary['00300'] ?><?= $total ?><?= $msg_ary['00310'] ?>)&nbsp;
<?php
  // for paging
  if ($page > 1) {
?>
<a href="javascript:void(0)" onclick="paging(<?= $page - 1 ?>); return false;">&lt;<?= $msg_ary['00320'] ?></a>
<?php
  }
  if ($page < $max_page) {
?>　　
<a href="javascript:void(0)" onclick="paging(<?= $page + 1 ?>); return false;"><?= $msg_ary['00330'] ?>&gt;</a>
<?php
  }
  if ($page > 1 or $page < $max_page) {
?>
&nbsp;(<?= $page ?>/<?= $max_page ?>)
<?php
  }
?>

<table class="table table-bordered">
<thead>
<tr>
<th><?= $msg_ary['00200'] ?></th>
<th><?= $msg_ary['00220'] ?></th>
<th><?= $msg_ary['00230'] ?></th>
<th><?= $msg_ary['00240'] ?></th>
<th><?= $msg_ary['00250'] ?></th>
<th></th>
<th></th>
</tr>
</thead>
<tbody>

<?php
  foreach ($users as $value) {
?>

<tr>
<td><?= htmlspecialchars($value["user_id"]) ?></td>
<td><?= htmlspecialchars($value["lastname"]) ?></td>
<td><?= htmlspecialchars($value["firstname"]) ?></td>
<td><?= htmlspecialchars($value["email"]) ?></td>
<td><?= htmlspecialchars($value["locale"]) ?></td>
<td><button type="button" class="btn btn-warning btn-sm update-user-btn" value="<?= $value["user_id"] ?>"><?= $msg_ary['00340'] ?></button></td>
<td><button type="button" class="btn btn-danger btn-sm del-user-btn" value="<?= $value["user_id"] ?>"><?= $msg_ary['00350'] ?></button></td>
</tr>

<?php
  }
?>

</tbody>
</table>

<!-- User change Modal -->
<div class="modal fade" id="update-user-modal" tabindex="-1" role="dialog" aria-labelledby="myModalLabel2" aria-hidden="true">
<div class="modal-dialog modal-lg" role="document">
<div class="modal-content">
<div class="modal-header">
<button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
<h4 class="modal-title" id="myModalLabel2"><?= $msg_ary['00360'] ?></h4>
</div>
<div class="modal-body">

<form id="update-user-form" method="POST">

<div class="form-group row">
<label for="user_id" class="col-sm-3 ml-sm-auto col-form-label"><?= $msg_ary['00200'] ?></label>
<div class="col-sm-4 mr-sm-auto">
<p class="user_id"></p>
</div>
</div>

<div class="form-group row">
<label for="lastname" class="col-sm-3 ml-sm-auto col-form-label"><?= $msg_ary['00220'] ?></label>
<div class="col-sm-4 mr-sm-auto">
<input type="text" name="lastname" maxlength="64" class="form-control" required />
</div>
</div>

<div class="form-group row">
<label for="firstname" class="col-sm-3 ml-sm-auto col-form-label"><?= $msg_ary['00230'] ?></label>
<div class="col-sm-4 mr-sm-auto">
<input type="text" name="firstname" maxlength="64" class="form-control" required />
</div>
</div>

<div class="form-group row">
<label for="email" class="col-sm-3 ml-sm-auto col-form-label"><?= $msg_ary['00240'] ?></label>
<div class="col-sm-4 mr-sm-auto">
<input type="email" name="email" maxlength="256" class="form-control" required />
</div>
</div>

<div class="form-group row">
<label for="locale" class="col-sm-3 ml-sm-auto col-form-label"><?= $msg_ary['00250'] ?></label>
<div class="col-sm-4 mr-sm-auto">
<select name="locale" class="form-control">

<?php
foreach ($locales as $value) {
  echo '<option value="' . $value . '">' . $value . '</option>';
}
?>

</select>
</div>
</div>

<div class="form-group row">
<label for="role_id" class="col-sm-3 ml-sm-auto col-form-label"><?= $msg_ary['00260'] ?></label>
<div class="col-sm-4 mr-sm-auto">
<select name="role_id" class="form-control">

<?php
  foreach ($roles as $value) {
    echo '<option value="' . $value . '">' . $value . '</option>';
  }
?>

</select>
</div>
</div>

<div class="form-group row">
<div class="col-sm-7 ml-sm-auto">
<button type="button" id="update-user-submit-btn" class="btn btn-primary"><?= $msg_ary['00340'] ?></button>
</div>
</div>

<input type="hidden" name="user_id" value="" />
</form>

</div>
</div>
</div>
</div>
<!-- /User change Modal -->
<?php
  /* ------- </When there is a search result> ------- */
}
elseif (!empty(filter_input(INPUT_POST, 'search_user'))) {
?>

<div class="text-sm-center">
<strong><?= $msg_ary['00370'] ?></strong>
</div>
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

<script src="//code.jquery.com/jquery-3.2.1.min.js" integrity="sha256-hwg4gsxgFZhOsEEamdOYGBf13FyQuiTwlAQgxVSNgt4=" crossorigin="anonymous"></script>
<script src="//cdnjs.cloudflare.com/ajax/libs/popper.js/1.11.0/umd/popper.min.js" integrity="sha384-b/U6ypiBEHpOf/4+1nzFpr53nxSS+GLCkfwBdFNTxtclqqenISfwAzpKaMNFNmj4" crossorigin="anonymous"></script>
<script src="//maxcdn.bootstrapcdn.com/bootstrap/4.0.0-beta/js/bootstrap.min.js" integrity="sha384-h0AbiXch4ZDo7tp9hKZ4TsHbi047NrKGLO3SEJAg45jXxnGIfYzk4Si90RDIqNm1" crossorigin="anonymous"></script>
<script>
(function() {

"use strict";

const root = this,
      $    = root.jQuery;

const paging = function(page) {
  $('#search-user-form [name=page]').val(page);
  $('#search-user-form').submit();
};
root.paging = paging;

$(function() {
  $('#search-user-reset-btn').on('click', function() {
    $('#search-user-form').find("textarea, :text, select").val("").end().find(":checked").prop("checked", false);
  });

  $('#register-user-submit-btn').on('click', function() {
    if (!$('#register-user-form [name=user_id]')[0].checkValidity()) {
      alert('<?= $msg_ary['00380'] ?>');
      return false;
    }
    else if (!$('#register-user-form [name=user_id]').val().match(/^<?= get_regexp('user_id') ?>$/)) {
      alert('<?= $msg_ary['00070'] ?>');
      return false;
    }
    if (!$('#register-user-form [name=password]')[0].checkValidity()) {
      alert('<?= $msg_ary['00390'] ?>');
      return false;
    }
    else if (!$('#register-user-form [name=password]').val().match(/^<?= get_regexp('password') ?>$/)) {
      alert('<?= $msg_ary['00080'] ?>');
      return false;
    }
    if (!$('#register-user-form [name=lastname]')[0].checkValidity()) {
      alert('<?= $msg_ary['00410'] ?>');
      return false;
    }
    if (!$('#register-user-form [name=firstname]')[0].checkValidity()) {
      alert('<?= $msg_ary['00420'] ?>');
      return false;
    }
    if (!$('#register-user-form [name=email]')[0].checkValidity()) {
      alert('<?= $msg_ary['00090'] ?>');
      return false;
    }

    $('#register-user-form').submit();
  });

  $('#register-user-modal').on('hidden.bs.modal', function() {
    $('body').removeClass('modal-open');
  });
 
  $('.update-user-btn').on('click', function() {
    const userId = $(this).val();
    $.ajax({
      url : "<?= filter_input(INPUT_SERVER, 'PHP_SELF') ?>",
      type : "POST",
      beforeSend : function(xhr){
        xhr.setRequestHeader("If-Modified-Since", "Thu, 01 Jun 1970 00:00:00 GMT");
      },
      dataType : "json",
      data : {
        "user_id" : userId,
        "case" : "search_user_solo",
        "token" : "<?= sha1(session_id()) ?>"
      }
    }).done(function(data) {
      var user = data[0];
      $('#update-user-form .user_id').html(user["user_id"]);
      $('#update-user-form [name=user_id]').val(user["user_id"]);
      $('#update-user-form [name=firstname]').val(user["firstname"]);
      $('#update-user-form [name=lastname]').val(user["lastname"]);
      $('#update-user-form [name=email]').val(user["email"]);
      $('#update-user-form [name=locale]').val(user["locale"]);
      $('#update-user-form [name=role_id]').val(user["role_id"]);
      $('#update-user-modal').modal('show');
      $('#update-user-modal').on('hidden.bs.modal', function() {
        $('body').removeClass('modal-open');
      });
    }).fail(function() {
      alert('<?= $msg_ary['00400'] ?>');
      return false;
    });
  });

  $('#update-user-submit-btn').on('click', function() {
    if (!$('#update-user-form [name=lastname]')[0].checkValidity()) {
      alert('<?= $msg_ary['00410'] ?>');
      return false;
    }
    if (!$('#update-user-form [name=firstname]')[0].checkValidity()) {
      alert('<?= $msg_ary['00420'] ?>');
      return false;
    }
    if (!$('#update-user-form [name=email]')[0].checkValidity()) {
      alert('<?= $msg_ary['00090'] ?>');
      return false;
    }

    $.ajax({
      url : "<?= filter_input(INPUT_SERVER, 'PHP_SELF') ?>",
      type : "POST",
      beforeSend : function(xhr){
        xhr.setRequestHeader("If-Modified-Since", "Thu, 01 Jun 1970 00:00:00 GMT");
      },
      dataType : "text",
      data : {
        "case" : "update_user",
        "token" : "<?= sha1(session_id()) ?>",
        "user_id" : $('#update-user-form [name=user_id]').val(),
        "lastname" : $('#update-user-form [name=lastname]').val(),
        "firstname" : $('#update-user-form [name=firstname]').val(),
        "email" : $('#update-user-form [name=email]').val(),
        "locale" : $('#update-user-form [name=locale]').val(),
        "role_id" : $('#update-user-form [name=role_id]').val()
      }
    }).done(function(data) {
      if (data.match(/OK/)) {
        alert('<?= $msg_ary['00440'] ?>');
        $('#update-user-modal').modal('hide');
      }
      else {
        alert('<?= $msg_ary['00450'] ?>');
        return false;
      }
    }).fail(function() {
      alert('<?= $msg_ary['00460'] ?>');
      return false;
    });
  });

  $('.del-user-btn').on('click', function() {
    $('#del-user-form [name=user_id]').val($(this).val());
  	if (window.confirm('<?= $msg_ary['00430'] ?>' + ' (' + $(this).val() + ')')) {
      $('#del-user-form').submit();
	  }
	  else {
      return false;
    }
  });
});

}).call(this);
</script>

<form id="del-user-form" method="POST">
<input type="hidden" name="user_id" value="" />
<input type="hidden" name="case" value="del_user" />
<input type="hidden" name="token" value="<?= sha1(session_id()) ?>" />
</form>

</body>
</html>
