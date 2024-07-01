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

class TalkRoomRequest extends TalkRoomRequestBuilder {
	/**
	 * @param string $token
	 *
	 * @return TalkRoom
	 * @throws TalkDataNotFoundException
	 */
	public function getRoomByToken(string $token): TalkRoom {
		$qb = $this->getTalkRoomSelectSql();
		$qb->limit('token', $token);

		return $this->getRoomFromRequest($qb);
	}


	/**
	 * @param string $token
	 *
	 * @return TalkActor[]
	 */
	public function getActorsByToken(string $token): array {
		$qb = $this->getActorSelectSql();
		$qb->innerJoin(
			$qb->getDefaultSelectAlias(), self::TABLE_TALK_ROOM, 'tr',
			$qb->expr()->eq($qb->getDefaultSelectAlias() . '.room_id', 'tr.id')
		);

		$qb->limit('token', $token, 'tr');

		return $this->getActorsFromRequest($qb);
	}


	/**
	 * @param string $singleId
	 *
	 * @return TalkRoom[]
	 */
	public function getRoomsAvailableToCircle(string $singleId): array {
		$qb = $this->getTalkRoomSelectSql();
		$qb->innerJoin(
			$qb->getDefaultSelectAlias(), self::TABLE_TALK_ATTENDEE, 'ta',
			$qb->expr()->eq($qb->getDefaultSelectAlias() . '.id', 'ta.room_id')
		);

		$qb->limit('actor_type', 'circles', 'ta');
		$qb->limit('actor_id', $singleId, 'ta');

		return $this->getRoomsFromRequest($qb);
	}


	/**
	 * @param string $groupName
	 *
	 * @return TalkRoom[]
	 */
	public function getRoomsAvailableToGroup(string $groupName): array {
		$qb = $this->getTalkRoomSelectSql();
		$qb->innerJoin(
			$qb->getDefaultSelectAlias(), self::TABLE_TALK_ATTENDEE, 'ta',
			$qb->expr()->eq($qb->getDefaultSelectAlias() . '.id', 'ta.room_id')
		);

		$qb->limit('actor_type', 'groups', 'ta');
		$qb->limit('actor_id', $groupName, 'ta');

		return $this->getRoomsFromRequest($qb);
	}

	/**
	 * @param string $userName
	 *
	 * @return TalkRoom[]
	 */
	public function getRoomsAvailableToUser(string $userName): array {
		$qb = $this->getTalkRoomSelectSql();
		$qb->innerJoin(
			$qb->getDefaultSelectAlias(), self::TABLE_TALK_ATTENDEE, 'ta',
			$qb->expr()->eq($qb->getDefaultSelectAlias() . '.id', 'ta.room_id')
		);

		$qb->limit('actor_type', 'users', 'ta');
		$qb->limit('actor_id', $userName, 'ta');

		return $this->getRoomsFromRequest($qb);
	}
}
