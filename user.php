<?php
/**
 * ユーザ情報管理（検索参照、登録、変更、削除）を行う。
 * User information management (search reference, registration, change, deletion)
 *
 * パーミッションID: manage_users が必要です。
 * Permission ID: manage_users is required.
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
$search_key_del_flg = "";

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
  if (filter_input(INPUT_POST, 'token') != sha1(session_id())) {
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
            $exec_err1 = $stmt->error;
            $stmt2->execute();
            $exec_err2 = $stmt->error;

            if (!$exec_err1 and !$exec_err2) {
              $mysqli->commit();
              $msg = $msg_ary['00100'];
              log_info(filter_input(INPUT_SERVER, 'PHP_SELF') . ' ' . $msg);
            }
            else {
              $mysqli->rollback();
              $msg = $msg_ary['00110'];
              log_error(filter_input(INPUT_SERVER, 'PHP_SELF') . ' ' . $msg . ' (' . $exec_err1 . ' / ' . $exec_err2 . ')');
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
        $del_flg = $mysqli->real_escape_string(filter_input(INPUT_POST, 'del_flg'));

        // where
        $where = " WHERE 1";
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
        if ($del_flg == '1') {
          $where = $where . " AND del_flg = 1";
          $search_key_del_flg = 'checked';
        }
        else {
          $where = $where . " AND del_flg = 0";
          $search_key_del_flg = "";
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
          SELECT user_id, firstname, lastname, email, locale, del_flg
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
              "locale" => $row["locale"],
              "del_flg" => $row["del_flg"]
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
            SELECT u.user_id, u.firstname, u.lastname, u.email, u.locale, u.del_flg, ur.role_id
            FROM users u, user_role ur
            WHERE u.user_id = ?
             AND u.user_id = ur.user_id
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
                "del_flg" => htmlspecialchars($row["del_flg"]),
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
            and filter_input(INPUT_POST, 'del_flg') != ""
            and !empty(filter_input(INPUT_POST, 'role_id'))) {
          log_info(filter_input(INPUT_SERVER, 'PHP_SELF') . ' update_user user_id=' . filter_input(INPUT_POST, 'user_id'));
          $user_id = $mysqli->real_escape_string(htmlspecialchars_decode(filter_input(INPUT_POST, 'user_id')));
          $firstname = $mysqli->real_escape_string(htmlspecialchars_decode(filter_input(INPUT_POST, 'firstname')));
          $lastname = $mysqli->real_escape_string(htmlspecialchars_decode(filter_input(INPUT_POST, 'lastname')));
          $email = $mysqli->real_escape_string(htmlspecialchars_decode(filter_input(INPUT_POST, 'email')));
          $locale = $mysqli->real_escape_string(htmlspecialchars_decode(filter_input(INPUT_POST, 'locale')));
          $del_flg = $mysqli->real_escape_string(htmlspecialchars_decode(filter_input(INPUT_POST, 'del_flg')));
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
              SET firstname = ?, lastname = ?, email = ?, locale = ?, del_flg = ?, update_user = '" . $_SESSION['user_id'] . "'
              WHERE user_id = ?
            ");
            $stmt->bind_param('ssssis', $firstname, $lastname, $email, $locale, $del_flg, $user_id);
            $stmt2 = $mysqli->prepare("
              UPDATE user_role
              SET role_id = ?, update_user = '" . $_SESSION['user_id'] . "'
              WHERE user_id = ?
            ");
            $stmt2->bind_param('ss', $role_id, $user_id);

            $mysqli->autocommit(FALSE);
            $stmt->execute();
            $exec_err1 = $stmt->error;
            $stmt2->execute();
            $exec_err2 = $stmt->error;

            header("Content-type: text/plain; charset=UTF-8");
            if (!$exec_err1 and !$exec_err2) {
              $mysqli->commit();
              log_info(filter_input(INPUT_SERVER, 'PHP_SELF') . ' ' . $msg_ary['00130']);
              echo 'OK';
            }
            else {
              $mysqli->rollback();
              log_error(filter_input(INPUT_SERVER, 'PHP_SELF') . ' ' . $msg_ary['00140'] . ' (' . $exec_err1 . ' / ' . $exec_err2 . ')');
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

          if ($stmt->execute()) {
            $msg = $msg_ary['00150'];
            log_info(filter_input(INPUT_SERVER, 'PHP_SELF') . ' ' . $msg_ary['00150']);
          }
          else {
            $msg = $msg_ary['00160'];
            log_error(filter_input(INPUT_SERVER, 'PHP_SELF') . ' ' . $msg . ' (' . $stmt->error . ')');
          }

          $mysqli->close();
        }

        break;  // del_user

      case "del_user_comp":
        /*** ユーザ完全削除処理
             User deletion processing ***/

        if (!empty(filter_input(INPUT_POST, 'user_id'))) {
          log_info(filter_input(INPUT_SERVER, 'PHP_SELF') . ' del_user_comp user_id=' . filter_input(INPUT_POST, 'user_id'));
          $user_id = $mysqli->real_escape_string(filter_input(INPUT_POST, 'user_id'));

          $stmt = $mysqli->prepare("
            DELETE FROM users
            WHERE user_id = ?
             AND del_flg = 1
          ");
          $stmt->bind_param('s', $user_id);
          $stmt2 = $mysqli->prepare("
            DELETE FROM user_role
            WHERE user_id = ?
          ");
          $stmt2->bind_param('s', $user_id);

          $mysqli->autocommit(FALSE);
          $stmt->execute();
          $exec_err1 = $stmt->error;
          $stmt2->execute();
          $exec_err2 = $stmt->error;

          if (!$exec_err1 and !$exec_err2) {
            $mysqli->commit();
            $msg = $msg_ary['00170'];
            log_info(filter_input(INPUT_SERVER, 'PHP_SELF') . ' ' . $msg_ary['00150']);
          }
          else {
            $mysqli->rollback();
            $msg = $msg_ary['00180'];
            log_error(filter_input(INPUT_SERVER, 'PHP_SELF') . ' ' . $msg . ' (' . $exec_err1 . ' / ' . $exec_err2 . ')');
          }

          $mysqli->close();
        }

        break;  // del_user_comp
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
<title><?= $msg_ary['00010'] ?></title>

<link rel="stylesheet" href="css/bootstrap.min.css">
</head>
<body>
<div class="container">

<?php
include_once 'header.php';
?>

<div class="row">
<div class="col">
<h3><?= $msg_ary['00190'] ?></h3>
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
<?= $msg_ary['00200'] ?>
</div>
</div>
<br>
<?php
  /* ------- </Message before processing> ------- */
}
?>

<!-- Button trigger modal -->
<div class="row">
<div class="col">
<button type="button" class="btn btn-outline-primary" data-bs-toggle="modal" data-bs-target="#register-user-modal">
<?= $msg_ary['00210'] ?>
</button>
</div>
</div>

<!-- New user registration Modal -->
<div class="modal fade" id="register-user-modal" tabindex="-1" aria-labelledby="register-user-modal-label" aria-hidden="true">
<div class="modal-dialog modal-lg">
<div class="modal-content">
<form id="register-user-form" method="POST">

<div class="modal-header">
<h5 class="modal-title" id="register-user-modal-label"><?= $msg_ary['00220'] ?></h5>
<button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
</div>
<div class="modal-body">
<div class="row d-flex justify-content-center">
<div class="col-6">

<div class="row mb-2">
<div class="col">
<input type="text" name="user_id" placeholder="<?= $msg_ary['00230'] ?>" maxlength="128" class="form-control" required autofocus>
</div>
</div>

<div class="row mb-2">
<div class="col">
<input type="password" id="password" name="password" placeholder="<?= $msg_ary['00240'] ?>" maxlength="64" autocomplete="off" class="form-control" required>
</div>
</div>

<div class="row mb-2">
<div class="col">
<input type="text" name="lastname" placeholder="<?= $msg_ary['00250'] ?>" maxlength="64" class="form-control" required>
</div>
</div>

<div class="row mb-2">
<div class="col">
<input type="text" name="firstname" placeholder="<?= $msg_ary['00260'] ?>" maxlength="64" class="form-control" required>
</div>
</div>

<div class="row mb-2">
<div class="col">
<input type="email" name="email" placeholder="<?= $msg_ary['00270'] ?>" maxlength="256" class="form-control" required>
</div>
</div>

<div class="row mb-3">
<label for="locale" class="col-sm-3 col-form-label"><?= $msg_ary['00280'] ?></label>
<div class="col-sm-9">
<select name="locale" class="form-select" aria-label="locale">
<?php
foreach ($locales as $value) {
  echo '<option value="' . $value . '">' . $value . '</option>';
}
?>
</select>
</div>
</div>

<div class="row mb-3">
<label for="role_id" class="col-sm-3 col-form-label"><?= $msg_ary['00290'] ?></label>
<div class="col-sm-9">
<select name="role_id" class="form-select" aria-label="role_id" required>
<?php
foreach ($roles as $value) {
  echo '<option value="' . $value . '">' . $value . '</option>';
}
?>
</select>
</div>
</div>

<div class="row g-3">
<div class="col-auto">
<button type="reset" class="btn btn-light"><?= $msg_ary['00300'] ?></button>
<button type="submit" id="register-user-submit-btn" class="btn btn-primary"><?= $msg_ary['00310'] ?></button>
</div>
</div>

</div>
</div>
</div>

<input type="hidden" name="case" value="register_user">
<input type="hidden" name="token" value="<?= sha1(session_id()) ?>">
</form>
</div>
</div>
</div>
<!-- /New user registration Modal -->

<br>

<form id="search-user-form" method="POST">

<div class="row d-flex justify-content-center">
<div class="col-4">

<div class="row mb-2">
<div class="col">
<input type="text" name="user_id" placeholder="<?= $msg_ary['00230'] ?>" maxlength="128" class="form-control" value="<?= $search_key_user_id ?>">
</div>
</div>

<div class="row mb-2">
<div class="col">
<input type="text" name="email" placeholder="<?= $msg_ary['00270'] ?>" maxlength="256" class="form-control" value="<?= $search_key_email ?>">
</div>
</div>

<div class="row mb-2">
<div class="col">
<input type="text" name="lastname" placeholder="<?= $msg_ary['00250'] ?>" maxlength="64" class="form-control" value="<?= $search_key_lastname ?>">
</div>
</div>

<div class="row mb-2">
<div class="col">
<input type="text" name="firstname" placeholder="<?= $msg_ary['00260'] ?>" maxlength="64" class="form-control" value="<?= $search_key_firstname ?>">
</div>
</div>

<div class="row mb-3">
<label for="locale" class="col-sm-3 col-form-label"><?= $msg_ary['00280'] ?></label>
<div class="col-sm-9">
<select name="locale" class="form-select" aria-label="locale">
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

<div class="row mb-2">
<div class="col">
<div class="form-check">
<input class="form-check-input" type="checkbox"  name="del_flg" value="1" <?= $search_key_del_flg ?>>
<label class="form-check-label" for="del_flg">
<?= $msg_ary['00320'] ?>
</label>
</div>
</div>
</div>

<div class="row g-3">
<div class="col-auto">
<button type="button" id="search-user-reset-btn" class="btn btn-light"><?= $msg_ary['00300'] ?></button>
<button type="submit" id="search-user-submit-btn" class="btn btn-primary"><?= $msg_ary['00330'] ?></button>
</div>
</div>

</div>
</div>

<input type="hidden" name="case" value="search_user">
<input type="hidden" name="page" value="">
<input type="hidden" name="token" value="<?= sha1(session_id()) ?>">
</form>
<br>
<br>

<div class="row">
<div class="col">

<?php
if (count($users) > 0) {
  /* ------- <When there is a search result> ------- */
?>
(<?= $msg_ary['00340'] ?><?= $total ?><?= $msg_ary['00350'] ?>)&nbsp;
<?php
  // for paging
  if ($page > 1) {
?>
<a href="javascript:void(0)" onclick="paging(<?= $page - 1 ?>); return false;">&lt;<?= $msg_ary['00360'] ?></a>
<?php
  }
  if ($page < $max_page) {
?>　　
<a href="javascript:void(0)" onclick="paging(<?= $page + 1 ?>); return false;"><?= $msg_ary['00370'] ?>&gt;</a>
<?php
  }
  if ($page > 1 or $page < $max_page) {
?>
&nbsp;(<?= $page ?>/<?= $max_page ?>)
<?php
  }
?>

<table class="table table-hover">
<thead>
<tr>
<th><?= $msg_ary['00230'] ?></th>
<th><?= $msg_ary['00250'] ?></th>
<th><?= $msg_ary['00260'] ?></th>
<th><?= $msg_ary['00270'] ?></th>
<th><?= $msg_ary['00280'] ?></th>
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
<td><button type="button" class="btn btn-warning btn-small update-user-btn" value="<?= $value["user_id"] ?>"><?= $msg_ary['00390'] ?></button></td>
<?php
    if ($value["user_id"] == 'admin') {
?>
<td></td>
<?php
    }
    elseif ($value["del_flg"] == '1') {
?>
<td><button type="button" class="btn btn-danger btn-small del-user-comp-btn" value="<?= $value["user_id"] ?>"><?= $msg_ary['00410'] ?></button></td>
<?php
    }
    else {
?>
<td><button type="button" class="btn btn-danger btn-small del-user-btn" value="<?= $value["user_id"] ?>"><?= $msg_ary['00400'] ?></button></td>
<?php
    }
?>
</tr>
<?php
  }
?>
</tbody>
</table>

<!-- User change Modal -->
<div class="modal fade" id="update-user-modal" tabindex="-1" aria-labelledby="update-user-modal-label" aria-hidden="true">
<div class="modal-dialog modal-lg">
<div class="modal-content">
<form id="update-user-form" method="POST">

<div class="modal-header">
<h5 class="modal-title" id="update-user-modal-label"><?= $msg_ary['00420'] ?></h5>
<button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
</div>
<div class="modal-body">
<div class="row d-flex justify-content-center">
<div class="col-6">

<div class="row mb-2">
<div class="col">
<label class="uk-form-label" for="locale"><?= $msg_ary['00230'] ?></label>
<div class="form-control">
<p class="user_id"></p>
</div>
</div>
</div>

<div class="row mb-2">
<div class="col">
<input type="text" name="lastname" placeholder="<?= $msg_ary['00250'] ?>" maxlength="64" class="form-control" required>
</div>
</div>

<div class="row mb-2">
<div class="col">
<input type="text" name="firstname" placeholder="<?= $msg_ary['00260'] ?>" maxlength="64" class="form-control" required>
</div>
</div>

<div class="row mb-2">
<div class="col">
<input type="email" name="email" placeholder="<?= $msg_ary['00270'] ?>" maxlength="256" class="form-control" required>
</div>
</div>

<div class="row mb-3">
<label for="locale" class="col-sm-3 col-form-label"><?= $msg_ary['00280'] ?></label>
<div class="col-sm-9">
<select name="locale" class="form-select" aria-label="locale">
<?php
foreach ($locales as $value) {
  echo '<option value="' . $value . '">' . $value . '</option>';
}
?>
</select>
</div>
</div>

<div class="row mb-3">
<label for="del_flg" class="col-sm-3 col-form-label"><?= $msg_ary['00380'] ?></label>
<div class="col-sm-9">
<select name="del_flg" class="form-select" aria-label="del_flg">
<option value="0">0</option>
<option value="1">1</option>
</select>
</div>
</div>

<div class="row mb-3">
<label for="role_id" class="col-sm-3 col-form-label"><?= $msg_ary['00290'] ?></label>
<div class="col-sm-9">
<select name="role_id" class="form-select" required>
<?php
foreach ($roles as $value) {
  echo '<option value="' . $value . '">' . $value . '</option>';
}
?>
</select>
</div>
</div>

<div class="row g-3">
<div class="col-auto">
<button type="button" id="update-user-submit-btn" class="btn btn-primary"><?= $msg_ary['00390'] ?></button>
</div>
</div>

</div>
</div>
</div>

<input type="hidden" name="user_id" value="">
</form>
</div>
</div>
</div>
<!-- /User change Modal -->
<?php
  /* ------- </When there is a search result> ------- */
}
elseif (filter_input(INPUT_POST, 'case') == 'search_user') {
?>
<div class="row text-center">
<div class="col">
<strong><?= $msg_ary['00430'] ?></strong>
</div>
</div>
<br>
<?php
}
?>

</div>
</div>
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

const root = this;

let updateUserModal;

const paging = function(page) {
  document.querySelector('#search-user-form [name="page"]').value = page;
  document.getElementById('search-user-form').submit();
};
root.paging = paging;

document.addEventListener('DOMContentLoaded', function() {
  document.getElementById('search-user-reset-btn').addEventListener('click', function() {
    document.querySelector('#search-user-form [name="user_id"]').value = "";
    document.querySelector('#search-user-form [name="email"]').value = "";
    document.querySelector('#search-user-form [name="lastname"]').value = "";
    document.querySelector('#search-user-form [name="firstname"]').value = "";
    document.querySelector('#search-user-form [name="locale"]').value = "";
  });

  document.getElementById('register-user-submit-btn').addEventListener('click', function() {
    if (!document.querySelector('#register-user-form [name="user_id"]').checkValidity()) {
      alert('<?= $msg_ary['00440'] ?>');
      return false;
    }
    else if (!document.querySelector('#register-user-form [name="user_id"]').value.match(/^<?= get_regexp('user_id') ?>$/)) {
      alert('<?= $msg_ary['00070'] ?>');
      return false;
    }
    if (!document.querySelector('#register-user-form [name="password"]').checkValidity()) {
      alert('<?= $msg_ary['00450'] ?>');
      return false;
    }
    else if (!document.querySelector('#register-user-form [name="password"]').value.match(/^<?= get_regexp('password') ?>$/)) {
      alert('<?= $msg_ary['00080'] ?>');
      return false;
    }
    if (!document.querySelector('#register-user-form [name="lastname"]').checkValidity()) {
      alert('<?= $msg_ary['00470'] ?>');
      return false;
    }
    if (!document.querySelector('#register-user-form [name="firstname"]').checkValidity()) {
      alert('<?= $msg_ary['00480'] ?>');
      return false;
    }
    if (!document.querySelector('#register-user-form [name="email"]').checkValidity()) {
      alert('<?= $msg_ary['00090'] ?>');
      return false;
    }

    document.getElementById('register-user-form').submit();
  });

  document.querySelectorAll('.update-user-btn').forEach(function(button) {
    button.addEventListener('click', function() {
      const userId = button.value;
      const postData = new FormData;
      postData.set('user_id', userId);
      postData.set('case', 'search_user_solo');
      postData.set('token', '<?= sha1(session_id()) ?>');
      fetch('<?= filter_input(INPUT_SERVER, 'PHP_SELF') ?>', {
        method: 'POST',
        cache: 'no-cache',
        body: postData
      })
      .then(function(response) {
        return response.json();
      })
      .then(function(data) {
        const user = data[0];
        document.querySelector('#update-user-form .user_id').innerHTML = user["user_id"];
        document.querySelector('#update-user-form [name="user_id"]').value = user["user_id"];
        document.querySelector('#update-user-form [name="firstname"]').value = user["firstname"];
        document.querySelector('#update-user-form [name="lastname"]').value = user["lastname"];
        document.querySelector('#update-user-form [name="email"]').value = user["email"];
        document.querySelector('#update-user-form [name="locale"]').value = user["locale"];
        document.querySelector('#update-user-form [name="del_flg"]').value = user["del_flg"];
        document.querySelector('#update-user-form [name="role_id"]').value = user["role_id"];

        updateUserModal = new bootstrap.Modal(document.getElementById('update-user-modal'), {
          keyboard: false
        });
        updateUserModal.show();
      })
      .catch(function(err) {
        console.error('Error:', err);
        alert('<?= $msg_ary['00460'] ?>');
      });
    });
  });

<?php
if (count($users) > 0) {
  /* ------- <When there is a search result> ------- */
?>
  document.getElementById('update-user-submit-btn').addEventListener('click', function() {
    if (!document.querySelector('#update-user-form [name=lastname]').checkValidity()) {
      alert('<?= $msg_ary['00470'] ?>');
      return false;
    }
    if (!document.querySelector('#update-user-form [name=firstname]').checkValidity()) {
      alert('<?= $msg_ary['00480'] ?>');
      return false;
    }
    if (!document.querySelector('#update-user-form [name=email]').checkValidity()) {
      alert('<?= $msg_ary['00090'] ?>');
      return false;
    }

    const postData = new FormData;
    postData.set('case', 'update_user');
    postData.set('token', '<?= sha1(session_id()) ?>');
    postData.set('user_id', document.querySelector('#update-user-form [name="user_id"]').value);
    postData.set('lastname', document.querySelector('#update-user-form [name="lastname"]').value);
    postData.set('firstname', document.querySelector('#update-user-form [name="firstname"]').value);
    postData.set('email', document.querySelector('#update-user-form [name="email"]').value);
    postData.set('locale', document.querySelector('#update-user-form [name="locale"]').value);
    postData.set('del_flg', document.querySelector('#update-user-form [name="del_flg"]').value);
    postData.set('role_id', document.querySelector('#update-user-form [name="role_id"]').value);
    fetch('<?= filter_input(INPUT_SERVER, 'PHP_SELF') ?>', {
      method: 'POST',
      cache: 'no-cache',
      body: postData
    })
    .then(function(response) {
      return response.text();
    })
    .then(function(data) {
      if (data.match(/OK/)) {
        alert('<?= $msg_ary['00500'] ?>');
        updateUserModal.hide();
      }
      else {
        alert('<?= $msg_ary['00510'] ?>');
        return false;
      }
    })
    .catch(function(err) {
      console.error('Error:', err);
      alert('<?= $msg_ary['00520'] ?>');
      return false;
    });
  });
<?php
  /* ------- </When there is a search result> ------- */
}
?>

  document.querySelectorAll('.del-user-btn').forEach(function(button) {
    button.addEventListener('click', function() {
      document.querySelector('#del-user-form [name="user_id"]').value = button.value;
      if (window.confirm('<?= $msg_ary['00490'] ?>' + ' (' + button.value + ')')) {
        document.getElementById('del-user-form').submit();
      }
      else {
        return false;
      }
    });
  });

  document.querySelectorAll('.del-user-comp-btn').forEach(function(button) {
    button.addEventListener('click', function() {
      document.querySelector('#del-user-comp-form [name="user_id"]').value = button.value;
      if (window.confirm('<?= $msg_ary['00530'] ?>' + ' (' + button.value + ')')) {
        document.getElementById('del-user-comp-form').submit();
      }
      else {
        return false;
      }
    });
  });
});

}).call(this);
</script>

<form id="del-user-form" method="POST">
<input type="hidden" name="user_id" value="">
<input type="hidden" name="case" value="del_user">
<input type="hidden" name="token" value="<?= sha1(session_id()) ?>">
</form>

<form id="del-user-comp-form" method="POST">
<input type="hidden" name="user_id" value="">
<input type="hidden" name="case" value="del_user_comp">
<input type="hidden" name="token" value="<?= sha1(session_id()) ?>">
</form>

</body>
</html>
