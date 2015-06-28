<!DOCTYPE html>
<html>
<head>
<style type="text/css">
</style>
</head>
<body>
<form id="frm" method="post" enctype="multipart/form-data">
<input id="file-input" type="file" name="file" onchange="document.getElementById('frm').submit()">
</form>
<?php

function escape($text) {
	return addcslashes($text, "\0..\x1f'\\");
}

if (!empty($_FILES['file']) and empty($_FILES['file']['error'])) {
	$file = base64_encode(file_get_contents($_FILES['file']['tmp_name']));
	$fileName = (empty($_FILES['file']['name']) ? '' : escape($_FILES['file']['name']));
	$fileType = (empty($_FILES['file']['type']) ? '' : escape($_FILES['file']['type']));
?>

<script>
var FileName = '<?= $fileName ?>'
var FileType = '<?= $fileType ?>'
var FileData = '<?= $file ?>'
if (FileData && window.parent.FileCallback) {
	window.parent.FileCallback(FileName, FileType, FileData)
}
</script>
<?php } ?>
</body>
</html>