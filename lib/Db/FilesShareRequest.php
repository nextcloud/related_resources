<?php

declare(strict_types=1);


/**
 * SPDX-FileCopyrightText: 2022 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */


namespace OCA\RelatedResources\Db;

use OCA\RelatedResources\Exceptions\FilesShareNotFoundException;
use OCA\RelatedResources\Model\FilesShare;
use OCA\RelatedResources\Tools\Db\ExtendedQueryBuilder;
use OCA\RelatedResources\Tools\Exceptions\InvalidItemException;
use OCA\RelatedResources\Tools\Exceptions\RowNotFoundException;
use OCP\DB\QueryBuilder\IQueryBuilder;
use OCP\Share\IShare;

class FilesShareRequest extends CoreQueryBuilder {
	protected function getFilesShareSelectSql(): ExtendedQueryBuilder {
		$qb = $this->getQueryBuilder();
		$qb->generateSelect(self::TABLE_FILES_SHARE, self::EXTERNAL_TABLES[self::TABLE_FILES_SHARE]);

		return $qb;
	}

	/**
	 * @throws FilesShareNotFoundException
	 */
	public function getItemFromRequest(ExtendedQueryBuilder $qb): FilesShare {
		/** @var FilesShare $share */
		try {
			$share = $qb->asItem(FilesShare::class);
		} catch (InvalidItemException|RowNotFoundException $e) {
			throw new FilesShareNotFoundException();
		}

		return $share;
	}

	/**
	 * @return list<FilesShare>
	 */
	public function getItemsFromRequest(ExtendedQueryBuilder $qb): array {
		return $qb->asItems(FilesShare::class);
	}
	/**
	 * @param list<int> $itemIds
	 *
	 * @return list<FilesShare>
	 */
	public function getSharesByItemIds(array $itemIds): array {
		$result = [];
		foreach (array_chunk($itemIds, 1000) as $chunk) {
			$qb = $this->getFilesShareSelectSql();
			$qb->andWhere($qb->expr()->in($qb->getDefaultSelectAlias() . '.file_source', $qb->createNamedParameter($chunk, IQueryBuilder::PARAM_INT_ARRAY)));

			$result = array_merge($result, $this->getItemsFromRequest($qb));
		}
		return $result;
	}

	/**
	 * @return list<FilesShare>
	 */
	public function getSharesToGroup(string $groupName): array {
		$qb = $this->getFilesShareSelectSql();
		$qb->andWhere($qb->expr()->eq($qb->getDefaultSelectAlias() . '.share_type', $qb->createNamedParameter(IShare::TYPE_GROUP, IQueryBuilder::PARAM_INT)))
			->andWhere($qb->expr()->eq($qb->getDefaultSelectAlias() . '.share_with', $qb->createNamedParameter($groupName)));

		return $this->getItemsFromRequest($qb);
	}

	/**
	 * @return list<FilesShare>
	 */
	public function getSharesToUser(string $userId): array {
		$qb = $this->getFilesShareSelectSql();
		$qb->andWhere($qb->expr()->eq($qb->getDefaultSelectAlias() . '.share_type', $qb->createNamedParameter(IShare::TYPE_USER, IQueryBuilder::PARAM_INT)))
			->andWhere($qb->expr()->eq($qb->getDefaultSelectAlias() . '.share_with', $qb->createNamedParameter($userId)));

		return $this->getItemsFromRequest($qb);
	}
}
