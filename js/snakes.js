
//---------------------------------------------------------------------------
//---------------------------------------------------------------------------
function ASnakeMap(Fields) {
	this.Description = ''
	this.HeadX = 3
	this.HeadY = 3

	var i
	if (Fields) {
		for(i in Fields) {
			if (i in this) this[i] = Fields[i]
		}
	}

	this.Lines = '--'.repeat(49)
	if (Fields && Fields.Lines) {
		for(i in Fields.Lines) {
			var Line = Fields.Lines[i]
			var Pos = Line.Y * 14 + Line.X * 2
			this.Lines = this.Lines.splice(Pos, Pos + Line.Line.length, Line.Line)
		}
	}

//---------------------------------------------------------------------------
	this.RenderCell = function(Name, x, y, Size) {
		Canvas.RenderSprite(Sprites.Get(Name), x * Size, y * Size)
	}

//---------------------------------------------------------------------------
	this.Render = function(x, y, RotRef, Size) {
		if (!RotRef) RotRef = 0
		if (!Size) Size = 16

		Canvas.SaveState()
		Canvas.RotateReflect(ABox(x, y, Size * 7, Size * 7), RotRef)

		var Cells = ArrayChunk(this.Lines.chunk(2), 7)
		for(var cy in Cells) {
			for(var cx in Cells[cy]) {
				var Cell = Cells[cy][cx].chunk()
				if (Cell[1] == '-') {
					this.RenderCell(Size + '.Any', cx, cy, Size)
				} else {
					this.RenderCell(Size + '.Group.' + Cell[1], cx, cy, Size)
					var t = Cell[0].toUpperCase()
					this.RenderCell(Size + '.' + t, cx, cy, Size)
					if (t != Cell[0]) this.RenderCell(Size + '.Not', cx, cy, Size)
				}
			}
		}
		this.RenderCell(Size + '.OwnHead', this.HeadX, this.HeadY, Size)

		Canvas.RestoreState()
	}

//---------------------------------------------------------------------------
	this.clone = function() {
		var Fields = {Description: this.Description, HeadX: this.HeadX,
			HeadY: this.HeadY, Lines: [{X: 0, Y: 0, Line: this.Lines}]}
		return new ASnakeMap(Fields)
	}

//---------------------------------------------------------------------------
	this.Serialize = function() {
		var Result = {
			Description: this.Description, HeadX: this.HeadX, HeadY: this.HeadY
		}
		var Match = /^(-*)(.*?)-*$/.exec(this.Lines)
		if (!Match || !Match[2]) Result.Lines = [{X: 0, Y: 0, Line: '--'}]
		else {
			var Len = Match[1].length >> 1
			Result.Lines = [{X: Len % 7, Y: Math.floor(Len / 7), Line: Match[2]}]
		}
		return Result
	}

//---------------------------------------------------------------------------
	return this
}

//---------------------------------------------------------------------------
//---------------------------------------------------------------------------
function ASnake(Fields) {
	this.SnakeId = null
	this.PlayerId = null
	this.PlayerName = null
	this.SnakeName = ''
	this.SnakeType =  'N'
	this.SkinId = 1
	this.ProgramDescription = ''
	this.Templates = ['S', 'S', 'S', 'S']

	var i
	if(Fields) {
		if (!(Fields instanceof Object)) Fields = {SnakeId: Fields}
		for(i in Fields) {
			if (i in this) this[i] = Fields[i]
		}
	}

	if (!Fields || !Fields.Maps) this.Maps = [new ASnakeMap()]
	else {
		this.Maps = []
		for(i in Fields.Maps) this.Maps.push(new ASnakeMap(Fields.Maps[i]))
	}

//---------------------------------------------------------------------------
	this.Refresh = function (Handler, ErrorHandler, Context) {
		PostRequest(null, {Request: 'snake info', SnakeId: this.SnakeId}, 10, function (Fields) {
			for(var i in Fields) {
				if (this[i] != undefined) this[i] = Fields[i]
			}
			if (Handler) Handler.call(Context)
		}, function (Status, Message, Text) {
			if (ErrorHandler) Handler.call(Context, Status, Message, Text)
		}, this)
	}

//---------------------------------------------------------------------------
  this.Serialize = function() {
    var Names = ['SnakeId', 'PlayerId', 'SnakeName', 'SnakeType', 'SkinId',
			'ProgramDescription', 'Templates']
    var Result = {Maps: []}
		for(var i in Names) Result[Names[i]] = this[Names[i]]
		for(i in this.Maps) Result.Maps.push(this.Maps[i].Serialize())
		return Result
  }

//---------------------------------------------------------------------------
	return this
}

//---------------------------------------------------------------------------
//---------------------------------------------------------------------------
function AFight(Fields) {
	this.FightId = null
	this.SlotIndex = null
	this.SlotName = null
	this.FightType = 'train'
	this.FightTime = null
	this.FightResult = null
	this.TurnLimit = 1000
	this.Turns = []
	this.Snakes = [null, null, null, null]
	this.SnakeStats = [null, null, null, null]
	this.OtherPlayerIds = [null, null, null]

	this.SlotIndex = null

	if(Fields) {
		for(var i in Fields) {
			if (i in this) this[i] = Clone(Fields[i])
		}

		for(i in this.Snakes) {
			if (this.Snakes[i]) {
				if (!this.Snakes[i].Serialize) {
					var SnakeFields = this.Snakes[i]
					for (var j in this.SnakeStats[i]) SnakeFields[j] = this.SnakeStats[i][j]
					this.Snakes[i] = new ASnake(SnakeFields)
				}
			}
		}
	}

//---------------------------------------------------------------------------
	this.MakeChallenge = function (OtherPlayerId) {
		this.FightType = 'challenge'
		if (OtherPlayerId) {
			this.OtherPlayerIds[0] = OtherPlayerId
		}
	}

//---------------------------------------------------------------------------
	this.SetSnake = function (Snake, Index) {
		if (!Snake.Serialize) {
			Snake = new ASnake(Snake)
		}
		this.Snakes[Index] = Snake
	}

//---------------------------------------------------------------------------
	this.Serialize = function() {
		var Result = {}
		var Names = ['FightId', 'FightType', 'FightTime', 'FightResult',
			'TurnLimit', 'Turns', 'SnakeStats', 'SlotIndex']
		for(var i in Names) Result[Names[i]] = this[Names[i]]
		Result.Snakes = [null, null, null, null]
		for(i in this.Snakes) {
			if (this.Snakes[i]) Result.Snakes[i] = this.Snakes[i].Serialize()
		}
		return Result
	}

//---------------------------------------------------------------------------
	return this
}


//---------------------------------------------------------------------------
//---------------------------------------------------------------------------
var Game = {
	Player: {
		PlayerId: null,
		PlayerName: null,
		FightId: null,
		Rating: null,
		SnakeId: null,
		SnakeName: null,
		SkinId: null
	},

	Tabs: {
		Unique: {}, // {key: TabId}
		// {id: TabId}
		Players: {},
		Snakes: {},
		Fights: {}
	},

//---------------------------------------------------------------------------
	SetPlayer: function (Data) {
		for (var Name in this.Player) {
			this.Player[Name] = Data[Name]
		}
		if (Data.SnakeId) this.Player.Fighter = {
			SnakeId: Data.SnakeId,
			SnakeName: Data.SnakeName,
			SkinId: Data.SkinId
		}
		if (this.Player.FightId) {
			this.AddTab(new AFightViewer(new AFight({FightId: this.Player.FightId})))
		} else {
			TabSet.CurrentTab.Show()
		}
	},

//---------------------------------------------------------------------------
	Run: function () {
		TabSet.Init()
		SnakeSkins.Load()
		PostRequest(null, {Request: 'whoami'}, 10, this.SetPlayer, null, this)
	},

//---------------------------------------------------------------------------
	RegisterTab: function (List, Key, Tab) {
		this.Tabs[List][Key] = Tab
	},

//---------------------------------------------------------------------------
	UnregisterTab: function (List, Key) {
		delete this.Tabs[List][Key]
	},

//---------------------------------------------------------------------------
	FindTab: function (List, Key) {
		return this.Tabs[List][Key]
	},

//---------------------------------------------------------------------------
	AddTab: function (Tab) {
		if (Tab.TabList && Tab.TabKey) {
			var RegisteredTab = this.Tabs[Tab.TabList][Tab.TabKey]
			if (RegisteredTab) {
				TabSet.Select(RegisteredTab)
				return RegisteredTab
			}
		}

		TabSet.Add(Tab)
		return Tab
	}

//---------------------------------------------------------------------------
}


var CanvasColors = {
	Info: '#9cf',
	Create: '#9f9',
	Modify: '#ff6',
	Delete: '#f99',
	Button: '#eee',
	Items: ['#eef', '#efe']
}
