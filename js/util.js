window.onerror = function (Message, File, Line) {
	File = File.substring(File.indexOf(BaseUrl) + BaseUrl.length)
	alert('Ошибка: "' + Message + '" в ' + File + ' строка ' + Line)
}

//---------------------------------------------------------------------------
String.prototype.repeat = function(Count) {
	Count = Math.floor(Count)
	var Str = this
	if (!Count || Count < 0) return ''

	var Result = '';
	if (Count & 1) Result = Str
	Count >>= 1
	while(Count) {
		Str += Str
		if (Count & 1) Result += Str
		Count >>= 1
	}
	return Result
}

//---------------------------------------------------------------------------
String.prototype.splice = function(First, Last, Replace) {
	return this.slice(0, First) + Replace + this.slice(Last)
}

//---------------------------------------------------------------------------
String.prototype.chunk = function(Size) {
	if (Size == undefined) Size = 1
	if (Size <= 0) return

	var Result = new Array(Math.floor((this.length + Size - 1) / Size))
	var Len = Result.length
	if (!Len) return Result

	var Pos = 0
	for(var i = 0; i < Len; i++) {
		Result[i] = this.substr(Pos, Size)
		Pos += Size
	}
	return Result
}

//---------------------------------------------------------------------------
String.prototype.encode = function() {
	return this.replace('&', '&amp;').replace('<', '&lt;').replace('>', '&gt;').
		replace('"', '&quot;')
}

//---------------------------------------------------------------------------
String.prototype.subst = function(Params) {
	return this.replace(/\{\{([A-Za-z_0-9]+)\}\}/g, function(Match, Name) {
		if (Params[Name] == undefined) return Match
		else return Params[Name]
	})
}

//---------------------------------------------------------------------------
String.prototype.trim = function () {
	return this.replace(/^\s+|\s+$/g, '')
}

//---------------------------------------------------------------------------
function ArrayFill(Len, Value, DoClone) {
	var Arr = new Array(Len)
	if (DoClone == undefined) DoClone = true
	if (Len) {
		if(DoClone && Value instanceof Array) {
			for(var i = 0; i < Len; i++) Arr[i] = Clone(Value)
		} else {
			for(var i = 0; i < Len; i++) Arr[i] = Value
		}
	}
	return Arr
}

//---------------------------------------------------------------------------
function Clone(Obj) {
	if (!(Obj instanceof Object)) return Obj

	if (Obj.clone) return Obj.clone()

	if (typeof Obj == 'function') return Obj

	if (Obj instanceof Array) {
		var Result = []
		var Len = Obj.length
		for(var i = 0; i < Len; i++) {
			Result[i] = Clone(Obj[i])
		}
	} else {
		//var c = Obj.constructor
		var Result = {}
		Result.__proto__ = Obj.prototype
		for(var i in Obj) {
			if (Obj.hasOwnProperty(i) && i !== 'prototype') {
				Result[i] = Clone(Obj[i])
			}
		}
	}
	return Result
}

//---------------------------------------------------------------------------
function ArrayChunk(Arr, Size) {
	if (Size <= 0) return

	var Len = Arr.length
	var Result = []
	for(var i = 0; i < Len; i += Size) {
		Result.push(Arr.slice(i, i + 7))
	}
	return Result
}

//---------------------------------------------------------------------------
function ArrayShuffle(Arr) {
	if (!Arr.length || Arr.length == 1) return Arr

	for(var Len = Arr.length; Len > 1; Len--) {
		var i = Math.floor(Math.random() * Len)
		var v = Arr[i]
		Arr[i] = Arr[Len - 1]
		Arr[Len - 1] = v
	}
}

//---------------------------------------------------------------------------
function ArrayRandom(Arr) {
	if (!Arr.length) return
	if (Arr.length == 1) return Arr[0]
	return Arr[Math.floor(Math.random() * Arr.length)]
}

//---------------------------------------------------------------------------
function ArrayRange(First, Last) {
	var Step = (Last >= First ? 1 : -1)
	var Len = Math.abs(Last - First) + 1
	var Result = new Array(Len)
	for(var i = 0; i < Len; i++) {
		Result[i] = First
		First += Step
	}
	return Result
}

//---------------------------------------------------------------------------
function NewId(Name, GetLast) {
	if (!Name) Name = '_'
	if (!this[Name]) this[Name] = 0
	if (!GetLast) this[Name]++
	return this[Name]
}

//---------------------------------------------------------------------------
function Extend(Child, Parent) {
	var f = function() {}
	f.prototype = Parent
	Child.prototype = new f()
	Child.prototype.constructor = Child
	Child.prototype.Parent = Child.prototype
}

//---------------------------------------------------------------------------
function GetDataset(Dom) {
	var Dataset = Dom.dataset
	if (Dataset == undefined) {
		Dataset = {}
		var Attr = Dom.attributes
		var Len = Attr.length
		for(var i = 0; i < Len; i++) {
			var Node = Attr.item(i)
			if (Node.nodeName.substr(0, 5) == 'data-') {
				Dataset[Node.nodeName.substr(5)] = Node.nodeValue
			}
		}
	}
	return Dataset
}

//---------------------------------------------------------------------------
function GetImage(Id) {
	var Result = new Image()
	Result.src = document.getElementById(Id).src
	return Result
}

//---------------------------------------------------------------------------
function ABox(x, y, w, h) {
	return {x: x, y: y, w: w, h: h}
}

//---------------------------------------------------------------------------
function Style(Dom, Name, Value) {
	if (typeof Dom == 'string') Dom = document.getElementById(Dom)
	if (!Dom) return false

	var Values = Dom.getAttribute('style')
	if (Values) Values = Values.split(';')
	else Values = []

	for (var i in Values) {
		var Pair = Values[i].split(':', 2)
		if (Name == Pair[0].trim()) {
			var OldValue = String(Pair[1]).trim()
			if (Value) {
				Values[i] = Name + ':' + Value
				Dom.setAttribute('style', Values.join(';'))
			}
			return OldValue
		}
	}

	if (Value) {
		if (Values.length) {
			Values.push(Name + ':' + Value)
			Values = Values.join(';')
		} else {
			Values = Name + ':' + Value
		}
		Dom.setAttribute('style', Values)
	}

	return null
}

//---------------------------------------------------------------------------
function Show(Dom, Show) {
	if (Show == undefined) Show = true
	return Style(Dom, 'display', (Show ? 'block' : 'none'))
}

//---------------------------------------------------------------------------
var Ajax = {
	LastRequest: null,

	Get: function (Url, Data, Timeout, SuccessHandler, ErrorHandler, Context) {
		return this.Send('GET', Url, Data, Timeout, SuccessHandler, ErrorHandler, Context)
	},

	Post: function (Url, Data, Timeout, SuccessHandler, ErrorHandler, Context) {
		return this.Send('POST', Url, Data, Timeout, SuccessHandler, ErrorHandler, Context)
	},

	_Cancel: function () {
		if (this.Xhr) this.Xhr.abort()
		if (this.Timer) {
			clearTimeout(this.Timer)
			this.Timer = null
			this.Pending = false
		}
	},

	EncodeData: function (Data, Name) {
		if (!Name) Name = ''
		if (Data == undefined) Data = ''
		var t = typeof Data
		if (t == 'boolean') return Name + '=' + Number(Data)

		if (t == 'number' || t == 'string') return Name + '=' + encodeURIComponent(Data)

		var Result = []
		for (var Key in Data) {
			Result.push(this.EncodeData(Data[Key], (Name ? Name + '[' + Key + ']' : Key)))
		}

		return Result.join('&')
	},

	Send: function (Method, Url, Data, Timeout, SuccessHandler, ErrorHandler, Context) {
		if (Data) Data = this.EncodeData(Data)
		var Xhr = new XMLHttpRequest()

		if (Timeout) {
			var Timer = setTimeout(function () {
				clearTimeout(Timer)
				Timer = null
				Xhr.abort()
			}, Timeout * 1000)
		}

		var Request = {Xhr: Xhr, Timer: Timer, Pending: true, Cancel: this._Cancel}

		Xhr.onreadystatechange = function() {
//			console.log(this.readyState)
			if (this.readyState != 4) return

			if (Timer) {
				clearTimeout(Timer)
				Timer = null
			}

			if (this.status == 200) {
				if (SuccessHandler) {
					SuccessHandler.call(Context, Xhr.responseText)
				}
			} else {
				if (ErrorHandler) {
					var Status = Xhr.status
					var StatusText = Xhr.statusText
					if (!Status && Request.Pending) {
						Status = -1
						StatusText = 'сервер не отвечает'
					}
					ErrorHandler.call(Context, Status, StatusText, Xhr.responseText)
				}
			}
		}

		if (Method != 'POST') {
			if (Url.indexOf('?')) {
				Url += '&' + Data
			} else {
				Url += '?' + Data
			}
		}

		Xhr.open(Method, Url)
		if (Method == 'POST') {
			Xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded; charset=utf-8')
			Xhr.send(Data)
		} else {
			Xhr.send()
		}

		this.LastRequest = Request
		return this.LastRequest
	},

	Cancel: function () {
		if (!this.LastRequest) return false

		this.LastRequest.Cancel()
		this.LastRequest = null
		return true
	}
}
