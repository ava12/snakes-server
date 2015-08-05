function AChallengePlanner(Fight) {
	this.TabTitle = 'вызов'
	this.TabSprite = Sprites.Get('Fight')
	this.TabList = 'Unique'
	this.TabKey = 'Challenge'

	if (Fight instanceof AFight) {
		if (Fight.FightId) return new AFightViewer(Fight)

		this.Fight = Fight
	} else this.Fight = new AFight()
	this.Fight.FightType = 'challenge'

	this.ShowWidget = false
	this.Widget = new ARatingListWidget({y: 30, IsPopup: true})
	this.PlayerListColors = ['#f99', '#ee6', '#6e6', '#9df']
	this.OtherPlayers = []
	this.PlayerIndex = 0

	this.TabControls = {Items: {
		PlayerButtons: {Items: [
			{x: 20, y: 71, w: 620, h: 28, Data: {cls: 'change', id: 0}},
			{x: 20, y: 101, w: 620, h: 28, Data: {cls: 'change', id: 1}},
			{x: 20, y: 131, w: 620, h: 28, Data: {cls: 'change', id: 2}}
		]},
		RunButton: {x: 275, y: 170, w: 70, h: 30, Label: 'В бой!',
			BackColor: CanvasColors.Create, Data: {cls: 'run'}}
	}}
	this.ListBox = {x: 10, y: 40, w: 620, h: 30}
	this.ListFields = {
		PlayerName : {x: 20, y: 41, w: 230, h: 28},
		Rating: {x: 250, y: 41, w: 70, h: 28},
		Skin: {x: 325, y: 47, w: 48, h: 16},
		SnakeName: {x: 380, y: 41, w: 220, h: 28}
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
	this.RenderItem = function (Player, Index, Top) {
		Canvas.FillRect(TranslatedBox(this.ListBox, 0, Top), this.PlayerListColors[Index])
		if (!Player) return

		var Names = {PlayerName: true, Rating: true, SnakeName: true}
		for (var Name in Names) {
			Canvas.RenderText(Player[Name], TranslatedBox(this.ListFields[Name], 0, Top))
		}
		var Box = this.ListFields.Skin
		Canvas.RenderSprite(SnakeSkins.Get(Player.SkinId), Box.x, Box.y + Top)
	}

//---------------------------------------------------------------------------
	this.RenderBody = function () {
		this.RenderItem(Game.Player, 0, 0)
		var ItemHeight = this.ListBox.h
		for (var i = 0, Top = ItemHeight; i < 3; i++, Top += ItemHeight) {
			this.RenderItem(this.OtherPlayers[i], i + 1, Top)
		}

		var Button = this.TabControls.Items.RunButton
		Canvas.RenderTextButton(Button.Label, Button, Button.BackColor)

		if (this.ShowWidget) this.Widget.Render()
	}

//---------------------------------------------------------------------------
	this.RunFight = function () {
		var Request = {Request: 'fight challenge', PlayerIds: []}
		for (var i = 0; i < 3; i++) {
			if (this.OtherPlayers[i]) {
				Request.PlayerIds[i] = this.OtherPlayers[i].PlayerId
			} else {
				alert('Необходимо выбрать трех соперников')
				return
			}
		}

		PostRequest(null, Request, 10, function (Response) {
			this.UnregisterTab()
			TabSet.Replace(this, new AFightViewer(new AFight(Response)))
		}, null, this)
	}

//---------------------------------------------------------------------------
	this.SetPlayer = function (Index, Player) {
		this.OtherPlayers[Index] = Player
		this.Show()
	}

//---------------------------------------------------------------------------
	this.OnClick = function (x, y, Dataset) {
		var Id = Dataset.id

		switch (Dataset.cls) {
			case 'change':
				this.PlayerIndex = Id
				this.ShowWidget = true
				this.Show()
			break

			case 'list-cancel':
				this.ShowWidget = false
				this.Show()
			break

			case 'list-challenge':
			case 'list-player':
				this.ShowWidget = false
				var Player = this.Widget.List.Items[Id]
				if (this.CanChallenge(Player)) {
					this.SetPlayer(this.PlayerIndex, Player)
				}
				this.Show()
			break

			case 'run':
				this.RunFight()
			break

			default:
				if (this.ShowWidget) this.Widget.OnClick(x, y, Dataset)
				else alert('не реализовано')
		}
	}

//---------------------------------------------------------------------------
	this.CanChallenge = function (Player) {
		if (Player.PlayerId == Game.Player.PlayerId) return false

		for (var i in this.OtherPlayers) {
			if (this.OtherPlayers[i].PlayerId == Player.PlayerId) return false
		}

		return true
	}

//---------------------------------------------------------------------------
	this.AddPlayer = function (Player) {
		if (!this.CanChallenge(Player)) return false

		for (var i = 0; i < 3; i++) {
			if (!this.OtherPlayers[i]) {
				this.SetPlayer(i, Player)
				return true
			}
		}

		return false
	}

//---------------------------------------------------------------------------
	this.RenderControls = function () {
		Canvas.RenderHtml('controls', Canvas.MakeControlHtml(this.ShowWidget ? this.Widget.WidgetControls : this.TabControls))
	}

//---------------------------------------------------------------------------
}
Extend(AChallengePlanner, BPageTab)
