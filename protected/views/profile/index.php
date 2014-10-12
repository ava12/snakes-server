<script type="text/javascript">

function SetError(Name, Message) {
	var Dom = document.getElementById('e-' + Name)
	if (!Dom) {
		Dom = document.getElementById('e-other')
		if (Message) Message = Dom.innerHTML + Message + '<br>'
	}
	Dom.innerHTML = (Message ? Message : '')
}

function ChangeName() {
	SetError('name')
	var Name = document.getElementById('f-name').value
	if (!Name) {
		SetError('name', 'требуется имя')
		return
	}

	if (Name.trim().length < 2) {
		SetError('name', 'не менее 2 символов')
		return
	}

	PostRequest(BaseUrl + 'profile/chname', {name: Name}, 20,
		function (Data) {
			location.reload()
		},
		function (Status, Message, Data) {
			if (!Status) return

			if (Status == 200) SetError('name', Data.Error)
			else alert('Ошибка ' + Status + ':' + Message)
		}
	)
}

function ChangePassword() {
	var Values = {
		pold: 'введите текущий пароль',
		pnew: 'введите новый пароль',
		pnew2: 'повторите пароль'
	}

	var HasErrors = false

	for (var Name in Values) {
		SetError(Name)
		var Value = document.getElementById('f-' + Name).value
		if (Value) Values[Name] = Value
		else {
			SetError(Name, Values[Name])
			HasErrors = true
		}
	}

	if (HasErrors) return

	if (Values.pnew2 !== Values.pnew) {
		SetError('pnew', 'пароли не совпадают')
		return
	}

	PostRequest(BaseUrl + 'logindata', {}, 20, function (Data) {
		var OldHash = MakeRegistrationHash(Data.Login, Values.pold, Data.Salt)
		var NewHash = MakeRegistrationHash(Data.Login, Values.pnew, Data.Salt)
		var Request = {
			HashDiff: HashXor(OldHash, NewHash),
			Hash: MakeLoginHash(NewHash, Data.Timestamp),
			Timestamp: Data.Timestamp
		}

		PostRequest(BaseUrl + 'profile/chpass', Request, 20, function() {
			location.reload()
		})
	})
}

</script>

<div class="form">
<label for="f-name">Новое имя:</label><br>
<input type="text" id="f-name">
<div class="error" id="e-name"></div>
<input type="button" value="Сменить" onclick="ChangeName()">
</div>

<div class="form">
<label for="f-pold">Текущий пароль:</label><br>
<input type="password" id="f-pold"><br>
<div class="error" id="e-pold"></div>
<label for="f-pnew">Новый пароль:</label><br>
<input type="password" id="f-pnew"><br>
<div class="error" id="e-pnew"></div>
<label for="f-pnew2">Еще раз:</label><br>
<input type="password" id="f-pnew2"><br>
<div class="error" id="e-pnew2"></div>
<div class="error" id="e-other"></div>
<input type="button" value="Сменить" onclick="ChangePassword()">
</div>

