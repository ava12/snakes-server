<?php
$this->pageTitle = '';

if ($this->player and $this->player->isConfirmed()) {
?>
<a href="<?= BASE_URL ?>game" class="fl-right">Играть</a>
<?php } ?>
<br>
<h1>Рекорды</h1>
<table class="list-table ratings" cellspacing="0" border="1">
<col width="2*">
<col width="1*">
<col width="3*">
<tr><th>Игрок<th>Рекорд<th>Боец</tr>
<?php
foreach ($provider->data as $player) {
	$name = htmlspecialchars($player->name);
	$rating = $player->rating;
	$snake = $player->fighter;
	$skin = $snake->skin_id;
	$fighter = htmlspecialchars($snake->name);
	echo "<tr><td>$name<td class=\"ar\">$rating<td><span class=\"skin skin$skin\"></span>$fighter</tr>\r\n";
}
?>
</table>

<?php $this->widget('application.widgets.Pager', array('pagination' => $provider->pagination)); ?>