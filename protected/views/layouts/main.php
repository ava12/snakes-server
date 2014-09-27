<!DOCTYPE html>
<html>
<head>
<meta charset="utf-8">
<meta http-equiv="Cache-Control" content="no-cache">

<title>Змеи</title>

<link rel="stylesheet" type="text/css" href="<?= BASE_URL ?>style.css">
<script type="text/javascript" src="<?= BASE_URL ?>js/crypto-sha1.js"></script>
<script type="text/javascript" src="<?= BASE_URL ?>js/util.js"></script>
<script type="text/javascript" src="<?= BASE_URL ?>js/main.js"></script>
</head>

<body>
<noscript><div class="error">Необходимо включить поддержку JavaScript</div></noscript>
<div id="header">
<?php

$user = Yii::app()->user;
if ($user->getId()) { ?>
Привет, <?= htmlspecialchars($user->getName()) ?>
<a id="login-link" href="<?= BASE_URL ?>logout">Выход</a>
<?php } else { ?>
Добрый день, незнакомец.
<a href="<?= BASE_URL ?>login">Войти</a>
<?php } ?>
</div>

<div id="content">
<?= $content ?>
</div>

</body>
</html>