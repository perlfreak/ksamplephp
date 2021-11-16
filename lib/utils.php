<?php
/**
 * 他のファイルで利用する共通関数を定義する。
 * Define common functions to be used in other files.
 *
 * @license https://opensource.org/licenses/mit-license.html MIT License
 * @author 
 */

 /**
 * データベースに接続する。
 * Connect to the database.
 *
 * セッション情報があれば、そのキーが dbname である文字列を接続先とします。
 * If there is session information, connect the character string whose key is dbname to the connection destination.
 * セッション情報がなければ、引数であるDB名を接続先とします。
 * If there is no session information, the DB name which is the argument is the connection destination.
 * データベースに接続したら、mysqliインスタンスを返します。
 * After connecting to the database, return mysqli instance.
 *
 * @param DB名
 *        DB name
 * @return mysqliインスタンス
 *         mysqli instanse
 */
function connect_db(string $dbname) {
  $host = 'localhost';
  $username = 'dbuser';
  $password = '@P0o9i8u7';

  if (!empty($_SESSION['dbname'])) {
    $dbname = $_SESSION['dbname'];
  }

  $mysqli = new mysqli($host, $username, $password, $dbname);
  if ($mysqli->connect_error) {
    error_log($mysqli->connect_error);
    return null;
  }
  else {
    return $mysqli;
  }
}

/**
 * セッションが無効な場合はログインページに遷移させる。
 * If the session is invalid, transition to the login page.
 *
 */
function check_session() {
  if (empty($_SESSION['user_id'])) {
	  header("Location: index.php");
	  exit();
  }
}

/**
 * ロケール別の表示文字列を取得する。
 * Get the locale-specific display string.
 *
 * 引数のロケールがない場合、セッション情報があればそのキーが locale である文字列をロケールとします。
 * If there is no argument locale, if there is session information, the locale is the character string whose key is locale.
 * 引数のロケールがなく、セッション情報もない場合、GETパラメータ locale があればその値をロケールとします。
 * If there is no argument locale and there is no session information, the locale will be the value of the GET parameter locale if it exists.
 * 引数のロケールがなく、セッション情報がなく、GETパラメータ locale もない場合、ja をロケールとします。
 * If there is no argument locale, no session information, and no GET parameter locale, then ja is taken as the locale.
 * この関数を使用するファイルと同一階層にある msg ディレクトリ下の そのファイル名.ロケール.json の中身を取得して連想配列にして返します。
 * Get the contents of the {file name}.{locale}.json under the msg directory located at the same hierarchical level as the file using this function, make it into an associative array, and return.
 *
 * @return 表示文字列の連想配列
 *         Associative array of display string
 */
function get_msg(string $locale): array {
  if ($locale == "") {
    if (!empty($_SESSION['locale'])) {
      $locale = $_SESSION['locale'];
    }
    elseif (!empty(filter_input(INPUT_GET, 'locale'))) {
      $locale = filter_input(INPUT_GET, 'locale');
    }
    else {
      $locale = 'ja';
    }
  }
  return json_decode(file_get_contents('./msg/' . basename(filter_input(INPUT_SERVER, 'PHP_SELF')) . '.' . $locale . '.json'), true);
}

/**
 * 権限をチェックする。
 * Check the authority.
 *
 * セッション情報のキーが permissions である配列の要素に引数であるパーミッションIDが含まれるかチェックし、true または false を返します。
 * It checks whether the element of the array whose session information key is permissions contains the permission ID as an argument and returns true or false.
 *
 * @param パーミッションID
 *        permission id
 */
function check_permission(string $permission_id): bool {
  if (in_array($permission_id, $_SESSION['permissions'])) {
    return true;
  }
  else {
    return false;
  }
}

/**
 * ログを出力する。
 * Output the log.
 *
 * ログファイルは、/etc/php/7.2/apach2/php.ini で設定された /var/log/php/php.log です。
 * The log file is /var/log/php/php.log set in /etc/php/7.2/apache2/php.ini.
 * セッション情報があれば、DB名、ユーザID、引数のエラーメッセージをログに出力します。
 * If there is session information, output DB name, user ID, error message of argument to the log.
 * セッション情報がなければ、引数のエラーメッセージをログに出力します。
 * If there is no session information, output error message of the argument to the log.
 *
 * log_info インフォメーション用
 *          for information
 * log_warn 警告用
 *          for warning
 * log_error エラー用
 *           for error
 * log_fatal 致命的エラー用
 *           for fatal error
 * log_debug デバッグ用
 *           for debug
 *
 * @param エラーメッセージ
 *        error message
 */
function log_info(string $msg) {
  if (!empty($_SESSION['user_id'])) {
    error_log('[INFO] ' . $_SESSION['dbname'] . ' ' . $_SESSION['user_id'] . ' ' . $msg);
  }
  else {
    error_log('[INFO] ' . $msg);
  }
}
function log_warn($msg) {
  if (!empty($_SESSION['user_id'])) {
    error_log('[WARN] ' . $_SESSION['dbname'] . ' ' . $_SESSION['user_id'] . ' ' . $msg);
  }
  else {
    error_log('[WARN] ' . $msg);
  }
}
function log_error($msg) {
  if (!empty($_SESSION['user_id'])) {
    error_log('[ERROR] ' . $_SESSION['dbname'] . ' ' . $_SESSION['user_id'] . ' ' . $msg);
  }
  else {
    error_log('[ERROR] ' . $msg);
  }
}
function log_fatal($msg) {
  if (!empty($_SESSION['user_id'])) {
    error_log('[FATAL] ' . $_SESSION['dbname'] . ' ' . $_SESSION['user_id'] . ' ' . $msg);
  }
  else {
    error_log('[FATAL] ' . $msg);
  }
}
function log_debug($msg) {
  if (!empty($_SESSION['user_id'])) {
    error_log('[DEBUG] ' . $_SESSION['dbname'] . ' ' . $_SESSION['user_id'] . ' ' . $msg);
  }
  else {
    error_log('[DEBUG] ' . $msg);
  }
}

/**
 * 正規表現文字列を取得する。
 * Get the regular expression string.
 *
 * user_id: 先頭はアルファベット、それ以降はアルファベット、数字、アンダーバーのいずれか。半角4文字以上128文字以下。
 *          It starts with an alphabet, after that it is either an alphabet, a number, or an underscore. 4 to 128 characters.
 * password: アルファベットおよび数字。数字のみ、アルファベットのみはNG。半角8文字以上64文字以下。
 *           Alphabets and numbers. Only numbers, only alphabets are NG. 8 to 64 characters.
 *
 * @param キー
 *        key
 * @return 正規表現文字列
 *         regular expression string
 */
function get_regexp(string $key): string {
  if ($key == 'user_id') {
    return '[a-zA-Z]\w{3,127}';
  }
  elseif ($key == 'password') {
    return '(?=.*?[a-zA-Z])(?=.*?\d)[a-zA-Z\d]{8,64}';
  }
  else {
    return "";
  }
}

/**
 * 文字列の形式をチェックする。
 * Check the format of the string.
 *
 * user_id:
 * password:
 * email:
 *
 * @param キー
 *        key
 * @param チェック対象文字列
 *        Character string to be checked
 */
function check_format(string $key, string $value): bool {
  if ($key == 'user_id') {
    $regexp = get_regexp($key);
    if (preg_match('/\A' . $regexp . '\z/', $value)) {
      return true;
    }
    else {
      return false;
    }
  }
  elseif ($key == 'password') {
    $regexp = get_regexp($key);
    if (preg_match('/\A' . $regexp . '\z/', $value)) {
      return true;
    }
    else {
      return false;
    }
  }
  elseif ($key == 'email') {
    return (bool)filter_var($value, FILTER_VALIDATE_EMAIL);
  }
  else {
    return false;
  }
}

/**
 * 1行メッセージ用HTMLを取得する。
 * Get the HTML for one line message.
 *
 * @param タイトルタグ
 *        title tag
 * @param ページタイトル
 *        page title
 * @param メッセージ
 *        message
 * @return HTML
 */
function get_one_msg_html(string $title_tag, string $page_title, string $msg): string {
  $html  = <<<EOT
<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<meta name="robots" content="noindex,nofollow">
<meta http-equiv="Pragma" content="no-cache">
<meta http-equiv="Cache-Control" content="no-cache">
<meta http-equiv="Expires" content="0">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>$title_tag</title>

<link rel="stylesheet" href="css/bootstrap.min.css">
</head>
<body >
<div class="container">

<div class="row text-center">
<div class="col">
<h2>$page_title</h2>
</div>
</div>
<br>

<div class="row text-center">
<div class="col">
$msg
</div>
</div>
<br>

</div>
</body>
</html>
EOT;

  return $html;
}
?>