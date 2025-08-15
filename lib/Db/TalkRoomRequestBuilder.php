<?php

declare(strict_types=1);


/**
 * SPDX-FileCopyrightText: 2022 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */


namespace OCA\RelatedResources\Db;

use OCA\RelatedResources\Exceptions\TalkDataNotFoundException;
use OCA\RelatedResources\Model\TalkActor;
use OCA\RelatedResources\Model\TalkRoom;
use OCA\RelatedResources\Tools\Exceptions\InvalidItemException;
use OCA\RelatedResources\Tools\Exceptions\RowNotFoundException;

class TalkRoomRequestBuilder extends CoreQueryBuilder {
	/**
	 * @return CoreRequestBuilder
	 */
	protected function getTalkRoomSelectSql(): CoreRequestBuilder {
		$qb = $this->getQueryBuilder();
		$qb->generateSelect(self::TABLE_TALK_ROOM, self::$externalTables[self::TABLE_TALK_ROOM]);

		return $qb;
	}


	protected function getActorSelectSql(): CoreRequestBuilder {
		$qb = $this->getQueryBuilder();
		$qb->generateSelect(self::TABLE_TALK_ATTENDEE, self::$externalTables[self::TABLE_TALK_ATTENDEE]);

		return $qb;
	}


	/**
	 * @param CoreRequestBuilder $qb
	 *
	 * @return TalkRoom
	 * @throws TalkDataNotFoundException
	 */
	public function getRoomFromRequest(CoreRequestBuilder $qb): TalkRoom {
		/** @var TalkRoom $room */
		try {
			$room = $qb->asItem(TalkRoom::class);
		} catch (InvalidItemException|RowNotFoundException $e) {
			throw new TalkDataNotFoundException();
		}

		return $room;
	}

	/**
	 * @param CoreRequestBuilder $qb
	 *
	 * @return TalkRoom[]
	 */
	public function getRoomsFromRequest(CoreRequestBuilder $qb): array {
		return $qb->asItems(TalkRoom::class);
	}

	/**
	 * @param CoreRequestBuilder $qb
	 *
	 * @return TalkActor[]
	 */
	public function getActorsFromRequest(CoreRequestBuilder $qb): array {
		return $qb->asItems(TalkActor::class);
	}
}
