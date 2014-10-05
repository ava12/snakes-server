
//---------------------------------------------------------------------------
function MakeRegistrationHash(Login, Password, Salt) {
	return Crypto.SHA1(Crypto.SHA1(Login + ':' + Password) + Salt)
}

//---------------------------------------------------------------------------
function MakeLoginHash(Hash, Timestamp) {
	return Crypto.SHA1(Hash + Timestamp)
}

//---------------------------------------------------------------------------
function MakeSalt() {
	var Chars = '0123456789abcdef'
	var Len = Chars.length
	var Result = ''
	for (var i = 8; i > 0; i--) {
		Result += Chars.substr(Math.floor(Math.random() * Len), 1)
	}
	return Result
}

//---------------------------------------------------------------------------
function Login() {
	var Login = document.getElementById('l-login').value.toLowerCase()
	var Password = document.getElementById('l-password').value
	if (!Login || !Password) {
		return
	}

	PostRequest(BaseUrl + 'logindata', {Request: 'login data', Login: Login}, 20,
		function (Data) {
			PostRequest(
				BaseUrl + 'login',
				{
					Request: 'login',
					Login: Login,
					Hash: MakeLoginHash(MakeRegistrationHash(Login, Password, Data.Salt), Data.Timestamp),
					Timestamp: Data.Timestamp
				},
				20,
				function () {
					location.reload()
				}
			)
		}
	)
}

//---------------------------------------------------------------------------
function PostRequest(Url, Data, Timeout, SuccessHandler, ErrorHandler, Context, Dialog) {
	if (Dialog == undefined) Dialog = 'json-wait'
	if (Dialog) {
		Show(Dialog)
	}

	return Ajax.Post(Url, Data, Timeout,
		function (Text) {
			if (Dialog) Show(Dialog, false)
			try {
				var Data = JSON.parse(Text)
			} catch (e) {
				if (ErrorHandler) {
					ErrorHandler.call(Context, 200, 'некорректный JSON', Text)
				} else {
					alert('некорректный JSON')
				}
				return
			}

			if (!Data.Response || Data.Response == 'nack' || Data.Response == 'error') {
				if (ErrorHandler) {
					ErrorHandler.call(Context, 200, 'ошибка', Data)
				} else {
					alert('Ошибка: ' + Data.Error)
				}
				return
			}

			if (SuccessHandler) {
				SuccessHandler.call(Context, Data)
			}
		},

		function (Status, Message, Text) {
			if (Dialog) Show(Dialog, false)
			if (ErrorHandler) {
				ErrorHandler.call(Context, Status, Message, Text)
			} else {
				if (Status) {
					alert('Ошибка ' + Status + ': ' + Message)
				}
			}
		},

		Context
	)
}
