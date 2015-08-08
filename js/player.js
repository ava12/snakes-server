function APlayer(Id) {
	if (typeof Id == 'object') Id = Id.PlayerId
	this.TabList = 'Players'
	this.TabKey = Id

	this.Player = {
		Id: Id,
		Name: null,
		Rating: null,
		Snakes: []
	}

	this.IsMe = (Id == Game.Player.PlayerId)
	this.TabSprite = Sprites.Get(this.IsMe ? '16.Labels.Me' : '16.Labels.Player')

	this.TabControls = {Items: {
		Name: {x: 10, y: 24, w: 520, h: 30},
		Rating: {x: 530, y: 24, w: 100, h: 30},
		Refresh: {x: 10, y: 56, w: 100, h: 26, Label: 'Обновить', Data: {cls: 'refresh'},
			BackColor: CanvasColors.Info},
		Challenge: {x: 120, y: 56, w: 70, h: 26, Data: {cls: 'challenge'},
			Label: (this.IsMe ? 'Вызов' : 'Вызвать'), BackColor: CanvasColors.Create,
			Title: (this.IsMe ? 'вызвать на бой других игроков' : 'вызвать игрока на бой')},
		Fight: {x: 200, y: 56, w: 100, h: 26, Data: {cls: 'fight'},
			Label: 'Новый бой', BackColor: CanvasColors.Create},
		NewSnake: {x: 84, y: 90, w: 70, h: 22, Label: 'Новая', Data: {cls: 'snake-new'},
			BackColor: CanvasColors.Create},
		Snakes: {Items: []}
	}}

	this.Labels = {
		Snakes: {Label: 'Змеи:', x: 10, y: 90, w: 70, h: 22}
	}

	this.SnakeItemHeight = 30
	this.SnakeItemWidth = 620
	this.SnakeItemX = 10
	this.SnakeItemY = 116
	this.SnakeItems = {
		Me: {
			Skin: {x: 12, y: 7},
			Type: {x: 68, y: 3, w: 50, h: 26},
			Name: {x: 118, y: 3, w: 300, h: 26},
			Link: {x: 12, y: 3, w: 418, h: 26, Data: {cls: 'snake-edit'}},
			Fight: {x: 423, y: 3, w: 70, h: 26, Label: 'В бой!',
				BackColor: CanvasColors.Create, Data: {cls: 'snake-fight'}},
			Assign: {x: 506, y: 3, w: 50, h: 26, BackColor: CanvasColors.Modify,
				Label: 'Боец', Title: 'назначить змею бойцом', Data: {cls: 'snake-assign'}},
			Delete: {x: 560, y: 3, w: 70, h: 26,  BackColor: CanvasColors.Delete,
				Label: 'Удалить', Title: 'удалить змею', Data: {cls: 'snake-delete'}}
		},
		Other: {
			Skin: {x: 12, y: 7},
			Type: {x: 68, y: 3, w: 50, h: 26},
			Name: {x: 118, y: 3, w: 415, h: 26},
			Link: {x: 12, y: 3, w: 533, h: 26, Data: {cls: 'snake-view'}},
			Fight: {x: 550, y: 3, w: 70, h: 26, Label: 'В бой!',
				BackColor: CanvasColors.Create, Data: {cls: 'snake-fight'}}
		}
	}

	if (!this.IsMe) {
		delete this.TabControls.Items.Fight
		delete this.TabControls.Items.NewSnake
	}

//---------------------------------------------------------------------------
	this.TabInit = function () {
		this.RegisterTab()
	}

//---------------------------------------------------------------------------
	this.OnClose = function () {
		this.UnregisterTab()
		return true
	}

//---------------------------------------------------------------------------
	this.MakeControl = function (Control, x, y, Params) {
		var Result = Clone(Control)
		for (var Name in Params) Result[Name] = Params[Name]
		Result.x += x
		Result.y += y
		return Result
	}

//---------------------------------------------------------------------------
	this.LoadPlayer = function () {
		var Request = {Request: 'player info', PlayerId: this.Player.Id}
		PostRequest(null, Request, 20, function (Data) {
			var KeyMap = {PlayerId: true, PlayerName: true, Rating: true,
				SnakeId: true, PlayerSnakes: true}
			for (var n in KeyMap) this.Player[n] = Data[n]
			this.TabTitle = this.Player.PlayerName

			this.Clear()
			this.TabControls.Items.Snakes.Items = []
			if (this.IsMe) {
				Game.Player.Rating = this.Player.Rating
				Game.Player.SnakeId = this.Player.SnakeId
			}

			var Controls = this.TabControls.Items
			Controls.Challenge.Skip = (this.Player.Rating == undefined)

			var y = this.SnakeItemY
			var IC = this.SnakeItems[this.IsMe ? 'Me' : 'Other']
			var Snakes = this.Player.PlayerSnakes
			var SnakeControls = Controls.Snakes.Items
			var FighterId = this.Player.SnakeId

			for (var i = 0; i < Snakes.length; i++) {
				var Item = Snakes[i]

				SnakeControls.push(this.MakeControl(IC.Fight, 0, y, {id: i}))

				if (this.IsMe || Item.SnakeType == 'B') {
					SnakeControls.push(this.MakeControl(IC.Link, 0, y, {
						id: i, Title: Item.SnakeName
					}))
				}

				if (this.IsMe && Item.SnakeId != FighterId) {
					SnakeControls.push(this.MakeControl(IC.Assign, 0, y, {id: i}))
					SnakeControls.push(this.MakeControl(IC.Delete, 0, y, {id: i}))
				}

				y += this.SnakeItemHeight
			}

			TabSet.RenderTabs()
			if (this.IsActive()) {
				this.Show()
			}
		}, null, this)
	}

//---------------------------------------------------------------------------
	this.RenderTextButton = function (Button, x, y) {
		if (!Button) return

		if (x || y) {
			Button = Clone(Button)
			if (x) Button.x += x
			if (y) Button.y += y
		}
		Canvas.RenderTextButton(Button.Label, Button, Button.BackColor)
	}

//---------------------------------------------------------------------------
	this.RenderBody = function () {
		if (!this.Player.PlayerName) {
			this.LoadPlayer()
			return
		}

		var Controls = this.TabControls.Items
		Canvas.RenderText(this.Player.PlayerName, Controls.Name)
		var Rating = (this.Player.Rating == undefined ? '---' : this.Player.Rating)
		Canvas.RenderText(Rating, Controls.Rating, '#000', 'right')
		this.RenderTextButton(Controls.Refresh)
		if (!Controls.Challenge.Skip) this.RenderTextButton(Controls.Challenge)
		if (Controls.Fight) this.RenderTextButton(Controls.Fight)
		this.RenderTextButton(Controls.NewSnake)

		for (var Name in this.Labels) {
			Canvas.RenderText(this.Labels[Name].Label, this.Labels[Name])
		}

		var Box = {x: this.SnakeItemX, y: this.SnakeItemY, w: this.SnakeItemWidth, h: this.SnakeItemHeight}
		var IC = this.SnakeItems[this.IsMe ? 'Me' : 'Other']
		var Snakes = this.Player.PlayerSnakes
		var FighterId = Game.Player.SnakeId

		for (var i = 0; i < Snakes.length; i++) {
			var Snake = Snakes[i]
			Canvas.FillRect(Box, CanvasColors.Items[i & 1])
			Canvas.RenderSprite(SnakeSkins.Get(Snake.SkinId), IC.Skin.x, IC.Skin.y + Box.y)
			Canvas.RenderText(
				(Snake.SnakeType == 'B' ? 'бот' : 'змея'),
				{x: IC.Type.x, y: IC.Type.y + Box.y, w: IC.Type.w, h: IC.Type.h}
			)
			Canvas.RenderText(Snake.SnakeName,
				{x: IC.Name.x, y: IC.Name.y + Box.y, w: IC.Name.w, h: IC.Name.h})

			this.RenderTextButton(IC.Fight, 0, Box.y)
			if (this.IsMe) {
				if (Snake.SnakeType != 'B' && Snake.SnakeId != FighterId) {
					this.RenderTextButton(IC.Assign, 0, Box.y)
				}
				if (Snake.SnakeId != FighterId) {
					this.RenderTextButton(IC.Delete, 0, Box.y)
				}
			}

			Box.y += Box.h
		}
	}

//---------------------------------------------------------------------------
	this.OnClick = function (x, y, Dataset) {
		var Class = Dataset.cls
		var Id = Dataset.id
		var Tab, SnakeId

		switch (Class) {
			case 'refresh':
				this.LoadPlayer()
			break

			case 'snake-view':
			case 'snake-edit':
				SnakeId = this.Player.PlayerSnakes[Id].SnakeId
				Game.AddTab(Class == 'snake-view' ? new ASnakeViewer(SnakeId) : new ASnakeEditor(SnakeId))
			break

			case 'snake-new':
				if (this.Player.PlayerSnakes.length >= 10) alert('Не более 10 змей')
				else {
					this.Player.PlayerName = null
					TabSet.Add(new ASnakeEditor())
				}
			break

			case 'snake-fight':
				Tab = Game.FindTab('Unique', 'Fight')
				if (Tab) {
					Tab.AddSnake(new ASnake(this.Player.PlayerSnakes[Id]))
					TabSet.Select(Tab)
				} else {
					TabSet.Add(new AFightPlanner(new ASnake(this.Player.PlayerSnakes[Id])))
				}
			break

			case 'fight':
				Game.AddTab(new AFightPlanner())
			break

			case 'challenge':
				Tab = Game.AddTab(new AChallengePlanner())
				if (!this.IsMe) Tab.AddPlayer(this.Player)
			break

			case 'snake-assign':
				SnakeId = this.Player.PlayerSnakes[Id].SnakeId
				PostRequest(null, {Request: 'snake assign', SnakeId: SnakeId}, 10, function () {
					this.LoadPlayer()
				}, function () {
					this.LoadPlayer()
				}, this)
			break

			default:
				alert('не реализовано')
			break
		}
	}

//---------------------------------------------------------------------------
}
Extend(APlayer, BPageTab)