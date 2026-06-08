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
use OCA\RelatedResources\Tools\Db\ExtendedQueryBuilder;
use OCA\RelatedResources\Tools\Exceptions\InvalidItemException;
use OCA\RelatedResources\Tools\Exceptions\RowNotFoundException;
use OCP\DB\QueryBuilder\IQueryBuilder;
use OCP\Share\IShare;

class DeckRequest extends CoreQueryBuilder {
	protected function getDeckBoardSelectSql(): ExtendedQueryBuilder {
		$qb = $this->getQueryBuilder();
		$qb->generateSelect(self::TABLE_DECK_BOARD, self::EXTERNAL_TABLES[self::TABLE_DECK_BOARD]);

		return $qb;
	}

	protected function getDeckShareSelectSql(): ExtendedQueryBuilder {
		$qb = $this->getQueryBuilder();
		$qb->generateSelect(self::TABLE_DECK_SHARE, self::EXTERNAL_TABLES[self::TABLE_DECK_SHARE]);

		return $qb;
	}

	/**
	 * @return DeckBoard
	 * @throws DeckDataNotFoundException
	 */
	public function getDeckFromRequest(ExtendedQueryBuilder $qb): DeckBoard {
		/** @var DeckBoard $deck */
		try {
			$deck = $qb->asItem(DeckBoard::class);
		} catch (InvalidItemException|RowNotFoundException $e) {
			throw new DeckDataNotFoundException();
		}

		return $deck;
	}

	/**
	 * @return DeckBoard[]
	 */
	public function getDecksFromRequest(ExtendedQueryBuilder $qb): array {
		return $qb->asItems(DeckBoard::class);
	}

	/**
	 * @return DeckShare[]
	 */
	public function getSharesFromRequest(ExtendedQueryBuilder $qb): array {
		return $qb->asItems(DeckShare::class);
	}
	/**
	 * @param int $itemId
	 *
	 * @return DeckBoard
	 * @throws DeckDataNotFoundException
	 */
	public function getBoardById(int $itemId): DeckBoard {
		$qb = $this->getDeckBoardSelectSql();
		$qb->limitInt('id', $itemId);

		return $this->getDeckFromRequest($qb);
	}


	/**
	 * @param int $boardId
	 *
	 * @return DeckShare[]
	 */
	public function getSharesByBoardId(int $boardId): array {
		$qb = $this->getDeckShareSelectSql();
		$qb->limitInt('board_id', $boardId);

		return $this->getSharesFromRequest($qb);
	}


	/**
	 * @param string $singleId
	 *
	 * @return DeckBoard[]
	 */
	public function getDeckAvailableToCircle(string $singleId): array {
		$qb = $this->getDeckBoardSelectSql();
		$qb->innerJoin(
			$qb->getDefaultSelectAlias(), self::TABLE_DECK_SHARE, 'ds',
			$qb->expr()->eq($qb->getDefaultSelectAlias() . '.id', 'ds.board_id')
		);

		$qb->limitInt('type', IShare::TYPE_CIRCLE, 'ds');
		$qb->limit('participant', $singleId, 'ds');

		return $this->getDecksFromRequest($qb);
	}


	/**
	 * @param string $groupName
	 *
	 * @return DeckBoard[]
	 */
	public function getDeckAvailableToGroup(string $groupName): array {
		$qb = $this->getDeckBoardSelectSql();
		$qb->innerJoin(
			$qb->getDefaultSelectAlias(), self::TABLE_DECK_SHARE, 'ds',
			$qb->expr()->eq($qb->getDefaultSelectAlias() . '.id', 'ds.board_id')
		);

		$qb->andWhere($qb->expr()->eq('ds.type', $qb->createNamedParameter(IShare::TYPE_GROUP, IQueryBuilder::PARAM_INT)));
		$qb->andWhere($qb->expr()->eq('ds.participant', $qb->createNamedParameter($groupName)));

		return $this->getDecksFromRequest($qb);
	}


	/**
	 * @param string $userName
	 *
	 * @return DeckBoard[]
	 */
	public function getDeckAvailableToUser(string $userName): array {
		$qb = $this->getDeckBoardSelectSql();
		$qb->innerJoin(
			$qb->getDefaultSelectAlias(), self::TABLE_DECK_SHARE, 'ds',
			$qb->expr()->eq($qb->getDefaultSelectAlias() . '.id', 'ds.board_id')
		);

		$qb->limitInt('type', IShare::TYPE_USER, 'ds');
		$qb->limit('participant', $userName, 'ds');

		return $this->getDecksFromRequest($qb);
	}
}
