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
$header_msg_ary = json_decode(file_get_contents('./msg/header.php.json'), true)[$locale];
?>
<div class="row">
<div class="col-sm-12">
<nav class="navbar navbar-expand-sm navbar-dark bg-dark">
<a class="navbar-brand" href="mypage.php"><?= $header_msg_ary['00010'] ?></a>
<button class="navbar-toggler" type="button" data-toggle="collapse" data-target="#navbarNavDropdown" aria-controls="navbarNavDropdown" aria-expanded="false" aria-label="Toggle navigation">
<span class="navbar-toggler-icon"></span>
</button>
<div class="collapse navbar-collapse" id="navbarNavDropdown">
<ul class="navbar-nav ml-sm-auto">
<li class="nav-item dropdown">
<a class="nav-link dropdown-toggle" href="#" id="dropdownMenu1" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false"><?= $header_msg_ary['00020'] ?></a>
<div class="dropdown-menu" aria-labelledby="dropdownMenu1">

<?php
if (check_permission('manage_users')) {
?>
<a class="dropdown-item" href="user.php"><?= $header_msg_ary['00030'] ?></a>
<?php
}
?>

</div>
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
</div>
</div>
<br />