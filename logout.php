<?php
/**
 * ログアウト処理を行う。
 * Perform logout processing.
 *
 * @license https://opensource.org/licenses/mit-license.html MIT License
 * @author 
 */

session_start();
require_once './lib/utils.php';

$locale = $_SESSION['locale'];
$user_id = $_SESSION['user_id'];
$dbname = $_SESSION['dbname'];

$_SESSION = array();
if (!empty(filter_input(INPUT_COOKIE, 'PHPSESSID'))) {
  setcookie("PHPSESSID", '', time() - 1800, '/');
}
session_destroy();

log_info($dbname . ' ' . $user_id . ' Logout');

header("Location: /" . $dbname . "/index.php?locale={$locale}");
exit;
?>