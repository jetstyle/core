<?php
interface SyncModelInterface {

	public function syncItem($handler, $item);
	public function getUSN();
	public function deleteOutdated($usn);
}
?>