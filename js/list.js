function AList() {
	this.TabWidth = 24
	this.Buttons = []
	this.BackColor = ['#eeeeff', '#eeffee']
	this.LinkColor = '#3333ff'
	this.List = {
		Request: {},
		Columns: [], // [{Label:, Width:, Name:?}+]
		Fields: [],
		Buttons: [],
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
	this.RefreshButton = {Label: 'Обновить', Title: 'перезагрузить страницу', Data: {cls: 'list-refresh'}}
	this.TabControls = {Items: []}
	this.ItemHeight = 30
	this.ItemWidth = 620
	this.ItemX = 10
	this.TopY = 40
	this.PageSize = 10

//---------------------------------------------------------------------------
	this.GetItemProperty = function(Item, Name) {
		Name = Name.split('.')
		for(var i = 0; i < Name.length; i++) Item = Item[Name[i]]
		return Item
	}

//---------------------------------------------------------------------------
	this.AddControl = function (Control) {
		this.TabControls.Items.push(Control)
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
		if (!BackColor) BackColor = '#eeeeee'
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
		if (!w) w = Canvas.GetTextMetrics(Text).w
		Canvas.RenderText(Text, ABox(x + 4, y + 1, w, this.ItemHeight - 2), Color, Align)
		return w + 8
	}

//---------------------------------------------------------------------------
	this.RenderPropertyText = function(Item, Index, x, y, Params) {
		var Text = this.GetItemProperty(Item, Params.Property)
		var Width = Params.Width
		if (!Width) Width = Canvas.GetTextMetrics(Text).w
		return this.RenderText(Text, x, y, Width, Params.Align)
	}

//---------------------------------------------------------------------------
	this.RenderPropertyLink = function (Item, Index, x, y, Params) {
		var Text = this.GetItemProperty(Item, Params.Property)
		var Width = Canvas.GetTextMetrics(Text).w
		var Box = Clone(Params)
		Box.x = x
		Box.y = y + 1
		Box.w = Width + 8
		Box.h = this.ItemHeight - 2
		this.RenderText(Text, x, y, Width, null, this.LinkColor)
		if (Box.id == undefined) Box.id = Index
		if (Box.Title  == undefined) Box.Title = Text
		this.AddControl(Box)
		return (Params.Width ? Params.Width : Width) + 8
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
			var Color = this.BackColor[Index % this.BackColor.length]
			Canvas.FillRect(ABox(x, y, this.ItemWidth, this.ItemHeight), Color)
		}
		for (var i = 0; i < Fields.length; i++) {
			x += this.RenderField(Fields[i], Item, Index, x, y)
		}
	}

//---------------------------------------------------------------------------
	this.RenderListButtons = function (y) {
		this.RenderItem(null, this.List.Buttons, y)
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
		if (!List.Page) this.RefreshList()

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
				this.RenderItem(Items[i], List.Fields, y, i)
				y += this.ItemHeight
			}
		}

		x = this.ItemX
		y += 4;
		x += this.RenderTextButton(null, null, x, y, this.RefreshButton)

		if (List.Pages > 1) {
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
	this.RenderBody = function() {
		this.Clear()
		this.TabControls.Items = []
		var y = this.TopY

		if (this.List.Buttons && this.List.Buttons.length) {
			this.RenderListButtons(y)
			y += this.ItemHeight * 1.5
		}

		this.RenderList(y)
		this.RenderControls()
	}

//---------------------------------------------------------------------------
	this.AfterListLoad = function () {}

//---------------------------------------------------------------------------
	this.RefreshList = function () {
		var List = this.List
		var PageSize = this.PageSize
		if (!List.Page) List.Page = 1
		var Request = Clone(List.Request)
		Request.FirstIndex = (List.Page - 1) * PageSize
		Request.Count = PageSize
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
		Request.SortBy = SortBy

		PostRequest(null, Request, 20, function (Response) {
			List.Items = Response[List.ItemName].slice(0, PageSize)
			List.Page = Math.floor(Response.FirstIndex / PageSize) + 1
			List.Pages = Math.floor((Response.TotalCount + PageSize - 1) / PageSize)
			List.SortDesc = (Response.SortBy[0].charAt(0) == '>')
			List.SortName = Response.SortBy[0].substr(1)
			this.AfterListLoad()
			if (this.IsActive()) this.RenderBody()
		}, null, this)
	}

//---------------------------------------------------------------------------
	this.OnCustomClick = function (x, y, Dataset) {}

//---------------------------------------------------------------------------
	this.OnClick = function (x, y, Dataset) {
		var Id = Dataset.id
		var List = this.List

		switch (Dataset.cls) {
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
				this.OnCustomClick(x, y, Dataset)
		}
	}

//---------------------------------------------------------------------------
	this.TabInit = function () {
		this.RefreshList()
	}

//---------------------------------------------------------------------------
}
Extend(AList, BPageTab)

//---------------------------------------------------------------------------
//---------------------------------------------------------------------------
function ARatingList() {
	this.TabTitle = 'Рейтинги'
	this.TabSprite = Sprites.Get('16.Labels.Ratings')
	this.List = {
		Request: {Request: 'ratings'},
		ItemName: 'Ratings',
		SortName: 'Rating',
		SortDesc: true,
		ExtraSort: [['PlayerId', false]],

		Columns: [
			{Label: 'Игрок', Width: 230, Name: 'PlayerName'},
			{Label: 'Рейтинг', Width: 100, Name: 'Rating'},
			{Label: 'Боец', Width: 290}
		],

		Fields: [
			{Type: 'PropertyLink', Width: 230, Property: 'PlayerName', Data: {cls: 'player'}},
			{Type: 'Separator'},
			{Type: 'PropertyText', Width: 84, Property: 'Rating', Align: 'right'},
			{Type: 'Gap', Width: 15},
			{Type: 'Separator'},
			{Type: 'Gap'},
			{Type: 'Skin'},
			{Type: 'Gap'},
			{Type: 'PropertyText', Width: 233, Property: 'SnakeName'}
		],
	}

//---------------------------------------------------------------------------
//---------------------------------------------------------------------------
//---------------------------------------------------------------------------
//---------------------------------------------------------------------------
}
Extend(ARatingList, new AList())


//---------------------------------------------------------------------------
//---------------------------------------------------------------------------
function ABotList() {
	this.Items = Game.OtherSnakes.List
	this.TabTitle = 'Боты'
	this.TabSprite = Sprites.Get('16.X')
	this.Fields = [
		{Type: 'Gap'},
		{Type: 'Skin'},
		{Type: 'PropertyText', Width: 472, Property: 'SnakeName'},
		{Type: 'TextButton', Width: 80, Label: 'Смотреть', BackColor: '#99ccff'},
	]

//---------------------------------------------------------------------------
	this.OnClick = function(x, y, Dataset) {
		var Snake = this.Items[Dataset.id]
		if (!Snake) return

		if (Snake.TabId) TabSet.Select(Snake.TabId)
		else TabSet.Add(new ASnakeViewer(Snake))
	}

//---------------------------------------------------------------------------
	this.Serialize = function() {
		return {Object: 'ABotList', Data: []}
	}

//---------------------------------------------------------------------------
}
Extend(ABotList, new AList())

ABotList.Restore = function() {
	return new ABotList()
}


//---------------------------------------------------------------------------
//---------------------------------------------------------------------------
function ASnakeList() {
	this.Items = Game.MySnakes.List
	this.TabTitle = 'Змеи'
	this.TabSprite = Sprites.Get('16.OwnHead')
	this.Buttons = [
		{Type: 'TextButton', Width: 70, Label: 'Новая', id: 'new', BackColor: '#9f9'},
		{Type: 'TextButton', Width: 70, Label: 'Импорт', id: 'import', BackColor: '#9f9'},
	]
	this.Fields = [
		{Type: 'Gap'},
		{Type: 'Skin'},
		{Type: 'PropertyText', Width: 332, Property: 'SnakeName'},
		{Type: 'TextButton', Width: 120, Label: 'Редактировать', BackColor: '#ff9',
			Data: {cls: 'edit'}},
		{Type: 'TextButton', Width: 90, Label: 'Удалить', BackColor: '#f99',
			Data: {cls: 'delete'}},
	]

//---------------------------------------------------------------------------
	this.TabInit = function() {
		Game.MySnakes.TabId = this.TabId
	}

//---------------------------------------------------------------------------
	this.OnClose = function() {
		Game.MySnakes.TabId = null
		return true
	}

//---------------------------------------------------------------------------
	this.OnClick = function(x, y, Dataset) {
		if (Dataset.id == 'new') {
			TabSet.Add(new ASnakeEditor())
			return
		}

		if (Dataset.id == 'import') {
			Canvas.RenderInput('textarea', 'Импорт змеи', '', function(Dataset) {
				if (!Dataset.value) return

				try {
					var Snake = new ASnake(window.JSON.parse(Dataset.value))
					Snake.SnakeName = ''
					Snake.TabId = null
					TabSet.Add(new ASnakeEditor(Snake))
				}
				catch(e) {
					alert('Некорректный формат данных')
				}
			}, this, true)
			return
		}

		var Snake = this.Items[Dataset.id]
		switch(Dataset.cls) {
			case 'edit': {
				if (Snake.TabId) TabSet.Select(Snake.TabId)
				else TabSet.Add(new ASnakeEditor(Snake))
			break }

			case 'delete': {
				if (confirm('Вы действительно хотите удалить змею "' + Snake.SnakeName + '"?')) {
					if (Snake.TabId) TabSet.Close(Snake.TabId)
					Game.MySnakes.Remove(Snake.SnakeName)
					this.RenderBody()
				}
			}
		}
	}

//---------------------------------------------------------------------------
	this.Serialize = function() {
		return {Object: 'ASnakeList', Data: []}
	}

//---------------------------------------------------------------------------
;(function() {
	if (!window.JSON) delete this.Buttons[1]
}).apply(this)

//---------------------------------------------------------------------------
}
Extend(ASnakeList, new AList())

ASnakeList.Restore = function() {
	return new ASnakeList()
}


//---------------------------------------------------------------------------
//---------------------------------------------------------------------------
function AFightList() {
	this.Items = Game.Fights.List
	this.TabTitle = 'Бои'
	this.TabSprite = Sprites.Get('Attack')
	this.Buttons = [
		{Type: 'TextButton', Width: 100, Label: 'Новый бой', id: 'new', BackColor: '#9f9'},
		{Type: 'TextButton', Width: 100, Label: 'Импорт', id: 'import', BackColor: '#9f9'},
	]
	this.Fields = [
		{Type: 'DateTime', Width: 170},
		{Type: 'SnakeSkin', Index: 0, BackColor: '#ff9999'},
		{Type: 'SnakeSkin', Index: 1, BackColor: '#ffff66'},
		{Type: 'SnakeSkin', Index: 2, BackColor: '#66ee66'},
		{Type: 'SnakeSkin', Index: 3, BackColor: '#99ddff'},
		{Type: 'Gap', Width: 8},
		{Type: 'TextButton', Width: 90, Label: 'Смотреть', BackColor: '#99ccff',
			Data: {cls: 'view'}},
		{Type: 'Gap', Width: 8},
		{Type: 'TextButton', Width: 90, Label: 'Удалить', BackColor: '#ff9999',
			Data: {cls: 'delete'}},
	]

//---------------------------------------------------------------------------
	this.RenderSnakeSkin = function(Item, Index, x, y, Params) {
		var Snake = Item.Snakes[Params.Index]
		if (!Snake) return 56

		var dy = (this.ItemHeight - 16) >> 1
		var Box = {x: x + 4, y: y + dy, w: 48, h: 16, Title: Snake.SnakeName}
		Canvas.FillRect(Box, Params.BackColor)
		Canvas.RenderSprite(SnakeSkins.Get(Snake.SkinId), x + 4, y + dy)
		this.AddControl(Box)
		return 56
	}

//---------------------------------------------------------------------------
	this.RenderDateTime = function(Item, Index, x, y, Params) {
		var dt = new Date(Item.FightTime * 1000)
		var d = [dt.getDate(), dt.getMonth() + 1, dt.getFullYear()]
		var t = [dt.getHours(), dt.getMinutes(), dt.getSeconds()]
		for(var i in t) if (t[i] < 10) t[i] = '0' + t[i]
		dt = d.join('.') + ' ' + t.join(':')
		Canvas.RenderText(dt, ABox(x + 4, y + 1, Params.Width, this.ItemHeight - 2))
		return Params.Width + 8
	}

//---------------------------------------------------------------------------
	this.TabInit = function() {
		Game.Fights.TabId = this.TabId
	}

//---------------------------------------------------------------------------
	this.OnClose = function() {
		Game.Fights.TabId = null
		return true
	}

//---------------------------------------------------------------------------
	this.OnClick = function(x, y, Dataset) {
		if (Dataset.id == 'new') {
			TabSet.Add(new AFightPlanner())
			return
		}

		if (Dataset.id == 'import') {
			Canvas.RenderInput('textarea', 'Импорт боя', '', function(Dataset, Context) {
				if (!Dataset.value) return

				try {
					var Fight = new AFight(window.JSON.parse(Dataset.value))
					Fight.SlotIndex = null
					Fight.TabId = null
					TabSet.Add(new AFightViewer(Fight))
				}
				catch(e) {
					alert('Некорректный формат данных')
				}
			}, this, true)
			return
		}

		var Fight = this.Items[Dataset.id]
		switch(Dataset.cls) {
			case 'view': {
				if (Fight.TabId) TabSet.Select(Fight.TabId)
				else TabSet.Add(new AFightViewer(Fight))
			break }

			case 'delete': {
				if (confirm('Вы действительно хотите удалить этот бой?')) {
					//if (Fight.TabId) TabSet.Close(Fight.TabId)
					Game.Fights.Remove(this.Items[Dataset.id])
					this.RenderBody()
				}
			break }
		}

//		if (Snake.TabId) TabSet.Select(Snake.TabId)
//		else TabSet.Add(new ASnakeEditor(Snake))
	}

//---------------------------------------------------------------------------
	this.Serialize = function() {
		return {Object: 'AFightList', Data: []}
	}

//---------------------------------------------------------------------------
;(function() {
	if (!window.JSON) delete this.Buttons[1]
}).apply(this)

//---------------------------------------------------------------------------
}
Extend(AFightList, new AList())

AFightList.Restore = function() {
	return new AFightList()
}

