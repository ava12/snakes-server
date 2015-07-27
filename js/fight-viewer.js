function AFightViewer(Fight) {
	this.TabTitle = 'бой'
	this.TabSprite = Sprites.Get('Fight')
	if (Fight instanceof AFight) {
		if (Fight.FightId || Fight.FightSlot != undefined) this.Fight = Fight
		else return new AFightPlanner(Fight)
	} else {
		if (typeof Fight == 'number') this.Fight = new AFight({FightId: Fight})
		else return new AFightPlanner()
	}

	this.TurnCount = 0
	this.Popup = null
	this.DebugSelectWidget = null

//---------------------------------------------------------------------------
	this.Items = {
		Field: {x: 12, y: 53, w: 400, h: 400},
		Snakes: {
			List: {x: 428, y: 53, w: 200, h: 30},
			Length: {x: 430, y: 56, w: 30, h: 24},
			Skin: {x: 466, y: 60, w: 48, h: 16},
			Name: {x: 520, y: 56, w: 116, h: 24}
		},
		Map: {x: 428, y: 182, w: 112, h: 112},
		MapStepLabel: {x: 548, y: 206, w: 60, h: 24, Label: 'Шаг:'},
		MapStepText: {x: 608, y: 206, w: 20, h: 24},
		MapIndexLabel: {x: 548, y: 238, w: 60, h: 24, Label: 'Карта:'},
		MapIndexText: {x: 608, y: 238, w: 20, h: 24},
		MapReasonText: {x: 548, y: 260, w: 80, h: 24},
	}

	this.TabControls = {Items: {
		SnakeButtons: {x: 428, w: 200, h: 30, Data: {cls: 'snake'}, Items: [
			{y: 53, id: 0, Title: ''},
			{y: 83, id: 1, Title: ''},
			{y: 113, id: 2, Title: ''},
			{y: 143, id: 3, Title: ''},
		]},
		MapSnake: {x: 548, y: 182, w: 48, h: 16, Title: 'выбранная змея',
			Data: {cls: 'current-snake'}},
		RunButton: {x: 612, y: 182, w: 16, h: 16, Title: 'пуск/стоп',
			Data: {cls: 'run'}, Sprite: '16.Labels.Run'},
		TurnRuler: {x: 428, y: 302, w: 200, h: 16, Title: 'Текущий ход',
			Data: {cls: 'ruler'}},
		TurnCounter: {x: 478, y: 326, w: 100, h: 24, Title: 'Текущий ход',
			Data: {cls: 'turn'}},
		TurnButtons: {y: 330, w: 16, h: 16, Data: {cls: 'rewind'}, Items: [
			{x: 430, Title: '10 шагов назад', id: '-10', Sprite: '16.Labels.First'},
			{x: 454, Title: 'шаг назад', id: '-1', Sprite: '16.Labels.Back'},
			{x: 586, Title: 'шаг вперед', id: '1', Sprite: '16.Labels.Forth'},
			{x: 610, Title: '10 шагов вперед', id: '10', Sprite: '16.Labels.Last'},
		]},
		SaveButton: {x: 428, y: 370, w: 100, h: 24, Data: {cls: 'save'},
			Labels: ['Сохранить', 'Сохранить'],
			BackColors: [CanvasColors.Create, CanvasColors.Modify]},
		RefreshButton: {x: 538, y: 370, w: 90, h: 24, Data: {cls: 'refresh'},
			Label: 'Обновить', Title: 'перезагрузить бой с сервера', BackColor: '#9cf'},
	}}

	this.ReasonSprites = ['Debug.NoDebug', 'Debug.NoMove', 'Debug.SingleMove', 'Debug.NoMap']
	this.ReasonTexts = [null, 'тупик', 'один ход', 'нет карты']

	this.KeyFrameRate = 20
	this.SnakeColors = ['#f99', '#ee6', '#6e6', '#9df']
	this.StepDelay = 50

	this.Turn = 0
	this.Step = 0
	this.StepOrder = [0, 1, 2, 3]
	this.StepDirs = [0, 0, 0, 0]
	this.dx = [0, 1, 0, -1]
	this.dy = [-1, 0, 1, 0]
	this.DebuggedSnakeIndex = null // 0 - 3 | null
	this.IsEating = false
	this.IsRunning = false
	this.TargetTurn = null
	this.Interval = null

	// состояние поля (расположение змей) на начало текущего хода
	// [([{x:, y:, t: тип_спрайта(h|b|l|r|t), d: направление(0 - 3)}*]|null)*4]
	this.TurnField = [null, null, null, null]

	// состояние поля на текущем шаге
	this.Field = [null, null, null, null]

	// поле на начало хода i * KeyFrameRate
	this.KeyFrames = []

	this.SnakeSkins = [null, null, null, null]


//---------------------------------------------------------------------------
	this.RenderTurnRuler = function() {
		var Value = this.Turn
		var Limit = this.TurnCount
		var RulerBox = this.TabControls.Items.TurnRuler
		var CounterBox = this.TabControls.Items.TurnCounter
		Canvas.FillRect(ABox(RulerBox.x - 4, RulerBox.y, RulerBox.w + 8, RulerBox.h), '#ffffff')
		Canvas.FillRect(RulerBox, '#dddddd')
		var MarkX = RulerBox.x + Value * RulerBox.w / Limit
		Canvas.Rect(ABox(MarkX - 4, RulerBox.y, 8, RulerBox.h), '#99ff99', '#000000')
		Value = (Value + 1).toString() + '/' + Limit
		Canvas.RenderTextBox(Value, CounterBox, '#000000', '#ffffff', '#000000',
			'center')
	}

//---------------------------------------------------------------------------
	this.ExtractTurnInfo = function() {
		var Info = this.Fight.Turns[this.Turn]
		if (Info == undefined) {
			if (this.Turn == this.TurnCount) Info = 0
			else return
		}

		for(var i = 0; i < 4; i++) {
			this.StepDirs[i] = (Info >> (6 + (i << 1))) & 3
		}
		var LastIndex = 6 // 0 + 1 + 2 + 3
		for(i = 0; i < 3; i++) {
			this.StepOrder[i] = (Info >> (i << 1)) & 3
			LastIndex -= this.StepOrder[i]
		}
		this.StepOrder[3] = LastIndex
	}

//---------------------------------------------------------------------------
	this.RenderCell = function(Sprite, x, y, SnakeIndex) {
		var Box = this.Items.Field
		x = Box.x + x * 16
		y = Box.y + y * 16
		if (SnakeIndex == undefined) {
			Canvas.RenderSprite(TileImage, x, y)
		} else {
			Canvas.FillRect(ABox(x, y, 16, 16), this.SnakeColors[SnakeIndex])
		}

		if (!Sprite) return

		if (Sprite.t) {
			Sprite = this.SnakeSkins[SnakeIndex].Get(Sprite.t, Sprite.d)
		}
		Canvas.RenderSprite(Sprite, x, y)
	}

//---------------------------------------------------------------------------
	this.MoveHead = function(SnakeIndex, Dir, Render) {
		var SnakeBody = this.Field[SnakeIndex]
		var Head = SnakeBody[0]
		Head.t = ['l', 'b', 'r'][(Dir - Head.d + 1) & 3]
		Head.d = Dir
		var x = Head.x + this.dx[Dir]
		var y = Head.y + this.dy[Dir]
		SnakeBody.unshift({x: x, y: y, t: 'h', d: Dir})
		if (Render) {
			this.RenderCell(Head, Head.x, Head.y, SnakeIndex)
			this.RenderCell(SnakeBody[0], x, y, SnakeIndex)
		}
	}

//---------------------------------------------------------------------------
	this.CutTail = function(SnakeIndex, Eaten, Render) {
		var SnakeBody = this.Field[SnakeIndex]
		var Tail = SnakeBody.pop()
		if (SnakeBody.length) {
			SnakeBody[SnakeBody.length - 1].t = 't'
		}

		if (Render) {
			if (Eaten) {
				this.RenderCell(Sprites.Get('Attack'), Tail.x, Tail.y)
			} else {
				var Head = SnakeBody[0]
				if (Tail.x != Head.x || Tail.y != Head.y) {
					this.RenderCell(null, Tail.x, Tail.y)
				}
			}
			if (SnakeBody.length) {
				Tail = SnakeBody[SnakeBody.length - 1]
				this.RenderCell(Tail, Tail.x, Tail.y, SnakeIndex)
			}
		}
	}

//---------------------------------------------------------------------------
	this.MakeStep = function(Render) {
		var SnakeIndex = this.StepOrder[this.Step]
		var SnakeBody = this.Field[SnakeIndex]
		if (!SnakeBody || SnakeBody.length < 2) {
			this.Step++
			return false
		}

		var Dir = this.StepDirs[SnakeIndex]
		if (!Dir) {
			this.Step++
			return false
		}

		Dir = (SnakeBody[0].d + Dir + 2) & 3
		if (this.IsEating) {
			this.IsEating = false
			this.MoveHead(SnakeIndex, Dir, Render)
			this.Step++
			return true
		}

		var x = SnakeBody[0].x + this.dx[Dir]
		var y = SnakeBody[0].y + this.dy[Dir]
		for(var i = 0; i < 4; i++) {
			if (i == SnakeIndex || !this.Field[i] || !this.Field[i].length) continue

			var Tail = this.Field[i][this.Field[i].length - 1]
			if (Tail.x == x && Tail.y == y) {
				this.CutTail(i, true, Render)
				if (Render) {
					this.IsEating = true
				} else {
					this.MoveHead(SnakeIndex, Dir, false)
					this.Step++
				}
				return true
			}
		}

		this.MoveHead(SnakeIndex, Dir, Render)
		this.CutTail(SnakeIndex, false, Render)
		this.Step++
		return true
	}

//---------------------------------------------------------------------------
	this.SelectStep = function() {
		this.Step = 0
		this.IsEating = false
		this.Field = Clone(this.TurnField)
		if (this.DebuggedSnakeIndex != undefined && this.Turn < this.TurnCount) {
			while(this.StepOrder[this.Step] != this.DebuggedSnakeIndex) {
				this.MakeStep()
			}
		}
	}

//---------------------------------------------------------------------------
	this.SelectTurn = function(Index) {
		if (Index > this.TurnCount) Index = this.TurnCount
		if (Index < 0) Index = 0

		var KeyIndex = Math.floor(Index / this.KeyFrameRate)
		if (KeyIndex >= this.KeyFrames.length) KeyIndex = this.KeyFrames.length - 1
		this.Field = Clone(this.KeyFrames[KeyIndex])
		this.Turn = KeyIndex * this.KeyFrameRate

		for(; this.Turn < Index; this.Turn++) {
			if (!(this.Turn % this.KeyFrameRate)) {
				KeyIndex = this.Turn / this.KeyFrameRate
				this.KeyFrames[KeyIndex] = Clone(this.Field)
			}
			this.ExtractTurnInfo()
			this.Step = 0
			while(this.Step < 4) {
				this.MakeStep()
			}
		}

		this.TurnField = this.Field
		this.ExtractTurnInfo()
		this.SelectStep()

		this.RenderTurnRuler()
		this.RenderField()
		this.RenderDebugInfo(true)
	}

//---------------------------------------------------------------------------
	this.RenderStepInfo = function() {
		Canvas.RenderTextBox(this.Step + 1, this.Items.MapStepText, '#000', '#fff')
	}

//---------------------------------------------------------------------------
	this.RenderDebugInfo = function(Force) {
	var Box = this.Items.Map
	var Stats = this.Fight.SnakeStats[this.DebuggedSnakeIndex]
	if (this.DebuggedSnakeIndex == undefined || Stats.DebugData.length <= this.Turn) {
		if (!Force) return

		Canvas.RenderSprite(Sprites.Get('Debug.NoDebug'), Box.x, Box.y)
		Canvas.RenderTextBox('', this.Items.MapIndexText, '#000', '#fff')
		Canvas.RenderTextBox('', this.Items.MapReasonText, '#000', '#fff')
		return
	}

	var Snake = this.Fight.Snakes[this.DebuggedSnakeIndex]
	var t = Stats.DebugData.charCodeAt(this.Turn) - 32
	var Index = t >> 3, RotRef = t & 7, Reason = ''
	if (Index) {
		Snake.Maps[Index - 1].Render(Box.x, Box.y, RotRef)
	} else {
		Canvas.RenderSprite(Sprites.Get(this.ReasonSprites[RotRef]), Box.x, Box.y)
		Index = ''
		Reason = this.ReasonTexts[RotRef]
	}
	Canvas.RenderTextBox(Index, this.Items.MapIndexText, '#000', '#fff')
	Canvas.RenderTextBox(Reason, this.Items.MapReasonText, '#000', '#fff')
}

//---------------------------------------------------------------------------
	this.RenderSnakeLength = function(Index) {
		if (!this.Field[Index]) return

		var Box = Clone(this.Items.Snakes.Length)
		Box.y += this.Items.Snakes.List.h * Index
		Canvas.RenderTextBox(this.Field[Index].length, Box, '#000', this.SnakeColors[Index], null, 'right')
	}

//---------------------------------------------------------------------------
	this.RenderDebuggedSnake = function() {
		var Box = this.TabControls.Items.MapSnake
		if (this.DebuggedSnakeIndex == undefined) {
			Canvas.RenderTextButton('---', Box, '#eee')
		} else {
			Canvas.FillRect(Box, this.SnakeColors[this.DebuggedSnakeIndex])
			Canvas.RenderSprite(SnakeSkins.Get(this.Fight.Snakes[this.DebuggedSnakeIndex].SkinId), Box.x, Box.y)
		}
	}

//---------------------------------------------------------------------------
	this.RenderSaveButton = function() {
		var Button = this.TabControls.Items.SaveButton
		var IsSaved = (this.Fight.SlotIndex != undefined)
		Canvas.RenderTextButton(Button.Labels[IsSaved ? 1 : 0], Button,
			Button.BackColors[IsSaved ? 1 : 0])
	}

//---------------------------------------------------------------------------
	this.RenderStatic = function() {
		var Items = this.Items
		for(var i in Items) {
			if (Items[i].Label) {
				Canvas.RenderTextBox(Items[i].Label, Items[i])
			}
		}

		Items = Items.Snakes
		var Box = Items.List
		var Snakes = this.Fight.Snakes

		Canvas.SaveState()
		for(i = 0; i < 4; i++) {
			Canvas.FillRect(Box, this.SnakeColors[i])
			if (Snakes[i]) {
				Canvas.RenderSprite(SnakeSkins.Get(Snakes[i].SkinId), Items.Skin.x, Items.Skin.y)
				Canvas.RenderText(Snakes[i].SnakeName, Items.Name)
			}
			Canvas.Translate(0, Box.h)
		}
		Canvas.RestoreState()

		Items = this.TabControls.Items
		Canvas.RenderSprite(Sprites.Get(Items.RunButton.Sprite), Items.RunButton.x, Items.RunButton.y)

		this.RenderSaveButton()

		if (Items.ExportButton) {
			Canvas.RenderTextButton(Items.ExportButton.Label, Items.ExportButton,
				Items.ExportButton.BackColor)
		}

		Items = Items.TurnButtons
		for(i in Items.Items) {
			Canvas.RenderSprite(Sprites.Get(Items.Items[i].Sprite), Items.Items[i].x, Items.y)
		}
	}

//---------------------------------------------------------------------------
	this.RenderField = function() {
		Canvas.RenderTiles(TileImage, this.Items.Field)
		var Box = this.Items.Field
		for(var i in this.Field) {
			if (!this.Field[i]) continue

			var Snake = this.Field[i]
			var Color = this.SnakeColors[i]
			var Skin = this.SnakeSkins[i]

			for(var j in Snake) {
				var x = Box.x + (Snake[j].x * 16)
				var y = Box.y + (Snake[j].y * 16)
				Canvas.FillRect(ABox(x, y, 16, 16), Color)
				Canvas.RenderSprite(Skin.Get(Snake[j].t, Snake[j].d), x, y)
			}

			this.RenderSnakeLength(i)
		}

		this.RenderStepInfo()

		if (this.IsEating) {
			var Dir = this.StepDirs[this.Step]
			var Head = this.Field[this.StepOrder[this.Step]][0]
			x = Box.x + (Head.x + this.dx[Dir]) * 16
			y = Box.y + (Head.y + this.dy[Dir]) * 16
			Canvas.RenderSprite(Sprites.Get('Attack'), x, y)
		}
	}

//---------------------------------------------------------------------------
	this.RenderBody = function() {
		this.RenderStatic()
		this.RenderDebuggedSnake()
		this.RenderTurnRuler()
		this.RenderField()
		this.RenderDebugInfo(true)

		if (this.Popup) this.Popup.Render()
	}

//---------------------------------------------------------------------------
	this.Stop = function() {
		clearInterval(this.Interval)
		this.Interval = null
		this.IsRunning = false
		this.TargetTurn = null
	}

//---------------------------------------------------------------------------
	this.OnShow = function() {
		if (this.IsRunning) {
			this.Interval = setInterval(this.OnTick, this.StepDelay, this)
		}
	}

//---------------------------------------------------------------------------
	this.OnHide = function() {
		if (this.IsRunning) {
			clearInterval(this.Interval)
			this.Interval = null
		}
	}

//---------------------------------------------------------------------------
	this.NextTurn = function() {
		this.Step = 0
		this.Turn++
		this.TurnField = Clone(this.Field)
		this.ExtractTurnInfo()
		this.RenderDebugInfo()
		this.RenderTurnRuler()
		return (this.Turn < this.TurnCount)
	}

//---------------------------------------------------------------------------
	this.OnTick = function(t) {
		if (!t.IsRunning || t.Turn >= t.TurnCount) {
			t.Stop()
			return
		}

		do {
			if (t.Step > 3) {
				if (!t.NextTurn()) break
			}

			if (
				t.TargetTurn != undefined && t.Turn >= t.TargetTurn && !t.IsEating &&
				((t.DebuggedSnakeIndex == undefined && !t.Step) ||
				 (t.StepOrder[t.Step] == t.DebuggedSnakeIndex))
			) {
				t.Stop()
				return
			}
		} while(!t.MakeStep(true))

		if (t.Step > 3) t.NextTurn()

		for(var i = 0; i < 4; i++) t.RenderSnakeLength(i)
		t.RenderDebugInfo(true)
		t.RenderStepInfo()
	}

//---------------------------------------------------------------------------
	this.SelectSnake = function(Index) {
		if (Index != undefined && !this.Fight.SnakeStats[Index].DebugData) return

		this.DebuggedSnakeIndex = Index
		this.SelectStep()
		this.Show()
/*
		this.RenderField()
		this.RenderDebugInfo(true)
		this.RenderDebuggedSnake()
*/
	}

//---------------------------------------------------------------------------
	this.SaveFight = function (Index, NewName) {
		NewName = NewName.trim()
		if (!NewName) return

		var Request = {
			Request: 'slot save',
			SlotIndex: Index,
			FightId: this.Fight.FightId,
			SlotName: NewName
		}

		PostRequest(null, Request, 10, function () {
			if (!this.Fight.SlotIndex) {
				this.Fight.SlotIndex = Index
				this.Show()
			}
		}, null, this)
	}

//---------------------------------------------------------------------------
	this.OnClick = function(x, y, Dataset) {
		var Class = Dataset.cls
		var Id = Dataset.id
		var i

		switch(Class) {
			case 'turn':
				i = prompt('Номер хода:', this.Turn + 1)
				if (i) this.SelectTurn(parseInt(i) - 1)
			break

			case 'ruler':
				i = Math.floor(x * (this.TurnCount + 1) / this.TabControls.Items.TurnRuler.w)
				this.SelectTurn(i)
			break

			case 'rewind': {
				Id = parseInt(Id)
				if (Id != 1) {
					if (this.IsRunning) this.Stop()
					this.SelectTurn(this.Turn + Id)
				} else {
					if (!this.IsRunning) {
						this.Interval = setInterval(this.OnTick, this.StepDelay, this)
						this.IsRunning = true
					}
					this.TargetTurn = this.Turn + 1
				}
			break }

			case 'snake': {
				Id = parseInt(Id)
				if (Id == this.DebuggedSnakeIndex) Id = null
				this.SelectSnake(Id)
			break }

			case 'run': {
				if (!this.IsRunning) {
					this.Interval = setInterval(this.OnTick, this.StepDelay, this)
					this.IsRunning = true
					this.TargetTurn = null
				} else {
					this.TargetTurn = this.Turn
				}
			break }

			case 'current-snake':
				this.Popup = this.DebugSelectWidget
				this.Show()
			break

			case 'debug-none':
				Id = null
				// nobr
			case 'debug-snake':
				this.Popup = null
				this.SelectSnake(Id)
			break

			case 'save':
				this.Popup = new ASlotSelectWidget()
				this.Show()
			break

			case 'list-cancel':
				this.Popup = null
				this.Show()
			break

			case 'slot-name':
				var Item = this.Popup.List.Items[Id]
				this.Popup = new AInputWidget({Title: 'Название для сохраненного боя', WidgetId: Id})
				this.Show()
				if (Item && Item.SlotName) this.Popup.SetValue(Item.SlotName)
			break

			case 'widget-ack':
				this.SaveFight(Dataset.widget, this.Popup.GetValue())
				//nobr
			case 'widget-cancel':
				this.Popup = null
				this.Show()
			break

			default:
				if (this.Popup) this.Popup.OnClick(x, y, Dataset)
		}
	}

//---------------------------------------------------------------------------
	this.Prepare = function () {
		this.TabTitle += ' (' + FormatDate(this.Fight.FightTime) + ')'
		this.TurnCount = this.Fight.Turns.length

		var Buttons = this.TabControls.Items.SnakeButtons.Items
		for(var i = 0; i < 4; i++) {
			if (!this.Fight.Snakes[i]) delete Buttons[i]
			else Buttons[i].Title = this.Fight.Snakes[i].SnakeName
		}

		var StartX = [12, 9, 12, 15], StartY = [15, 12, 9, 12]
		var DeltaX = [0, -1, 0, 1], DeltaY = [1, 0, -1, 0]
		var Snakes = this.Fight.Snakes
		for(var i = 0; i < 4; i++) {
			if (!Snakes[i]) continue

			var x = StartX[i], y = StartY[i]
			var dx = DeltaX[i], dy = DeltaY[i]
			var Cells = []
			for(var j = 0; j < 10; j++) {
				Cells.push({x: x, y: y, t: 'b', d: i})
				x += dx
				y += dy
			}
			Cells[0].t = 'h'
			Cells[9].t = 't'
			this.Field[i] = Cells
			this.SnakeSkins[i] = new ASkin(Snakes[i].SkinId)
		}
		this.TurnField = Clone(this.Field)
		this.KeyFrames[0] = Clone(this.Field)
		this.ExtractTurnInfo()

		var Box = this.TabControls.Items.MapSnake
		this.DebugSelectWidget = new ADebugSelectWidget({
			x: Box.x - 3, y: Box.y - 3, Snakes: Snakes
		})
	}

//---------------------------------------------------------------------------
	this.ProcessResponse = function(Response) {
		if (Response.Response == 'fight info') {
			this.Fight = new AFight(Response)
			this.Prepare()
			this.Show()
			return
		}

		var Request = {Request: 'fight info', FightId: this.Fight.FightId}
		PostRequest(null, Request, 20, this.ProcessResponse, null, this)
	}

//---------------------------------------------------------------------------
	this.RenderControls = function () {
		Canvas.RenderHtml('controls', Canvas.MakeControlHtml(this.Popup ? this.Popup.WidgetControls : this.TabControls))
	}

//---------------------------------------------------------------------------
	;(function() {
		if (this.Fight.FightTime) {
			this.Prepare()
			return
		}

		var Request = {Request: 'fight info', FightId: this.Fight.FightId}
		PostRequest(null, Request, 10, this.ProcessResponse, null, this)
	}).call(this)

//---------------------------------------------------------------------------
}
Extend(AFightViewer, BPageTab)
