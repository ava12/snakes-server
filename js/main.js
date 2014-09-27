//---------------------------------------------------------------------------
function MakeRegistrationHash(Login, Password, Salt) {
	return Crypto.SHA1(Crypto.SHA1(Login + ':' + Password) + Salt)
}

//---------------------------------------------------------------------------
function MakeLoginHash(Login, Password, Salt, Timestamp) {
	return Crypto.SHA1(MakeRegistrationHash(Login, Password, Salt) + Timestamp)
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
	var Login = document.getElementById('login').value.toLowerCase()
	var Password = document.getElementById('password').value
	if (!Login || !Password) {
		return
	}

	var Salt = MakeSalt()
	var Timestamp = Math.floor((new Date()).getTime() / 1000)
	var Hash = MakeLoginHash(Login, Password, Salt, Timestamp)
}