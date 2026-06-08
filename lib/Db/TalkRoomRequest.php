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
use OCA\RelatedResources\Tools\Db\ExtendedQueryBuilder;
use OCA\RelatedResources\Tools\Exceptions\InvalidItemException;
use OCA\RelatedResources\Tools\Exceptions\RowNotFoundException;

class TalkRoomRequest extends CoreQueryBuilder {
	protected function getTalkRoomSelectSql(): ExtendedQueryBuilder {
		$qb = $this->getQueryBuilder();
		$qb->generateSelect(self::TABLE_TALK_ROOM, self::EXTERNAL_TABLES[self::TABLE_TALK_ROOM]);

		return $qb;
	}

	protected function getActorSelectSql(): ExtendedQueryBuilder {
		$qb = $this->getQueryBuilder();
		$qb->generateSelect(self::TABLE_TALK_ATTENDEE, self::EXTERNAL_TABLES[self::TABLE_TALK_ATTENDEE]);

		return $qb;
	}

	/**
	 * @throws TalkDataNotFoundException
	 */
	public function getRoomFromRequest(ExtendedQueryBuilder $qb): TalkRoom {
		/** @var TalkRoom $room */
		try {
			$room = $qb->asItem(TalkRoom::class);
		} catch (InvalidItemException|RowNotFoundException $e) {
			throw new TalkDataNotFoundException();
		}

		return $room;
	}

	/**
	 * @return TalkRoom[]
	 */
	public function getRoomsFromRequest(ExtendedQueryBuilder $qb): array {
		return $qb->asItems(TalkRoom::class);
	}

	/**
	 * @return TalkActor[]
	 */
	public function getActorsFromRequest(ExtendedQueryBuilder $qb): array {
		return $qb->asItems(TalkActor::class);
	}
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
