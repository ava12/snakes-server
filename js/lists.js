//---------------------------------------------------------------------------
//---------------------------------------------------------------------------
function AListTab() {
	this.TabWidth = 24
	this.ListX = 0
	this.ListY = 40
	this.ListFields = {Width: 640, Height: 410, BorderColor: null}

//---------------------------------------------------------------------------
	this.RenderControls = function () {
		var Html =
			Canvas.MakeControlHtml(this.TabControls) +
			Canvas.MakeControlHtml(this.List.WidgetControls)
		Canvas.RenderHtml('controls', Html)
	}

//---------------------------------------------------------------------------
	this.RenderList = function () {
		this.TabControlHtml = null
		this.List.Render(this.ListX, this.ListY)
	}

//---------------------------------------------------------------------------
}
Extend(AListTab, BPageTab)


//---------------------------------------------------------------------------
//---------------------------------------------------------------------------
function ARatingListTab() {
	this.TabTitle = 'Рейтинги'
	this.TabSprite = Sprites.Get('16.Labels.Ratings')
	this.List = new ARatingListWidget(this.ListFields)

	this.OnClick = function (x, y, Dataset) {
		this.List.OnClick(x, y, Dataset)
	}
	this.RenderBody = this.RenderList

//---------------------------------------------------------------------------
}
Extend(ARatingListTab, new AListTab())


//---------------------------------------------------------------------------
//---------------------------------------------------------------------------
function APlayerListTab() {
	this.TabTitle = 'Игроки'
	this.TabSprite = Sprites.Get('16.Labels.Players')
	this.List = new APlayerListWidget(this.ListFields)

	this.OnClick = function (x, y, Dataset) {
		this.List.OnClick(x, y, Dataset)
	}
	this.RenderBody = this.RenderList

//---------------------------------------------------------------------------
}
Extend(APlayerListTab, new AListTab())


//---------------------------------------------------------------------------
//---------------------------------------------------------------------------
function ASnakeListTab() {
	this.TabTitle = 'Змеи'
	this.TabSprite = Sprites.Get('16.X')
	this.List = new ASnakeListWidget(this.ListFields)

	this.OnClick = function (x, y, Dataset) {
		var Id = Dataset.id
		var Tab

		switch (Dataset.cls) {
			case 'snake':
				var Snake = this.Widget.List.Items[Id]
				Game.AddTab(Snake.PlayerId == Game.Player.Id ? new ASnakeEditor(Snake.SnakeId) : new ASnakeViewer(Snake.SnakeId))
			break

			default:
				this.List.OnClick(x, y, Dataset)
		}
	}
	this.RenderBody = this.RenderList

//---------------------------------------------------------------------------
}
Extend(ASnakeListTab, new AListTab())


//---------------------------------------------------------------------------
//---------------------------------------------------------------------------
function AFightListTab() {
	this.TabTitle = 'Бои'
	this.TabSprite = Sprites.Get('Attack')
	this.TabWidth = 64
	this.TabList = 'Unique'
	this.TabKey = 'Fights'
	this.ListY = 70
	this.ListType = 'ordered'
	this.Lists = {
		ordered: new AOrderedFightsWidget(this.ListFields),
		challenged: new AChallengedFightsWidget(this.ListFields),
		slots: new AFightSlotsWidget(this.ListFields)
	}
	this.List = this.Lists.ordered

	this.TabControls = {Items: {
		ordered: {x: 5, y: 35, w: 50, h: 30, Label: 'Мои', id: 'ordered', Data: {cls: 'list'}},
		challenged: {x: 60, y: 35, w: 100, h: 30, Label: 'Вызовы', id: 'challenged', Data: {cls: 'list'}},
		slots: {x: 165, y: 35, w: 150, h: 30, Label: 'Сохраненные', id: 'slots', Data: {cls: 'list'}},
	}}

//---------------------------------------------------------------------------
	this.RenderBody = function() {
		for (var Name in this.TabControls.Items) {
			var Control = this.TabControls.Items[Name]
			if (Name == this.ListType) {
				Canvas.RenderTextBox(Control.Label, Control, '#000', '#fff')
			} else {
				Canvas.RenderTextButton(Control.Label, Control, '#ddf')
			}
		}
		this.RenderList()
	}

//---------------------------------------------------------------------------
}
Extend(AFightListTab, new AListTab())