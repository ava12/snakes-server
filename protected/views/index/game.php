<!DOCTYPE html>
<html>
<head>
<meta charset="utf-8">

<title>Змеи | игра</title>
<link rel="icon" type="image/vnd.microsoft.icon" href="<?= BASE_URL ?>favicon.ico">

<style>
* { margin: 0px; padding: 0px; }
html, body {
	position: absolute; width: 100%; height: 100%;
	font-family: Verdana, sans-serif; font-size: 18px;
}
h3 { font-size: 110%; margin: 8px; }
button, input[type=button] { cursor: pointer; }

#canvas-frame { margin: 1em auto 0px; background: #ddd; border: 2px solid; }
#canvas-frame, #canvas { width: 640px; height: 480px; position: relative; }
#control-frame>div, #load-screen {
	position: absolute; width: 100%; height: 100%; left: 0px; top: 0px;
}
#control-frame>div>*, #load-screen>div { position: absolute; }
#control-frame>div>div {
	background-image: url(../img/empty.png); background-repeat: repeat;
}
#control-frame #controls div, #tab_controls div, #tabs div  { cursor: pointer; }
#control-frame #tab_controls { height: 24px !important; overflow: hidden; }
#control-frame #tabs {
	left: 52px; width: 546px; height: 24px !important; overflow: hidden;
}
#snake-map { display: none; width: 112px; height: 112px; }

#control-frame .input {
	background-color: #fff; border: 1px solid;
	font-family: Verdana; font-size: 14px; line-height: 18px;
	position: absolute; width: 600px; height: 390px; left: 19px; top: 45px;
}
#control-frame .input>div { padding: 10px; }
#control-frame .input div.content { padding: 5px; border: 1px solid; }
#control-frame .input input[type=text] { width: 570px; font-family: monospace; }
#control-frame .input textarea { width: 570px; height: 250px; }

.fight-run {
	left: 160px; top: 195px; width: 240px; height: 30px; padding: 30px;
	text-align: center; font-size: 110%;
	background-color: #ffffff; border: 1px solid;
}

.snake-list-frame {
	display: inline-block; width: 49%; vertical-align: top; margin-right: 4px;
}

.snake-list { list-style: none; width: 20em; line-height: 2em; }
.snake-list li { cursor: pointer; }
.snake-list li:nth-child(odd) { background: #fee; }
.snake-list li:nth-child(even) { background: #eff; }
.snake-list li * { vertical-align: middle; }

.skin {
	width: 48px; height: 16px; margin: 2px 4px; border: none;
	display: inline-block; background: url(img/16/skins.png) no-repeat;
}
.skin1 { background-position: 0px 0px; }
.skin2 { background-position: 0px -16px; }
.skin3 { background-position: 0px -32px; }
.skin4 { background-position: 0px -48px; }
.skin5 { background-position: 0px -64px; }
.skin6 { background-position: 0px -80px; }

.debug-list {
	list-style: none; width: 48px; height: 110px; margin: 0px; padding: 4px;
	background-color: #eef; border: 1px solid #000; position: absolute;
}
.debug-list li {
	margin: 0px; padding: 0px; width: 100%; height: 22px; text-align: center;
}
.debug-list .skin { cursor: pointer; margin: 0px; }

.json-wait { display: none; position: absolute; left: 0px; top: 0px; right: 0px; bottom: 0px; }
.json-wait-back { background-color: #000; opacity: 0.5; width: 100%; height: 100%; }
.json-wait-dialog {
	position: absolute; left: 160px; right: 160px; top: 180px; bottom: 180px;
	text-align: center; border: 1px solid; background-color: #fff;
}
.json-wait-dialog div { padding: 1em; }
.json-wait-dialog input { padding: 1ex; }
input, textarea { padding: 0.5ex; }

</style>
<script type="text/javascript">
	var BaseUrl = '<?= BASE_URL ?>';
	var SessionId = '<?= Yii::app()->user->getClientSid() ?>';
</script>
<script type="text/javascript" src="<?= BASE_URL ?>js/util.js"></script>
<script type="text/javascript" src="<?= BASE_URL ?>js/main.js"></script>
</head>

<body>
<!--[if lt ie 9]><div style="color:#CC3333;text-align:center;padding:1ex">
Ваша программа не поддерживает Web-приложения.<br>
Пожалуйста, установите какой-нибудь браузер.
</div><div style="display:none"><![endif]-->

<div id="canvas-frame" onclick="Canvas.OnClick(event)">
<canvas id="canvas" width="640" height="480"></canvas>

<div id="control-frame">

<div id="controls"></div>
<div id="tab_controls"></div>
<div id="tabs"></div>

<div id="json-wait" class="json-wait">
<div class="json-wait-back"></div>
<div class="json-wait-dialog">
<div>
<img alt="" src="<?= BASE_URL ?>img/loading.gif"> Запрос к серверу.<br><br>
<input type="button" value="Отмена" onclick="Ajax.Cancel()">
</div>
</div>
</div>

<div id="game-wait" class="json-wait">
<div class="json-wait-back"></div>
<div class="json-wait-dialog">
<div>
<br>Соединение с сервером... <img alt="" src="<?= BASE_URL ?>img/loading.gif">
</div>
</div>
</div>

</div>

</div>

<canvas id="snake-map" width="112" height="112"></canvas>
<!--[if lt ie 9]></div><![endif]-->

<div style="display:none">
<img alt="" src="<?= BASE_URL ?>img/empty.png">
<img id="img-sprites" alt="" src="<?= BASE_URL ?>img/client/sprites.png">
<img id="img-skins" alt="" src="<?= BASE_URL ?>img/16/skins.png">
<img id="img-tile" alt="" src="<?= BASE_URL ?>img/client/tile.png">
<?php
	for ($i = 1; $i <= 6; $i++) {
		echo '<img alt="" src="' . BASE_URL . "img/16/skin$i.png\">\r\n";
	}
?>
</div>

<?php
	foreach(array(
		'canvas', 'sprites', 'tabs', 'snakes', 'skins',
		'widgets', 'lists', 'player', 'viewer', 'editor',
		'fight-planner', 'challenge-planner', 'fight-viewer',
	) as $name) {
		echo '<script type="text/javascript" src="' . BASE_URL . "js/$name.js\"></script>\r\n";
	}
?>

<script type="text/javascript">

var Canvas = new ACanvas('canvas', {
	controls: 'controls', tab_controls: 'tab_controls', tabs: 'tabs',
})

Canvas.SetFont('verdana', 14, 18)
window.onload = function() {
	Game.Run()
}

//</script>
</body>
</html>