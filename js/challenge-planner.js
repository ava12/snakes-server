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
	}

//---------------------------------------------------------------------------
//---------------------------------------------------------------------------
//---------------------------------------------------------------------------
//---------------------------------------------------------------------------
//---------------------------------------------------------------------------
//---------------------------------------------------------------------------
//---------------------------------------------------------------------------
//---------------------------------------------------------------------------
}
Extend(AChallengePlanner, BPageTab)
