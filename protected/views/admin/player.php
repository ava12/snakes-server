<?php
	if (!$player) throw new CHttpException(404);

	$this->pageTitle = 'администрирование: игрок ' . $player->id;
?>
<a href="<?= BASE_URL ?>admin/player">Список</a><br><br>
<script type="text/javascript">

var LoginData = {
	AdminLogin: <?= CJavaScript::encode($admin->login) ?>,
	AdminSalt: <?= CJavaScript::encode($admin->salt) ?>,
	Login: <?= CJavaScript::encode($player->login) ?>,
	Salt: <?= CJavaScript::encode($player->salt) ?>,
	TimeDiff: <?= CJavaScript::encode(time()) ?> - Math.floor(Date.parse(new Date()) / 1000)
}

function ChangePassword() {
	var AdminPass = document.getElementById('f-admin').value
	if (!AdminPass) {
		alert('укажите свой пароль')
		return
	}

	var Pass = document.getElementById('f-pass').value
	if (!Pass) {
		alert('укажите новый пароль игрока')
		return
	}

	var AdminHash = MakeRegistrationHash(LoginData.AdminLogin, AdminPass, LoginData.AdminSalt)
	var PlayerHash = MakeRegistrationHash(LoginData.Login, Pass, LoginData.Salt)
	var Timestamp = Math.floor(Date.parse(new Date()) / 1000) + LoginData.TimeDiff
	var LoginHash = MakeLoginHash(PlayerHash, Timestamp)
	PostRequest(BaseUrl + 'admin/chpass', {
		Login: LoginData.Login,
		Hash: LoginHash,
		Timestamp: Timestamp,
		HashDiff: HashXor(AdminHash, PlayerHash)
	}, 20, function () {
		location.reload()
	})
}

</script>

<?php

$list = array(
	'Логин' => $player->login,
	'Имя' => $player->name,
	'Рейтинг' => $player->rating,
	'Зарегистрирован' => date('d.m.y H:i', $player->registered),
);

foreach ($list as $name => $value) {
	$value = htmlspecialchars($value);
	echo "<strong>$name:</strong> $value<br>\r\n";
}
?>
<br>

<label for="f-admin">Твой пароль:</label>
<input type="password" id="f-admin">
<label for="f-pass">Новый пароль игрока:</label>
<input type="text" id="f-pass">
<input type="button" value="Сменить" onclick="ChangePassword()">
