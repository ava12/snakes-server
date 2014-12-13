//---------------------------------------------------------------------------
//---------------------------------------------------------------------------
function AListTab() {
	this.TabWidth = 24
	this.ListX = 0
	this.ListY = 40
	this.ListFields = {Width: 640, Height: 440, BorderColor: null}

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
