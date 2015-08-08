function AFightPlanner(Fight) {
	this.TabTitle = 'бой'
	this.TabSprite = Sprites.Get('Fight')
	this.TabList = 'Unique'
	this.TabKey = 'Fight'

	if (Fight instanceof AFight) {
		if (Fight.FightId) return new AFightViewer(Fight)

		this.Fight = Fight
	} else {
		this.Fight = new AFight()
		if (Fight instanceof ASnake) this.Fight.Snakes[0] = Fight
	}

	this.ShowWidget = false
	this.Widget = new ASnakeListWidget({y: 30, IsPopup: true})
	this.SnakeIndex = 0

	this.MaxTurnLimit = 1000

	this.SnakeListColors = ['#f99', '#ee6', '#6e6', '#9df']

	this.TabControls = {Items: {
		SnakeButtons: {Items: [
			{Items: {Change: {x: 20, y: 41, w: 500, h: 28, Data: {cls: 'change', id: 0}},
				Remove: {x: 530, y: 41, w: 100, h: 28, Data: {cls: 'remove', id: 0}}}},
			{Items: {Change: {x: 20, y: 71, w: 500, h: 28, Data: {cls: 'change', id: 1}},
				Remove: {x: 530, y: 71, w: 100, h: 28, Data: {cls: 'remove', id: 1}}}},
			{Items: {Change: {x: 20, y: 101, w: 500, h: 28, Data: {cls: 'change', id: 2}},
				Remove: {x: 530, y: 101, w: 100, h: 28, Data: {cls: 'remove', id: 2}}}},
			{Items: {Change: {x: 20, y: 131, w: 500, h: 28, Data: {cls: 'change', id: 3}},
				Remove: {x: 530, y: 131, w: 100, h: 28, Data: {cls: 'remove', id: 3}}}}
		]},
		TurnRuler: {x: 70, y: 180, w: 500, h: 16, Title: 'Лимит ходов',
			Data: {cls: 'ruler'}},
		TurnCounter: {x: 285, y: 209, w: 50, h: 22, Title: 'Лимит ходов',
			Data: {cls: 'limit'}},
		TurnButtons: {w: 16, h: 16, Data: {cls: 'turn'}, Items: [
			{x: 242, y: 212, Title: '-10', id: '-10', Sprite: '16.Labels.First'},
			{x: 262, y: 212, Title: '-1', id: '-1', Sprite: '16.Labels.Back'},
			{x: 342, y: 212, Title: '+1', id: '1', Sprite: '16.Labels.Forth'},
			{x: 362, y: 212, Title: '+10', id: '10', Sprite: '16.Labels.Last'}
		]},
		RunButton: {x: 275, y: 260, w: 70, h: 30, Label: 'В бой!',
			Data: {cls: 'run'}}
	}}
	this.ListBox = {x: 10, y: 40, w: 500, h: 30}
	this.ListItems = {
		Skin: {x: 20, y: 47, w: 48, h: 16},
		Name: {x: 80, y: 41, w: 415, h: 28},
		Remove: {x: 520, y: 42, w: 100, h: 26, Color: '#ffcccc', Label: 'Убрать'}
	}


//---------------------------------------------------------------------------
	this.TabInit = function() {
		this.RegisterTab()
	}

//---------------------------------------------------------------------------
	this.OnClose = function() {
		this.UnregisterTab()
		return true
	}

//---------------------------------------------------------------------------
	this.RenderTurnRuler = function(Value) {
		var RulerBox = this.TabControls.Items.TurnRuler
		var CounterBox = this.TabControls.Items.TurnCounter
		Canvas.FillRect(ABox(RulerBox.x - 4, RulerBox.y, RulerBox.w + 8, RulerBox.h), '#ffffff')
		Canvas.Rect(RulerBox, '#dddddd', '#000000')
		var MarkX = RulerBox.x + Value * RulerBox.w / this.MaxTurnLimit
		Canvas.Rect(ABox(MarkX - 4, RulerBox.y, 8, RulerBox.h), '#99ff99', '#000000')
		Value = Value.toString()
		Canvas.RenderTextBox(Value, CounterBox, '#000000', '#ffffff', '#000000',
			'center')
	}

//---------------------------------------------------------------------------
	this.RenderSnake = function(Index) {
		var Snake = this.Fight.Snakes[Index]
		if (!Snake) return

		var dy = this.ListBox.h * Index
		var Skin = SnakeSkins.Get(Snake.SkinId)
		var Items = this.ListItems
		Canvas.RenderSprite(Skin, Items.Skin.x, Items.Skin.y + dy)
		var Box = {x: Items.Name.x, y: Items.Name.y + dy, w: Items.Name.w, h: Items.Name.h}
		Canvas.RenderTextBox(Snake.SnakeName, Box, '#000000', this.SnakeListColors[Index])
	}

//---------------------------------------------------------------------------
	this.RenderSnakeList = function() {
		var Box = Clone(this.ListBox)
		var Buttons = ['Remove']
		var Items = this.TabControls.Items.SnakeButtons.Items
		var ListItems = Clone(this.ListItems)

		for(var i = 0; i < 4; i++) {
			Canvas.FillRect(Box, this.SnakeListColors[i])
			for(var j in Buttons) {
				var Button = ListItems[Buttons[j]]
				if (Items[i]) {
					Canvas.RenderTextBox(Button.Label, Button, '#000000', Button.Color, '#000000', 'center')
				}
				Button.y += Box.h
			}
			this.RenderSnake(i)
			Box.y += Box.h
		}
	}

//---------------------------------------------------------------------------
	this.RenderBody = function() {
		this.RenderSnakeList()
		this.RenderTurnRuler(this.Fight.TurnLimit)

		var Buttons = this.TabControls.Items.TurnButtons.Items
		for(var i in Buttons) {
			var Button = Buttons[i]
			Canvas.RenderSprite(Sprites.Get(Button.Sprite), Button.x, Button.y)
		}
		Button = this.TabControls.Items.RunButton
		Canvas.RenderTextBox(Button.Label, Button, '#000000', '#99ff99',
			'#000000', 'center', 'middle')

		if (this.ShowWidget) this.Widget.Render()
	}

//---------------------------------------------------------------------------
	this.ShowSnakeList = function(Index) {
		this.SnakeIndex = Index
		this.ShowWidget = true
		this.Show()
	}

//---------------------------------------------------------------------------
	this.RunFight = function() {
		var Snakes = this.Fight.Snakes
		var HasSnakes = false
		for(var i in Snakes) {
			if (this.Fight.Snakes[i]) {
				HasSnakes = true
				break
			}
		}
		if (!HasSnakes) {
			alert('Назначьте хотя бы одну змею!')
			return
		}

		var Request = {}
		if (Snakes[0] && !Snakes[0].SnakeId) {
			Request.Request = 'fight test'
			var Snake = Snakes[0].Serialize()
			for (var Name in Snake) Request[Name] = Snake[Name]
			Snakes = Snakes.slice(1)
			Request.OtherSnakeIds = []
			for (i in Snakes) Request.OtherSnakeIds.push(Snakes[i] ? Snakes[i].SnakeId : null)
		} else {
			Request.Request = 'fight train'
			Request.SnakeIds = []
			for (i in Snakes) Request.SnakeIds.push(Snakes[i] ? Snakes[i].SnakeId : null)
		}
		Request.TurnLimit = this.Fight.TurnLimit

		PostRequest(null, Request, 10, function (Response) {
			this.UnregisterTab()
			TabSet.Replace(this, new AFightViewer(new AFight(Response)))
		}, null, this)
	}

//---------------------------------------------------------------------------
	this.OnClick = function(x, y, Dataset) {
		var Limit
		switch(Dataset.cls) {
			case 'ruler':
				this.Fight.TurnLimit = (x * this.MaxTurnLimit / this.TabControls.Items.TurnRuler.w + 1)
				this.RenderTurnRuler(this.Fight.TurnLimit)
			break

			case 'turn':
				Limit = this.Fight.TurnLimit + parseInt(Dataset.id)
				if (Limit < 1) Limit = 1
				if (Limit > this.MaxTurnLimit) Limit = this.MaxTurnLimit
				this.Fight.TurnLimit = Limit
				this.RenderTurnRuler(Limit)
			break

			case 'limit':
				Limit = parseInt(prompt('Лимит ходов:', this.Fight.TurnLimit))
				if (Limit > 0 && Limit <= this.MaxTurnLimit) {
					this.Fight.TurnLimit = Limit
					this.RenderTurnRuler(Limit)
				}
			break

			case 'change':
				this.ShowSnakeList(Dataset.id)
			break

			case 'remove':
				this.Fight.Snakes[Dataset.id] = null
				this.RenderBody()
			break

			case 'run':
				this.RunFight()
			break

			case 'list-cancel':
				this.ShowWidget = false
				this.Show()
			break

			case 'list-snake':
				this.Fight.Snakes[this.SnakeIndex] = this.Widget.List.Items[Dataset.id]
				this.ShowWidget = false
				this.Show()
			break

			default:
				if (this.ShowWidget) this.Widget.OnClick(x, y, Dataset)
				else alert('не реализовано')
			break
		}
	}

//---------------------------------------------------------------------------
	this.RenderControls = function () {
		Canvas.RenderHtml('controls', Canvas.MakeControlHtml(this.ShowWidget ? this.Widget.WidgetControls : this.TabControls))
	}

//---------------------------------------------------------------------------
	this.AddSnake = function (Snake) {
		for (var i = 0; i < 4; i++) {
			if (!this.Fight.Snakes[i]) break
		}

		if (Snake.SnakeName) {
			this.Fight.Snakes[i] = Snake
			this.Show()
		} else Snake.Refresh(function () {
			this.Fight.Snakes[i] = Snake
			this.Show()
		}, null, this)
	}

//---------------------------------------------------------------------------
	;(function() {
		var FirstSnake = this.Fight.Snakes[0]
		if (FirstSnake) {
			if (!FirstSnake.SnakeId) {
				delete this.TabControls.Items.SnakeButtons.Items[0]
			} else if (!FirstSnake.SnakeName) {
				FirstSnake.Refresh(function () {
					this.Show()
				}, function () {
					this.Fight.Snakes[0] = null
					this.Show()
				}, this)
			}
		}
	}).call(this)

//---------------------------------------------------------------------------
}
Extend(AFightPlanner, BPageTab)
