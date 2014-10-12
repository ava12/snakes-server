<h1>Рекорды</h1>
<table class="list-table" cellspacing="0" border="1">
<tr><th>Игрок<th>Рекорд<th>Боец</tr>
<?php
foreach ($provider->data as $player) {
	$name = htmlspecialchars($player->name);
	$rating = $player->rating;
	$skin = mt_rand(1, 6);
	$fighter = 'боец ' . $name;
	echo "<tr><td>$name<td class=\"ar\">$rating<td><span class=\"skin skin$skin\"></span>$fighter</tr>\r\n";
}
?>
</table>

<?php $this->widget('application.widgets.Pager', array('pagination' => $provider->pagination)); ?>