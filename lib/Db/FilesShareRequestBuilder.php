<?php

declare(strict_types=1);


/**
 * SPDX-FileCopyrightText: 2022 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */


namespace OCA\RelatedResources\Db;

use OCA\RelatedResources\Exceptions\FilesShareNotFoundException;
use OCA\RelatedResources\Model\FilesShare;
use OCA\RelatedResources\Tools\Exceptions\InvalidItemException;
use OCA\RelatedResources\Tools\Exceptions\RowNotFoundException;

class FilesShareRequestBuilder extends CoreQueryBuilder {
	/**
	 * @return CoreRequestBuilder
	 */
	protected function getFilesShareSelectSql(): CoreRequestBuilder {
		$qb = $this->getQueryBuilder();
		$qb->generateSelect(self::TABLE_FILES_SHARE, self::$externalTables[self::TABLE_FILES_SHARE]);

		return $qb;
	}


	/**
	 * @param CoreRequestBuilder $qb
	 *
	 * @return FilesShare
	 * @throws FilesShareNotFoundException
	 */
	public function getItemFromRequest(CoreRequestBuilder $qb): FilesShare {
		/** @var FilesShare $share */
		try {
			$share = $qb->asItem(FilesShare::class);
		} catch (InvalidItemException|RowNotFoundException $e) {
			throw new FilesShareNotFoundException();
		}

		return $share;
	}

	/**
	 * @param CoreRequestBuilder $qb
	 *
	 * @return FilesShare[]
	 */
	public function getItemsFromRequest(CoreRequestBuilder $qb): array {
		return $qb->asItems(FilesShare::class);
	}
}
