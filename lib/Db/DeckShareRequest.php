<?php

declare(strict_types=1);


/**
 * Nextcloud - Related Resources
 *
 * This file is licensed under the Affero General Public License version 3 or
 * later. See the COPYING file.
 *
 * @author Maxence Lange <maxence@artificial-owl.com>
 * @copyright 2022
 * @license GNU AGPL version 3 or any later version
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 */


namespace OCA\RelatedResources\Db;


use OCA\RelatedResources\Model\DeckShare;
use OCP\Share\IShare;


class DeckShareRequest extends DeckShareRequestBuilder {

	/**
	 * @param int $itemId
	 *
	 * @return DeckShare[]
	 */
	public function getSharesByItemId(int $itemId): array {
		$qb = $this->getDeckShareSelectSql();
		$qb->limitInt('board_id', $itemId);

		return $this->getItemsFromRequest($qb);
	}


	/**
	 * @param string $singleId
	 *
	 * @return DeckShare[]
	 */
	public function getSharesToCircle(string $singleId): array {
		$qb = $this->getDeckShareSelectSql();
		$qb->limitInt('type', IShare::TYPE_CIRCLE);
		$qb->limit('participant', $singleId);

		$qb->generateSelectAlias(self::$externalTables[self::TABLE_DECK_BOARD], 'db', 'db');
		$qb->innerJoin(
			$qb->getDefaultSelectAlias(), self::TABLE_DECK_BOARD, 'db',
			$qb->expr()->eq($qb->getDefaultSelectAlias() . '.board_id', 'db.id')
		);

		return $this->getItemsFromRequest($qb);
	}


	/**
	 * @param string $groupName
	 *
	 * @return DeckShare[]
	 */
	public function getSharesToGroup(string $groupName): array {
		$qb = $this->getDeckShareSelectSql();
		$qb->limitInt('type', IShare::TYPE_GROUP);
		$qb->limit('participant', $groupName);

		$qb->generateSelectAlias(self::$externalTables[self::TABLE_DECK_BOARD], 'db', 'db');
		$qb->innerJoin(
			$qb->getDefaultSelectAlias(), self::TABLE_DECK_BOARD, 'db',
			$qb->expr()->eq($qb->getDefaultSelectAlias() . '.board_id', 'db.id')
		);

		return $this->getItemsFromRequest($qb);
	}


	/**
	 * @param string $userName
	 *
	 * @return DeckShare[]
	 */
	public function getSharesToUser(string $userName): array {
		$qb = $this->getDeckShareSelectSql();
		$qb->limitInt('type', IShare::TYPE_USER);
		$qb->limit('participant', $userName);

		$qb->generateSelectAlias(self::$externalTables[self::TABLE_DECK_BOARD], 'db', 'db');
		$qb->innerJoin(
			$qb->getDefaultSelectAlias(), self::TABLE_DECK_BOARD, 'db',
			$qb->expr()->eq($qb->getDefaultSelectAlias() . '.board_id', 'db.id')
		);

		return $this->getItemsFromRequest($qb);
	}
}
