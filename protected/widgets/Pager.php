<?php

class Pager extends CWidget {
	public $pagination;

	public function run() {
		if (!$this->pagination) return;

		$pageCount = $this->pagination->pageCount;
		if ($pageCount <= 1) return;

		$currentPage = $this->pagination->currentPage + 1;
		echo "Страница: \r\n";
		for ($page = 1; $page <= $pageCount; $page++) {
			if ($page == $currentPage) echo "<strong>$page</strong>\r\n";
			else echo "<a href=\"?page=$page\">$page</a>\r\n";
		}
	}
}