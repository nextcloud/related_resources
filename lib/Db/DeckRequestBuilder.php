<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2022 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\RelatedResources\Db;

use OCA\RelatedResources\Exceptions\DeckDataNotFoundException;
use OCA\RelatedResources\Model\DeckBoard;
use OCA\RelatedResources\Model\DeckShare;
use OCA\RelatedResources\Tools\Exceptions\InvalidItemException;
use OCA\RelatedResources\Tools\Exceptions\RowNotFoundException;

class DeckRequestBuilder extends CoreQueryBuilder {
	/**
	 * @return CoreRequestBuilder
	 */
	protected function getDeckBoardSelectSql(): CoreRequestBuilder {
		$qb = $this->getQueryBuilder();
		$qb->generateSelect(self::TABLE_DECK_BOARD, self::$externalTables[self::TABLE_DECK_BOARD]);

		return $qb;
	}

	protected function getDeckShareSelectSql(): CoreRequestBuilder {
		$qb = $this->getQueryBuilder();
		$qb->generateSelect(self::TABLE_DECK_SHARE, self::$externalTables[self::TABLE_DECK_SHARE]);

		return $qb;
	}

	/**
	 * @param CoreRequestBuilder $qb
	 *
	 * @return DeckBoard
	 * @throws DeckDataNotFoundException
	 */
	public function getDeckFromRequest(CoreRequestBuilder $qb): DeckBoard {
		/** @var DeckBoard $deck */
		try {
			$deck = $qb->asItem(DeckBoard::class);
		} catch (InvalidItemException|RowNotFoundException $e) {
			throw new DeckDataNotFoundException();
		}

		return $deck;
	}

	/**
	 * @param CoreRequestBuilder $qb
	 *
	 * @return DeckBoard[]
	 */
	public function getDecksFromRequest(CoreRequestBuilder $qb): array {
		return $qb->asItems(DeckBoard::class);
	}

	/**
	 * @param CoreRequestBuilder $qb
	 *
	 * @return DeckShare[]
	 */
	public function getSharesFromRequest(CoreRequestBuilder $qb): array {
		return $qb->asItems(DeckShare::class);
	}
}
