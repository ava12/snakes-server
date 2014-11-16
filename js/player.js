function APlayer(Id) {
	this.Player = {
		Id: Id,
		Name: null,
		Rating: null,
		Snakes: []
	}

	this.IsMe = (Id == Game.Player.Id)
	this.TabSprite = Sprites.Get(this.IsMe ? '16.Labels.Me' : '16.Labels.Player')

	this.TabControls = {Items: {
		Name: {x: 10, y: 24, w: 520, h: 30},
		Rating: {x: 530, y: 24, w: 100, h: 30},
		Refresh: {x: 10, y: 56, w: 100, h: 26, Label: 'Обновить', Data: {cls: 'refresh'}},
		Challenge: {x: 120, y: 56, w: 70, h: 26, Data: {cls: 'challenge'},
			Label: (this.IsMe ? 'Вызов' : 'Вызвать'), BackColor: CanvasColors.Create,
			Title: (this.IsMe ? 'вызвать на бой других игроков' : 'вызвать игрока на бой')},
		Fight: {x: 200, y: 56, w: 100, h: 26, Data: {cls: 'fight'},
			Label: 'Новый бой', BackColor: CanvasColors.Create},
		Snakes: {Items: []}
	}}

	this.Labels = {
		Snakes: {Label: 'Змеи:', x: 10, y: 86, w: 70, h: 30}
	}

	this.SnakeItemHeight = 30
	this.SnakeItemWidth = 620
	this.SnakeItemX = 10
	this.SnakeItemY = 116
	this.SnakeItems = {
		Me: {
			Skin: {x: 12, y: 7},
			Type: {x: 68, y: 3, w: 50, h: 26},
			Name: {x: 118, y: 3, w: 380, h: 26},
			Link: {x: 12, y: 3, w: 486, h: 26, Data: {cls: 'snake-edit'}},
			Assign: {x: 506, y: 3, w: 50, h: 26, BackColor: CanvasColors.Modify,
				Label: 'Боец', Title: 'назначить змею бойцом', Data: {cls: 'snake-assign'}},
			Delete: {x: 560, y: 3, w: 70, h: 26,  BackColor: CanvasColors.Delete,
				Label: 'Удалить', Title: 'удалить змею', Data: {cls: 'snake-delete'}}
		},
		Other: {
			Skin: {x: 12, y: 7},
			Type: {x: 68, y: 3, w: 50, h: 26},
			Name: {x: 118, y: 3, w: 510, h: 26},
			Link: {x: 12, y: 3, w: 616, h: 26, Data: {cls: 'snake-view'}},
		}
	}

	if (!this.IsMe) delete this.TabControls.Items.Fight

//---------------------------------------------------------------------------
	this.TabInit = function () {
		Game.Tabs.Players[this.Player.Id] = this.TabId
	}

//---------------------------------------------------------------------------
	this.OnClose = function () {
		delete Game.Tabs.Players[this.Player.Id]
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
			var KeyMap = {PlayerId: 'Id', PlayerName: 'Name', Rating: 'Rating',
				FighterId: 'FighterId', PlayerSnakes: 'Snakes'}
			for (var n in KeyMap) this.Player[KeyMap[n]] = Data[n]
			this.TabTitle = this.Player.Name

			this.Clear()
			this.TabControls.Items.Snakes.Items = []
			if (this.IsMe) {
				Game.Player.Rating = this.Player.Rating
				Game.Player.FighterId = this.Player.FighterId
			}

			var Controls = this.TabControls.Items
			Controls.Challenge.Skip = (Game.Player.Rating == undefined || this.Player.Rating == undefined)

			var y = this.SnakeItemY
			var IC = this.SnakeItems[this.IsMe ? 'Me' : 'Other']
			var Snakes = this.Player.Snakes
			var SnakeControls = Controls.Snakes.Items
			var FighterId = this.Player.FighterId

			for (var i = 0; i < Snakes.length; i++) {
				Item = Snakes[i]
				if (this.IsMe || Item.SnakeType == 'B') {
					SnakeControls.push(this.MakeControl(IC.Link, 0, y, {
						id: Item.SnakeId, Title: Item.SnakeName
					}))
				}

				if (this.IsMe && Item.SnakeId != FighterId) {
					SnakeControls.push(this.MakeControl(IC.Assign, 0, y, {id: Item.SnakeId}))
					SnakeControls.push(this.MakeControl(IC.Delete, 0, y, {id: Item.SnakeId}))
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
		if (x || y) {
			Button = Clone(Button)
			if (x) Button.x += x
			if (y) Button.y += y
		}
		Canvas.RenderTextButton(Button.Label, Button, Button.BackColor)
	}

//---------------------------------------------------------------------------
	this.RenderBody = function () {
		if (!this.Player.Name) {
			this.LoadPlayer()
			return
		}

		var Controls = this.TabControls.Items
		Canvas.RenderText(this.Player.Name, Controls.Name)
		var Rating = (this.Player.Rating == undefined ? '---' : this.Player.Rating)
		Canvas.RenderText(Rating, Controls.Rating, '#000', 'right')
		this.RenderTextButton(Controls.Refresh)
		if (!Controls.Challenge.Skip) this.RenderTextButton(Controls.Challenge)
		if (Controls.Fight) this.RenderTextButton(Controls.Fight)

		for (var Name in this.Labels) {
			Canvas.RenderText(this.Labels[Name].Label, this.Labels[Name])
		}

		var Box = {x: this.SnakeItemX, y: this.SnakeItemY, w: this.SnakeItemWidth, h: this.SnakeItemHeight}
		var IC = this.SnakeItems[this.IsMe ? 'Me' : 'Other']
		var Snakes = this.Player.Snakes
		var FighterId = Game.Player.FighterId

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

			if (this.IsMe && Snake.SnakeId != FighterId) {
				this.RenderTextButton(IC.Assign, 0, Box.y)
				this.RenderTextButton(IC.Delete, 0, Box.y)
			}

			Box.y += Box.h
		}
	}

//---------------------------------------------------------------------------
	this.OnClick = function (x, y, Dataset) {
		var Class = Dataset.cls
		var Id = Dataset.id

		switch (Class) {
			case 'refresh':
				this.LoadPlayer()
			break

			case 'snake-view':
				if (Game.Tabs.Snakes[Id]) TabSet.Select(Game.Tabs.Snakes[Id])
				else TabSet.Add(new ASnakeViewer(Id))
			break
		}
	}

//---------------------------------------------------------------------------
//---------------------------------------------------------------------------
//---------------------------------------------------------------------------
//---------------------------------------------------------------------------
}
Extend(APlayer, BPageTab)