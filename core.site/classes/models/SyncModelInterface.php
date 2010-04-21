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
	 * ƒл€ упрощени€ контрол€ над устаревшими данными предлагаетс€ использовать отдельное поле.
	 * ѕеред началом импорта мы выбираем из базы максимальное значение этого пол€ и увеличиваем значение на 1.
	 * ƒл€ всех добавленных и измененных записей мы обновл€ем поле этим значением.
	 * ¬се строки со меньшим значением этого пол€ удал€ем функцией deleteOutdated
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