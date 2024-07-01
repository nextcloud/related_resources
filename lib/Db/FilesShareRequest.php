<?php

declare(strict_types=1);


/**
 * SPDX-FileCopyrightText: 2022 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */


namespace OCA\RelatedResources\Db;

use OCA\RelatedResources\Model\FilesShare;
use OCP\Share\IShare;

class FilesShareRequest extends FilesShareRequestBuilder {
	/**
	 * @param int $itemId
	 *
	 * @return FilesShare[]
	 */
	public function getSharesByItemId(int $itemId): array {
		$qb = $this->getFilesShareSelectSql();
		$qb->limitInt('file_source', $itemId);

		return $this->getItemsFromRequest($qb);
	}


	/**
	 * @param array $itemIds
	 *
	 * @return FilesShare[]
	 */
	public function getSharesByItemIds(array $itemIds): array {
		$qb = $this->getFilesShareSelectSql();
		$qb->limitInArray('file_source', $itemIds);

		return $this->getItemsFromRequest($qb);
	}


	/**
	 * @param string $singleId
	 *
	 * @return FilesShare[]
	 */
	public function getSharesToCircle(string $singleId): array {
		$qb = $this->getFilesShareSelectSql();
		$qb->limitInt('share_type', IShare::TYPE_CIRCLE);
		$qb->limit('share_with', $singleId);

		return $this->getItemsFromRequest($qb);
	}

	/**
	 * @param string $groupName
	 *
	 * @return FilesShare[]
	 */
	public function getSharesToGroup(string $groupName): array {
		$qb = $this->getFilesShareSelectSql();
		$qb->limitInt('share_type', IShare::TYPE_GROUP);
		$qb->limit('share_with', $groupName);

		return $this->getItemsFromRequest($qb);
	}


	/**
	 * @param string $userId
	 *
	 * @return FilesShare[]
	 */
	public function getSharesToUser(string $userId): array {
		$qb = $this->getFilesShareSelectSql();
		$qb->limitInt('share_type', IShare::TYPE_USER);
		$qb->limit('share_with', $userId);

		return $this->getItemsFromRequest($qb);
	}
}
