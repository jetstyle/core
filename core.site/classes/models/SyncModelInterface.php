<?php
interface SyncModelInterface {

	/**
	 *
	 *
	 * @param CatalogueSync $handler
	 * @param array $item
	 */
	public function syncItem($handler, $item);

	/**
	 * ��� ��������� �������� ��� ����������� ������� ������������ ������������ ��������� ����.
	 * ����� ������� ������� �� �������� �� ���� ������������ �������� ����� ���� � ����������� �������� �� 1.
	 * ��� ���� ����������� � ���������� ������� �� ��������� ���� ���� ���������.
	 * ��� ������ �� ������� ��������� ����� ���� ������� �������� deleteOutdated
	 *
	 * @return int
	 */
	public function getUSN();

	/**
	 *
	 * @param int $usn
	 */
	public function deleteOutdated($usn);

	/**
	 *
	 */
	public function deleteSyncedItems();
}
?>