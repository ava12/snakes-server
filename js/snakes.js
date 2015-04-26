
//---------------------------------------------------------------------------
//---------------------------------------------------------------------------
function ASnakeMap(Fields) {
	this.Description = ''
	this.HeadX = 3
	this.HeadY = 3

	if (Fields) {
		for(var i in Fields) {
			if (this[i] != undefined) this[i] = Fields[i]
		}
	}

	this.Lines = '--'.repeat(49)
	if (Fields && Fields.Lines) {
		for(var i in Fields.Lines) {
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
		for(var y in Cells) {
			for(var x in Cells[y]) {
				var Cell = Cells[y][x].chunk()
				if (Cell[1] == '-') {
					this.RenderCell(Size + '.Any', x, y, Size)
				} else {
					this.RenderCell(Size + '.Group.' + Cell[1], x, y, Size)
					var t = Cell[0].toUpperCase()
					this.RenderCell(Size + '.' + t, x, y, Size)
					if (t != Cell[0]) this.RenderCell(Size + '.Not', x, y, Size)
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
			Description: this.Description, HeadX: this.HeadX, HeadY: this.HeadY,
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
	this.SnakeId = false
	this.PlayerId = false
	this.PlayerName = false
	this.SnakeName = ''
	this.SnakeType =  'N'
	this.SkinId = 1
	this.ProgramDescription = ''
	this.Templates = ['S', 'S', 'S', 'S']

	if(Fields) {
		if (!(Fields instanceof Object)) Fields = {SnakeId: Fields}
		for(var i in Fields) {
			if (this[i] != undefined) this[i] = Fields[i]
		}
	}

	if (!Fields || !Fields.Maps) this.Maps = [new ASnakeMap()]
	else {
		this.Maps = []
		for(var i in Fields.Maps) this.Maps.push(new ASnakeMap(Fields.Maps[i]))
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
	this.FightId = false
	this.FightType = 'train'
	this.FightTime = false
	this.FightResult = false
	this.TurnLimit = 1000
	this.Turns = []
	this.Snakes = [null, null, null, null]
	this.SnakeStats = [null, null, null, null]
	this.OtherPlayerIds = [null, null, null]

	this.SlotIndex = null

	if(Fields) {
		for(var i in Fields) {
			if (this[i] != undefined || i == 'SlotIndex') this[i] = Clone(Fields[i])
		}

		for(i in this.Snakes) {
			if (this.Snakes[i]) {
				if (!this.Snakes[i].Serialize) //this.Snakes[i] = Clone(this.Snakes[i])
				/*else*/ this.Snakes[i] = new ASnake(this.Snakes[i])
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
		for(var i in this.Snakes) {
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
		Id: null,
		Name: null,
		FightId: null
	},

	Tabs: {
		Unique: {}, // {key: TabId}
		// {id: TabId}
		Players: {},
		Snakes: {},
		Fights: {}
	},

//---------------------------------------------------------------------------
	Run: function () {
		PostRequest(null, {Request: 'whoami'}, 20, function (Data) {
			this.Player = {
				Id: Data.PlayerId,
				Name: Data.PlayerName,
				FightId: Data.FightId,
				Rating: Data.Rating
			}
			if (Data.SnakeId) this.Player.Fighter = {
				SnakeId: Data.SnakeId,
				SnakeName: Data.SnakeName,
				SkinId: Data.SkinId
			}
			TabSet.Init()
			if (this.Player.FightId) {
				TabSet.Add(new AFightViewer(new AFight({FightId: this.Player.FightId})))
			}
		}, null, this, 'game-wait')
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
