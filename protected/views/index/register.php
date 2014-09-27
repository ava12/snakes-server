<script type="text/javascript">

function RefreshCaptcha() {
	var Image = document.getElementById('f-img')
	Image.src = ''
	Image.src = 'captcha?t=' + (new Date()).getTime()
	document.getElementById('f-captcha').value = ''
}

function SetError(Name, Message) {
	document.getElementById('e-' + Name).innerHTML = Message
}

function GetValues() {
	var Fields = [
		['name', true, 2, null, 'требуется имя, не менее 2 символов'],
		['login', true, 2, /^[a-z0-9_.-]+$/, 'требуется логин (строчные латинские буквы, цифры, ._-, не менее 2 символов)'],
		['password', true, 1, null, 'требуется пароль'],
		['password2', true, 1, null, 'повторите пароль'],
		['forum_login', false, 0, null],
		['captcha', true, 1, null, 'требуется проверочный код'],
	]

	var Values = {}
	var HasErrors = false

	for (var i in Fields) {
		var Name = Fields[i][0]
		SetError(Name, '')
		var Value = document.getElementById('f-' + Name).value
		Values[Name] = Value
		if (
			(!Value && Fields[i][1]) ||
			(Value.length < Fields[i][2]) ||
			(Fields[i][3] && !Value.match(Fields[i][3]))
		) {
			HasErrors = true
			SetError(Name, Fields[i][4])
		}
	}

	if (Values.password && Values.password2 && Values.password !== Values.password2) {
			HasErrors = true
			SetError('password2', 'пароли не совпадают')
	}

	if (HasErrors) return false
	else return Values
}

function Register() {
	var Values = GetValues()
	if (!Values) return

	var Hash = MakeRegistrationHash(Values.login, Values.password, MakeSalt())
	var Data = {login: 1, name: 1, forum_login: 1, captcha: 1}
	for (var Name in Data) Data[Name] = Values[Name]
	Data.hash = Hash
	Ajax.Post('', Data, 30,
		function (Data) {
			try {
				Data = JSON.parse(Data)
				if (Data.Response && Data.Response == 'ack') {
					LoginRegistered(Data, Hash)
					return
				}

				if (Data.Errors) {
					for (var Name in Data.Errors) {
						SetError(Name, Data.Errors[Name][0])
					}
				}

				if (Data.Message) {
					alert(Data.Message)
				}
			} catch (e) {
				alert('Ошибка: некорректный формат ответа сервера')
			}
		},
		function (Status, Message) {
			alert('Ошибка ' + Status + ': ' + Message)
		}
	)
}

function LoginRegistered(Data, Hash) {
}


if (!window.JSON) {
	RefreshCaptcha = function() {}
	Register = function() {}
	alert('Форма регистрации не работает в устаревших браузерах')
}

</script>

<div class="form">
<?php

$defaultAttr = array('type' => 'text');
$fieldDef = array(
	'name' => array('label' => 'Имя:', 'attr' => array('maxlength' => 40)),
	'login' => array('label' => 'Логин:', 'attr' => array('maxlength' => 30)),
	'password' => array('label' => 'Пароль:', 'attr' => array('type' => 'password')),
	'password2' => array('label' => 'Повторите пароль:', 'attr' => array('type' => 'password')),
	'forum_login' => array('label' => 'Логин на форуме (необязательно):', 'attr' => array('maxlength' => 40)),
);

foreach ($fieldDef as $name => $def) {
	$attr = $def['attr'] + $defaultAttr;
	$input = '<input id="f-' . $name . '"';
	foreach ($attr as $attrName => $attrValue) {
		$input .= " $attrName=\"$attrValue\"";
	}
	$input .= '>';
?>
<label for="f-<?= $name ?>"><?= $def['label'] ?></label><br>
<?= $input ?>
<div class="error" id="e-<?= $name ?>"></div>
<?php
}
?>
<br>
<img id="f-img" class="captcha" alt="контрольный код" title="сменить картинку" onclick="RefreshCaptcha()">
<input type="text" id="f-captcha"><br>
<div class="error" id="e-captcha"></div>
<br>
<input class="fr" type="button" value="Зарегистрировать" onclick="Register()">
</div>

<script type="text/javascript">
RefreshCaptcha()
</script>