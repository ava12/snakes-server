//---------------------------------------------------------------------------
//---------------------------------------------------------------------------
function AWidget() {
	this.BackColor = '#fff'
	this.BorderColor = '#000'
	this.Width = 630
	this.Height = 446
	this.WidgetControls = {Items: []}
	this.x = 5
	this.y = 29
	this.IsPopup = false
	this.WidgetId = ''
	this.Popup = null

//---------------------------------------------------------------------------
	this.Render = function (x, y) {
		if (x != undefined) this.x = x
		if (y != undefined) this.y = y
		Canvas.SaveState()
		Canvas.Clip({x: this.x, y: this.y, w: this.Width, h: this.Height})
		Canvas.Translate(this.x, this.y)
		this.Clear()
		this.RenderBody()
		Canvas.RestoreState()
		if (this.Popup) {
			this.Popup.Render()
			this.WidgetControls = this.Popup.WidgetControls
		}
	}

//---------------------------------------------------------------------------
	this.Clear = function () {
		Canvas.Rect({x: 0, y: 0, w: this.Width, h: this.Height}, this.BackColor, this.BorderColor)
	}

//---------------------------------------------------------------------------
	this.SetFields = function (Fields) {
		if (!Fields) return

		for (var Name in Fields) {
			if (this[Name] != undefined) this[Name] = Fields[Name]
		}
	}

//---------------------------------------------------------------------------
	this.RenderBody = function () {}

//---------------------------------------------------------------------------
	this.AddControl = function (Control) {
		Control = Clone(Control)
		Control.x += this.x
		Control.y += this.y
		if (Control.Data) Control.Data.widget = this.WidgetId
		else Control.Data = {widget: this.WidgetId}
		this.WidgetControls.Items.push(Control)
	}

//---------------------------------------------------------------------------
	this.OnClick = function () {
		alert('- не реализовано -')
	}

//---------------------------------------------------------------------------
}


//---------------------------------------------------------------------------
//---------------------------------------------------------------------------
function AListWidget() {
	this.ItemBackColor = CanvasColors.Items
	this.LinkColor = '#3333ff'
	this.LinkBackColor = null
	this.List = {
		Request: {},
		Columns: [], // [{Label:, Width:, Name:?}+]
		Fields: [],
		EmptyFields: null,
		Pages: 0,
		Page: 0, // начиная с 1
		SortName: '',
		SortDesc: false,
		ExtraSort: [], // [[имя, инверсия?]*]
		ItemName: 'List',
		Items: []
	}
	this.SortButtons = [
		{x: 0, y: 0, w: 15, h: 15, Sprites: ['Sort.Asc', 'Sort.IsAsc'],
			id: '', Title: 'по возрастанию', Data: {cls: 'list-sort', name: ''}},
		{x: 0, y: 15, w: 15, h: 15, Sprites: ['Sort.Desc', 'Sort.IsDesc'],
			id: 1, Title: 'по убыванию', Data: {cls: 'list-sort', name: ''}}
	]
	this.RefreshButton = {Label: 'Обновить', Title: 'перезагрузить страницу',
		Data: {cls: 'list-refresh'}, BackColor: CanvasColors.Info}
	this.CancelButton = {Label: 'Отмена', Title: 'закрыть список', Data: {cls: 'list-cancel'}}
	this.ItemHeight = 30
	this.ItemWidth = 620
	this.ItemX = 5
	this.TopY = 5
	this.PageSize = 10

//---------------------------------------------------------------------------
	this.GetItemProperty = function(Item, Name) {
		Name = Name.split('.')
		for(var i = 0; i < Name.length; i++) Item = Item[Name[i]]
		return Item
	}

//---------------------------------------------------------------------------
	this.RenderTextButtonBox = function (Label, Button, x, y, BackColor, Id) {
		var Box = Clone(Button)
		if (!Label) Label = Box.Label
		Box.x += x
		Box.y += y
		Canvas.RenderTextBox(Label, Box, '#000000', BackColor, '#000000', 'center')
		Box.id = (Id ? Id : Button.id)
		this.AddControl(Box)
	}

//---------------------------------------------------------------------------
	this.RenderTextButton = function(Item, Index, x, y, Params) {
		var BackColor = Params.BackColor
		if (!BackColor) BackColor = CanvasColors.Button
		var Width = Params.Width
		if (!Width) Width = Canvas.GetTextMetrics(Params.Label).w + 8
		var Box = {x: x + 4, y: y + 4, w: Width, h: this.ItemHeight - 8}
		Canvas.RenderTextBox(Params.Label, Box, '#000000', BackColor, '#000000', 'center')
		Box.id = (Params.id ? Params.id : Index)
		if (Params.Title) Box.Title = Params.Title
		if (Params.Data) Box.Data = Params.Data
		this.AddControl(Box)
		return Width + 8
	}

//---------------------------------------------------------------------------
	this.RenderButton = function (Item, Index, x, y, Params) {
		var Box = Clone(Params)
		Box.x += x
		Box.y += y
		Canvas.RenderSprite(Sprites.Get(Params.Sprite), Box.x, Box.y)
		this.AddControl(Box)
		return Params.w
	}

//---------------------------------------------------------------------------
	this.RenderText = function (Text, x, y, w, Align, Color) {
		Text = String(Text).replace(/\s+/g, '\u00a0')
		var TextWidth = Canvas.GetTextMetrics(Text).w
		if (!w) w = TextWidth
		Canvas.RenderText(Text, ABox(x + 4, y + 1, w, this.ItemHeight - 2), Color, Align)
		if (TextWidth > w) {
			this.AddControl({x: x, y: y, w: w, h: this.ItemHeight - 2, Title: Text})
		}
		return w + 8
	}

//---------------------------------------------------------------------------
	this.RenderTextLabel = function(Item, Index, x, y, Params) {
		var Text = Params.Label
		var Width = Params.Width
		if (!Width) Width = Canvas.GetTextMetrics(Text).w
		return this.RenderText(Text, x, y, Width, Params.Align)
	}

//---------------------------------------------------------------------------
	this.RenderPropertyText = function(Item, Index, x, y, Params) {
		var Text = this.GetItemProperty(Item, Params.Property)
		var Width = Params.Width
		if (!Width) Width = Canvas.GetTextMetrics(Text).w
		return this.RenderText(Text, x, y, Width, Params.Align)
	}

//---------------------------------------------------------------------------
	this.RenderLink = function (Label, Id, x, y, Params) {
		if (!Label) Label = Params.Label
		var Width = Canvas.GetTextMetrics(Label).w
		var Box = Clone(Params)
		Box.x = x
		Box.y = y + 4
		Box.w = Width + 8
		Box.h = this.ItemHeight - 8
		Canvas.RenderTextBox(Label, Box, this.LinkColor, this.LinkBackColor)
		if (Box.id == undefined) Box.id = Id
		if (Box.Title == undefined) Box.Title = Label
		this.AddControl(Box)
		return (Params.Width ? Params.Width : Width) + 8
	}

//---------------------------------------------------------------------------
	this.RenderPropertyLink = function (Item, Index, x, y, Params) {
		var Label = this.GetItemProperty(Item, Params.Property)
		var Id = (Params.id == undefined ? (Params.IdProperty ? Item[Params.IdProperty] : Index) : Params.id)
		return this.RenderLink(Label, Id, x, y, Params)
	}

//---------------------------------------------------------------------------
	this.RenderSkin = function(Item, Index, x, y) {
		var dy = (this.ItemHeight - 16) >> 1
		Canvas.RenderSprite(SnakeSkins.Get(Item.SkinId), x, y + dy)
		return 48
	}

//---------------------------------------------------------------------------
	this.RenderGap = function(Item, Index, x, y, Params) {
		return (Params.Width ? Params.Width : 4)
	}

//---------------------------------------------------------------------------
	this.RenderSeparator = function (Item, Index, x, y, Params) {
		var Width = (Params.Width ? Params.Width : 1)
		Canvas.FillRect({x: x, y: y, w: Width, h: this.ItemHeight}, '#ffffff')
		return Width
	}

//---------------------------------------------------------------------------
	this.RenderField = function(Field, Item, Index, x, y) {
		var Name = 'Render' + Field.Type
		return this[Name].call(this, Item, Index, x, y, Field)
	}

//---------------------------------------------------------------------------
	this.RenderItem = function(Item, Fields, y, Index) {
		var x = this.ItemX
		if (Index != undefined) {
			var Color = this.ItemBackColor[Index % this.ItemBackColor.length]
			Canvas.FillRect(ABox(x, y, this.ItemWidth, this.ItemHeight), Color)
		}
		//if (!Item) return

		for (var i = 0; i < Fields.length; i++) {
			x += this.RenderField(Fields[i], Item, Index, x, y)
		}
	}

//---------------------------------------------------------------------------
	this.RenderListPageButton = function (Page, x, y) {
		if (Page == this.List.Page) {
			return this.RenderText(Page, x, y)
		} else {
			return this.RenderTextButton(null, Page, x, y, {Label: Page, Data: {cls: 'list-page'}})
		}
	}

//---------------------------------------------------------------------------
	this.RenderList = function (y) {
		var List = this.List
		if (!List.Loaded) this.RefreshList()

		var x = this.ItemX

		for (var i in List.Columns) {
			var Column = List.Columns[i]
			var Width = (Column.Name ? Column.Width - 15 : Column.Width)
			x += this.RenderText(Column.Label, x, y, Width, 'center')

			if (Column.Name) {
				for (var j = 0; j < 2; j++) {
					var Button = Clone(this.SortButtons[j])
					Button.Data.name = Column.Name
					var IsActive = (List.SortName == Column.Name && (!!j == !!List.SortDesc))
					Button.Sprite = Button.Sprites[IsActive ? 1 : 0]
					this.RenderButton(null, null, x, y, Button)
				}
				x += 15
			}
		}
		y += this.ItemHeight + 4

		var Items = List.Items
		if (Items) {
			for (i = 0; i < Items.length; i++) {
				this.RenderItem(Items[i], (Items[i] ? List.Fields : List.EmptyFields), y, i)
				y += this.ItemHeight
			}
		}

		x = this.ItemX
		y += 4;
		x += this.RenderTextButton(null, null, x, y, this.RefreshButton)
		if (this.IsPopup) {
			x += this.RenderTextButton(null, null, x, y, this.CancelButton)
		}

		if (List.Pages && List.Pages > 1) {
			x += 8
			x += this.RenderListPageButton(List, 1, x, y)
			var Page = List.Page
			var GroupFirst = (Page >= 5 ? Page - 3 : 2)
			var GroupLast = (Page <= (List.Pages - 4) ? Page + 3 : List.Pages - 1)

			if (Page > 5) x += 8
			for (i = GroupFirst; i <= GroupLast; i++) {
				x += this.RenderListPageButton(List, i, x, y)
			}
			if (Page < (List.Pages - 4)) x += 8
			this.RenderListPageButton(List, List.Pages, x, y)
		}
	}

//---------------------------------------------------------------------------
	this.RenderBody = function () {
		this.WidgetControls.Items = []
		this.RenderList(this.TopY)
	}

//---------------------------------------------------------------------------
	this.RefreshList = function () {
		var List = this.List
		var PageSize = this.PageSize
		var Request = Clone(List.Request)
		if (List.Page) {
			Request.FirstIndex = (List.Page - 1) * PageSize
			Request.Count = PageSize
		}
		var SortBy = []
		if (List.SortName) {
			SortBy.push((List.SortDesc ? '>' : '<') + List.SortName)
		}
		if (List.ExtraSort) {
			var Desc = !!List.SortDesc
			for (var i = 0; i < List.ExtraSort.length; i++) {
				SortBy.push((!!List.ExtraSort[i][1] == Desc ? '<' : '>') + List.ExtraSort[i][0])
			}
		}
		if (SortBy.length) Request.SortBy = SortBy

		List.Loaded = true
		PostRequest(null, Request, 10, function (Response) {
			List.Items = Response[List.ItemName].slice(0, PageSize)
			if (Response.FirstIndex != undefined) {
				List.Page = Math.floor(Response.FirstIndex / PageSize) + 1
				List.Pages = Math.floor((Response.TotalCount + PageSize - 1) / PageSize)
				List.SortDesc = (Response.SortBy[0].charAt(0) == '>')
				List.SortName = Response.SortBy[0].substr(1)
			}

			TabSet.CurrentTab.Show()
		}, null, this)
	}

//---------------------------------------------------------------------------
	this.OnClick = function (x, y, Dataset) {
		var Id = Dataset.id
		var List = this.List

		switch (Dataset.cls) {
			case undefined:
				return true
			break;

			case 'list-refresh':
				this.RefreshList()
			break;

			case 'list-page':
				List.Page = Id
				this.RefreshList()
			break;

			case 'list-sort':
				List.SortName = Dataset.name
				List.SortDesc = !!Id
				this.RefreshList()
			break;

			default:
				alert('не реализовано')
				return false
		}

		return true
	}

//---------------------------------------------------------------------------
}
Extend(AListWidget, new AWidget())


//---------------------------------------------------------------------------
//---------------------------------------------------------------------------
function ARatingListWidget(Fields) {
	this.SetFields(Fields)
	this.List = {
		Request: {Request: 'ratings'},
		ItemName: 'Ratings',
		SortName: 'Rating',
		SortDesc: true,
		ExtraSort: [['PlayerId', false]],

		Columns: [
			{Label: 'Игрок', Width: 200, Name: 'PlayerName'},
			{Label: 'Рейтинг', Width: 100, Name: 'Rating'},
			{Label: 'Боец', Width: 240}
		],

		Fields: [
			{Type: 'Gap'},
			{Type: 'PropertyLink', Width: 198, Property: 'PlayerName', Data: {cls: 'list-player'}},
			{Type: 'Separator'},
			{Type: 'PropertyText', Width: 84, Property: 'Rating', Align: 'right'},
			{Type: 'Gap', Width: 15},
			{Type: 'Separator'},
			{Type: 'Gap'},
			{Type: 'Skin'},
			{Type: 'Gap'},
			{Type: 'PropertyText', Width: 160, Property: 'SnakeName'},
			{Type: 'ChallengeButton', Width: 70, BackColor: CanvasColors.Create, Label: 'Вызвать'}
		]
	}

//---------------------------------------------------------------------------
	this.RenderChallengeButton = function (Item, Index, x, y, Params) {
		if (!Game.Player.SnakeId || Item.PlayerId == Game.Player.PlayerId) return Params.Width

		var Box = {x: x, y: y, Width: Params.Width, BackColor: Params.BackColor,
			Label: Params.Label, Data: {cls: 'list-challenge'}, Title: 'вызвать игрока на бой'}
		return this.RenderTextButton(Item, Index, x, y, Box)
	}

//---------------------------------------------------------------------------
	this.OnClick = function (x, y, Dataset) {
		var Class = Dataset.cls

		switch (Class) {
			case 'challenge':
				alert('<не реализовано>')
			break

			default:
				this.Parent.OnClick.call(this, x, y, Dataset)
		}
	}

//---------------------------------------------------------------------------
}
Extend(ARatingListWidget, new AListWidget())


//---------------------------------------------------------------------------
//---------------------------------------------------------------------------
function APlayerListWidget(Fields) {
	this.SetFields(Fields)
	this.List = {
		Request: {Request: 'player list'},
		ItemName: 'PlayerList',
		SortName: 'PlayerName',
		SortDesc: false,

		Columns: [
			{Label: 'Игрок', Width: 520, Name: 'PlayerName'},
			{Label: 'Рейтинг', Width: 100}
		],

		Fields: [
			{Type: 'Gap'},
			{Type: 'PropertyLink', Width: 515, Property: 'PlayerName', Data: {cls: 'list-player'}},
			{Type: 'Separator'},
			{Type: 'PropertyText', Width: 84, Property: 'Rating', Align: 'right'}
		]
	}

//---------------------------------------------------------------------------
}
Extend(APlayerListWidget, new AListWidget())


//---------------------------------------------------------------------------
//---------------------------------------------------------------------------
function ASnakeListWidget(Fields) {
	this.SetFields(Fields)
	this.List = {
		Request: {Request: 'snake list', SnakeTypes: 'BN'},
		ItemName: 'SnakeList',
		SortName: 'SnakeName',
		SortDesc: false,
		ExtraSort: [['SnakeId', false]],

		Buttons: [
			{Type: 'TextLabel', Label: 'все', id: 'BN', Data: {cls: 'snake-types'}},
			{Type: 'Gap'},
			{Type: 'TextButton', Label: 'змеи', id: 'N', Data: {cls: 'snake-types'}},
			{Type: 'Gap'},
			{Type: 'TextButton', Label: 'боты', id: 'B', Data: {cls: 'snake-types'}}
		],

		Columns: [
			{Label: 'Змея', Width: 290, Name: 'SnakeName'},
			{Label: 'Тип', Width: 40},
			{Label: 'Владелец', Width: 265, Name: 'PlayerName'}
		],

		Fields: [
			{Type: 'Gap'},
			{Type: 'Skin'},
			{Type: 'Gap'},
			{Type: 'PropertyLink', Width: 234, Property: 'SnakeName', Data: {cls: 'list-snake'}},
			{Type: 'Separator'},
			{Type: 'SnakeType', Width: 40},
			{Type: 'Separator'},
			{Type: 'Gap'},
			{Type: 'PropertyLink', Width: 260, Property: 'PlayerName', Data: {cls: 'list-player'}}
		]
	}

//---------------------------------------------------------------------------
	this.RenderSnakeType = function (Item, Index, x, y, Params) {
		var Text = (Item.SnakeType == 'B' ? 'бот' : 'змея')
		return this.RenderText(Text, x, y, Params.Width, 'center')
	}

//---------------------------------------------------------------------------
	this.RenderButtons = function (y) {
		this.RenderItem(null, this.List.Buttons, y, 0)
	}

//---------------------------------------------------------------------------
	this.RenderBody = function () {
		this.WidgetControls.Items = []
		this.RenderButtons(this.TopY)
		this.RenderList(this.TopY + 30)
	}

//---------------------------------------------------------------------------
	this.OnClick = function (x, y, Dataset) {
		var Class = Dataset.cls
		var Id = Dataset.id

		switch (Class) {
			case 'snake-types':
				this.List.Request.SnakeTypes = Id
				var Buttons = this.List.Buttons
				for (var i in Buttons) {
					if (Buttons[i].id) {
						Buttons[i].Type = (Buttons[i].id == Id ? 'TextLabel' : 'TextButton')
					}
				}
				this.RefreshList()
			break

			default:
				this.Parent.OnClick.call(this, x, y, Dataset)
		}
	}

//---------------------------------------------------------------------------
}
Extend(ASnakeListWidget, new AListWidget())


//---------------------------------------------------------------------------
//---------------------------------------------------------------------------
function AFightListWidget() {

//---------------------------------------------------------------------------
	this.RenderFightTime = function (Item, Index, x, y, Params) {
		var Label = FormatDate(Item.FightTime)
		var Id = (Params.IdProperty ? Item[Params.IdProperty] : Index)
		return this.RenderLink(Label, Id, x, y, Params)
	}

//---------------------------------------------------------------------------
	this.RenderFightSnake = function (Item, Index, x, y, Params) {
		var Width = (Params.Width ? Params.Width : 48)
		var Snake = Item.Snakes[Params.Index]
		if (!Snake) return Width

		var Title = Snake.SnakeName + ' (' + Snake.PlayerName + ')'
		this.AddControl({x: x, y: y + ((this.ItemHeight - 16) >> 1), w: 48, h: 16, Title: Title})
		this.RenderSkin(Snake, Index, x, y)
		return Width
	}

//---------------------------------------------------------------------------
	this.RenderFightType = function (Item, Index, x, y, Params) {
		var Types = {train: 'бой', challenge: 'вызов'}
		return this.RenderText(Types[Item.FightType], x, y, Params.Width, 'center')
	}

//---------------------------------------------------------------------------
}
Extend(AFightListWidget, new AListWidget())

//---------------------------------------------------------------------------
//---------------------------------------------------------------------------
function AOrderedFightsWidget(Fields) {
	this.SetFields(Fields)
	this.List = {
		Request: {Request: 'fight list', FightListType: 'ordered'},
		ItemName: 'FightList',

		Columns: [
			{Label: 'Время', Width: 120},
			{Label: 'Тип', Width: 50},
			{Label: 'Лимит', Width: 50},
			{Label: 'Ходов', Width: 50},
			{Label: 'Змеи', Width: 350}
		],

		Fields: [
			{Type: 'FightTime', IdProperty: 'FightId', Width: 120, Data: {cls: 'fight'}},
			{Type: 'FightType', Width: 50},
			{Type: 'PropertyText', Property: 'TurnLimit', Align: 'right', Width: 50},
			{Type: 'PropertyText', Property: 'TurnCount', Align: 'right', Width: 50},
			{Type: 'Gap'},
			{Type: 'FightSnake', Index: 0, Width: 60},
			{Type: 'FightSnake', Index: 1, Width: 60},
			{Type: 'FightSnake', Index: 2, Width: 60},
			{Type: 'FightSnake', Index: 3, Width: 60}
		]
	}

//---------------------------------------------------------------------------
}
Extend(AOrderedFightsWidget, new AFightListWidget())


//---------------------------------------------------------------------------
//---------------------------------------------------------------------------
function AChallengedFightsWidget(Fields) {
	this.SetFields(Fields)
	this.List = {
		Request: {Request: 'fight list', FightListType: 'challenged'},
		ItemName: 'FightList',

		Columns: [
			{Label: 'Время', Width: 120},
			{Label: 'Ходов', Width: 50},
			{Label: 'Змеи', Width: 450}
		],

		Fields: [
			{Type: 'FightTime', IdProperty: 'FightId', Width: 120, Data: {cls: 'fight'}},
			{Type: 'PropertyText', Property: 'TurnCount', Align: 'right', Width: 50},
			{Type: 'Gap'},
			{Type: 'FightSnake', Index: 0, Width: 60},
			{Type: 'FightSnake', Index: 1, Width: 60},
			{Type: 'FightSnake', Index: 2, Width: 60},
			{Type: 'FightSnake', Index: 3, Width: 60}
		]
	}

//---------------------------------------------------------------------------
}
Extend(AChallengedFightsWidget, new AFightListWidget())


//---------------------------------------------------------------------------
//---------------------------------------------------------------------------
function AFightSlotsWidget(Fields) {
	this.SetFields(Fields)
	this.List = {
		Request: {Request: 'slot list'},
		ItemName: 'SlotList',

		Columns: [
			{Label: 'Имя', Width: 324},
			{Label: 'Время', Width: 120},
			{Label: 'Тип', Width: 50}
		],

		Fields: [
			{Type: 'PropertyLink', Property: 'SlotName', Width: 328, Data: {cls: 'slot-name'}},
			{Type: 'FightTime', Width: 120},
			{Type: 'FightType', Width: 50},
			{Type: 'Gap', Width: 10},
			{Type: 'TextButton', Width: 70, Label: 'Удалить', Data: {cls: 'slot-delete'},
				BackColor: CanvasColors.Delete}
		],

		EmptyFields: []
	}

	this.SelectedId = null

//---------------------------------------------------------------------------
	this.OnClick = function (x, y, Dataset) {
		var Id = Dataset.id
		switch (Dataset.cls) {
			case 'slot-name':
				this.SelectedId = Id
				var Popup = new AInputWidget({Title: 'Имя для сохраненного боя:'})
				TabSet.CurrentTab.Popup = Popup
				TabSet.CurrentTab.Show()
				Popup.SetValue(this.List.Items[Id].SlotName)
			break

			case 'widget-ack':
				var Item = this.List.Items[this.SelectedId]
				var NewName = TabSet.CurrentTab.Popup.GetValue()
				if (NewName && NewName != Item.SlotName) {
					var Request = {Request: 'slot rename', SlotIndex: this.SelectedId, SlotName: NewName}
					PostRequest(null, Request, 10, function () {
						Item.SlotName = NewName
						this.Render()
					}, null, this)
				} // nobr
			case 'widget-cancel':
				TabSet.CurrentTab.Popup = null
				TabSet.CurrentTab.Show()
			break

			case 'slot-delete':
				var Slot = this.List.Items[Id]
				if (!Slot) return

				if (confirm('Удалить запись "' + Slot.SlotName + '"?')) {
					PostRequest(null, {Request: 'slot delete', SlotIndex: Id}, 10,
						this.RefreshList, null, this)
				}
			break

			default:
				this.Parent.OnClick.call(this, x, y, Dataset)
		}
	}

//---------------------------------------------------------------------------
}
Extend(AFightSlotsWidget, new AFightListWidget())


//---------------------------------------------------------------------------
//---------------------------------------------------------------------------
function ASlotSelectWidget(Fields) {
	this.SetFields(Fields)
	this.IsPopup = true
	this.List = {
		Request: {Request: 'slot list'},
		ItemName: 'SlotList',

		Columns: [
			{Label: 'Имя', Width: 404},
			{Label: 'Время', Width: 120},
			{Label: 'Тип', Width: 50}
		],

		Fields: [
			{Type: 'PropertyLink', Property: 'SlotName', Width: 408, Data: {cls: 'slot-name'}},
			{Type: 'FightTime', Width: 120},
			{Type: 'FightType', Width: 50}
		],

		EmptyFields: [
			{Type: 'Link', Label: '- пусто -', Width: 408, Data: {cls: 'slot-name'}}
		]
	}

//---------------------------------------------------------------------------
}
Extend(ASlotSelectWidget, new AFightListWidget())


//---------------------------------------------------------------------------
//---------------------------------------------------------------------------
function AInputWidget(Fields) {
	this.Title = 'Введите текст'
	this.Max = 255

	this.SetFields(Fields)

	this.IsPopup = true
	this.Height = 145
	this.y = 150
	this.Labels = {
		Title: {x: 5, y: 5, w: 620, h: 25},
		Warning: {x: 5, y: 35, w: 620, h: 25}
	}
	this.Controls = {
		Input: {Type: 'input', x: 10, y: 65, w: 600, h: 20, Max: this.Max, id: 'widget-input'},
		Cancel: {x: 550, y: 105, w: 70, h: 30, Label: 'Отмена', Data: {cls: 'widget-cancel'},
			BackColor: CanvasColors.Button},
		Set: {x: 475, y: 105, w: 70, h: 30, Label: 'Готово', Data: {cls: 'widget-ack'},
			BackColor: CanvasColors.Create}
	}
	this.WidgetControls = {Items: []}

//---------------------------------------------------------------------------
	this.GetValue = function () {
		return document.getElementById('widget-input').value.substr(0, this.Max)
	}

//---------------------------------------------------------------------------
	this.SetValue = function (Value) {
		document.getElementById('widget-input').value = Value
	}

//---------------------------------------------------------------------------
	this.RenderBody = function () {
		var Name

		if (!this.WidgetControls.Items.length) {
			for (Name in this.Controls) {
				this.AddControl(this.Controls[Name])
			}

			this.Labels.Title.Label = this.Title
			this.Labels.Warning.Label = '(не более ' + this.Max + ' символов)'
		}

		for (Name in this.Labels) {
			var Box = this.Labels[Name]
			Canvas.RenderTextBox(Box.Label, Box)
		}

		for (Name in {Set: true, Cancel: true}) {
			Box = this.Controls[Name]
			Canvas.RenderTextButton(Box.Label, Box, Box.BackColor)
		}
	}

//---------------------------------------------------------------------------
	this.Focus = function () {
		document.getElementById('widget-input').focus()
	}

//---------------------------------------------------------------------------
}
Extend(AInputWidget, new AWidget)


//---------------------------------------------------------------------------
//---------------------------------------------------------------------------
function ATextWidget(Fields) {
	this.Max = 1024

	this.SetFields(Fields)

	this.Height = 225
	this.y = 100

	this.Controls = {
		Input: {Type: 'text', x: 10, y: 65, w: 600, h: 100, Max: this.Max, id: 'widget-input'},
		Cancel: {x: 550, y: 185, w: 70, h: 30, Label: 'Отмена', Data: {cls: 'widget-cancel'},
			BackColor: CanvasColors.Button},
		Set: {x: 475, y: 185, w: 70, h: 30, Label: 'Готово', Data: {cls: 'widget-ack'},
			BackColor: CanvasColors.Create}
	}

//---------------------------------------------------------------------------
}
Extend(ATextWidget, new AInputWidget)

//---------------------------------------------------------------------------
//---------------------------------------------------------------------------
function ADebugSelectWidget(Fields) {
	this.SnakeColors = ['#f99', '#ee6', '#6e6', '#9df']
	this.Snakes = []

	this.SetFields(Fields)

	this.IsPopup = true
	this.Width = 54
	this.Height = 98

	this.ControlX = 3
	this.ControlY = 3
	this.ControlHeight = 19

//---------------------------------------------------------------------------
	this.RenderBody = function () {
		var Box = {x: this.ControlX, y: this.ControlY, w: 48, h: 16}
		Canvas.RenderTextButton('---', Box)
		for (var i = 0; i < 4; i++) {
			Box.y += this.ControlHeight
			if (this.Snakes[i] &&
				(this.Snakes[i].SnakeType == 'B' || this.Snakes[i].PlayerId == Game.Player.PlayerId)
			) {
				Canvas.FillRect(Box, this.SnakeColors[i])
				Canvas.RenderSprite(SnakeSkins.Get(this.Snakes[i].SkinId), Box.x, Box.y)
			}
		}
	}

//---------------------------------------------------------------------------
	;(function () {
		var x = this.ControlX
		var y = this.ControlY
		this.AddControl({x: x, y: y, w: 48, h: 16, Data: {cls: 'debug-none'}})
		for (var i = 0; i < 4; i++) {
			y += this.ControlHeight
			if (this.Snakes[i] &&
				(this.Snakes[i].SnakeType == 'B' || this.Snakes[i].PlayerId == Game.Player.PlayerId)
			) {
				this.AddControl({x: x, y: y, w: 48, h: 16, id: i, Data: {cls: 'debug-snake'}})
			}
		}
	}).call(this)

//---------------------------------------------------------------------------
}
Extend(ADebugSelectWidget, new AWidget)


//---------------------------------------------------------------------------
//---------------------------------------------------------------------------
function ASkinSelectWidget(Fields) {
	this.x = 5
	this.y = 35
	this.SetFields(Fields)

	this.IsPopup = true
	this.ItemX = 18
	this.ItemY = 48
	this.ItemsPerLine = 8
	this.ItemW = 78
	this.ItemH = 32

	this.Label = {x: 18, y: 18, Label: 'Выберите шкуру для змеи:'}
	this.CancelButton = {x: 542, y: this.ItemY, w: 70, h: 30, Label: 'Отмена', Data: {cls: 'widget-cancel'}}

	this.SkinIds = []

//---------------------------------------------------------------------------
	this.RenderBody = function () {
		Canvas.RenderText(this.Label.Label, this.Label)

		var x = this.ItemX
		var y = this.ItemY
		var cnt = 0
		for (var i in this.SkinIds) {
			Canvas.RenderSprite(SnakeSkins.Get(this.SkinIds[i]), x, y)
			cnt++
			if (cnt < this.ItemsPerLine) {
				x += this.ItemW
			} else {
				cnt = 0
				x = this.ItemX
				y += this.ItemH
			}
		}

		Canvas.RenderTextButton(this.CancelButton.Label, this.CancelButton, CanvasColors.Button)
	}

//---------------------------------------------------------------------------
	;(function () {
		this.SkinIds = SnakeSkins.SkinList
		var SkinNames = SnakeSkins.Skins
		var Control = {x: this.ItemX, y: this.ItemY, w: 48, h: 16, Title: '', id: 0, Data: {cls: 'skin'}}
		var cnt = 0
		for (var i in this.SkinIds) {
			Control.id = this.SkinIds[i]
			Control.Title = SkinNames[this.SkinIds[i]]
			this.AddControl(Control)

			Control.x += this.ItemW
			cnt++
			if (cnt == this.ItemsPerLine) {
				cnt = 0
				Control.x = this.ItemX
				Control.y += this.ItemH
			}
		}

		this.CancelButton.y += Math.floor((this.SkinIds.length + this.ItemsPerLine - 1) / this.ItemsPerLine) * this.ItemH
		this.AddControl(this.CancelButton)

		this.Height = this.CancelButton.y + this.CancelButton.h + 18
	}).call(this)

//---------------------------------------------------------------------------
}
Extend(ASkinSelectWidget, new AWidget)