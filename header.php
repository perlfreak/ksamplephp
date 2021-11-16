<?php
/**
 * Header
 *
 * @license https://opensource.org/licenses/mit-license.html MIT License
 * @author 
 */

/* ロケール別の表示文字列を取得
   Get locale-specific display string */
$locale = 'ja';
if (!empty($_SESSION['locale'])) {
  $locale = $_SESSION['locale'];
}
$header_msg_ary = json_decode(file_get_contents('./msg/header.php.' . $locale . '.json'), true);
?>
<nav class="navbar navbar-expand-lg navbar-light bg-light">
<div class="container-fluid">
<a class="navbar-brand" href="mypage.php"><?= $header_msg_ary['00010'] ?></a>
<ul class="navbar-nav">
<li class="nav-item dropdown">
<a class="nav-link dropdown-toggle" href="#" id="navbarDropdownMenuLink" role="button" data-bs-toggle="dropdown" aria-expanded="false">
<?= $header_msg_ary['00020'] ?>
</a>
<ul class="dropdown-menu" aria-labelledby="navbarDropdownMenuLink">
<?php
if (check_permission('manage_users')) {
?>
<li><a class="dropdown-item" href="user.php"><?= $header_msg_ary['00030'] ?></a></li>
<?php
}
?>
</ul>
</li>
<li class="nav-item">
<a href="chg_pw.php" class="nav-link"><?= $header_msg_ary['00040'] ?></a>
</li>
<li class="nav-item">
<a href="logout.php" class="nav-link"><?= $header_msg_ary['00050'] ?></a>
</li>
</ul>
</div>
</nav>
<br>