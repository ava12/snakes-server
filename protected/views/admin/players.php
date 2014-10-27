<h1>Список игроков</h1>
<table class="list-table" border="1" cellspacing="0">
<tr><th>Ид<th>Логин<th>Имя<th>Рейтинг<th>Зарегистрирован<th>Группы</tr>
<?php

$this->pageTitle = 'администрирование: игроки';

foreach ($provider->data as $player) {
	$id = $player->id;
	$login = htmlspecialchars($player->login);
	$name = htmlspecialchars($player->name);
	$rating = $player->rating;
	$registered = date('d.m.y H:i', $player->registered);
	$groups = array();
	if (!$player->hasGroup(Player::GROUP_PLAYER)) $groups[] = '!!!';
	if ($player->hasGroup(Player::GROUP_ADMIN)) $groups[] = 'админ';
	$groups = implode(', ', $groups);

	echo "<tr><td><a href=\"" . BASE_URL . "admin/player/$id\">$id" .
		"<td>$login<td>$name<td class=\"ar\">$rating<td>$registered<td>$groups</tr>\r\n";
}

?>
</table>
<?php $this->widget('application.widgets.Pager', array('pagination' => $provider->pagination)); ?>
