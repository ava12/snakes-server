function APlayer(Id) {
	this.Player = {
		Id: Id,
		Name: null,
		Rating: null,
		Snakes: []
	}

	this.IsMe = (Id == Game.Player.Id)
	this.TabSprite = Sprites.Get(this.IsMe ? '16.Labels.Me' : '16.Labels.Player')

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
	this.LoadPlayer = function () {
		var Request = {Request: 'player info', PlayerId: this.PlayerId}
		PostRequest(null, Request, 20, function (Data) {
			var KeyMap = {PlayerId: 'Id', PlayerName: 'Name', Rating: 'Rating', PlayerSnakes: 'Snakes'}
			for (var n in KeyMap) this.Player[KeyMap[n]] = Data[n]
			if (this.IsActive()) {
				this.Clear()
				this.RenderBody()
			}
		}, null, this)
	}

//---------------------------------------------------------------------------
//---------------------------------------------------------------------------
//---------------------------------------------------------------------------
//---------------------------------------------------------------------------
//---------------------------------------------------------------------------
//---------------------------------------------------------------------------
}
Extend(APlayer, BPageTab)