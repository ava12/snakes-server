var SnakeSkins = {
	Image: GetImage('img-skins'),

	SkinList: [],
	Skins: {},

//---------------------------------------------------------------------------
	Load: function () {
		PostRequest(null, {Request: 'skin list'}, 10, function (Data) {
			this.SkinList = []
			this.Skins = {}

			for (var i in Data.SkinList) {
				var Item = Data.SkinList[i]
				this.SkinList.push(Item.SkinId)
				this.Skins[Item.SkinId] = Item.SkinName
			}
		}, null, this, 'game-wait')
	},

//---------------------------------------------------------------------------
	Get: function (Index) {
		return {
			Image: this.Image, Title: this.Skins[Index],
			x: 0, y: (Number(Index) - 1) << 4, w: 48, h: 16
		}
	},

//---------------------------------------------------------------------------
}

function ASkin(Index) {
	this.Image = new Image()
	this.Image.src = 'img/16/skin' + Index + '.png'
	this.TypeX = {h: 0, b: 16, r: 32, l: 48, t: 64}

//---------------------------------------------------------------------------
	this.Get = function(Type, Dir) {
		return {
			Image: this.Image,
			x: this.TypeX[Type.charAt(0).toLowerCase()],
			y: Dir << 4,
			w: 16,
			h: 16,
		}
	}

//---------------------------------------------------------------------------
}