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

use OCA\RelatedResources\Model\CalendarShare;

class CalendarShareRequest extends CalendarShareRequestBuilder {
	/**
	 * @param int $itemId
	 *
	 * @return CalendarShare[]
	 */
	public function getSharesByItemId(int $itemId): array {
		$qb = $this->getCalendarShareSelectSql();
		$qb->limit('type', 'calendar');
		$qb->limitInt('resourceid', $itemId);

		return $this->getItemsFromRequest($qb);
	}


	/**
	 * @param string $groupName
	 *
	 * @return CalendarShare[]
	 */
	public function getSharesToCircle(string $singleId): array {
		$qb = $this->getCalendarShareSelectSql();
		$qb->limit('type', 'calendar');
		$qb->limit('principaluri', 'principals/circles/' . $singleId);

		$qb->generateSelectAlias(self::$externalTables[self::TABLE_CALENDARS], 'cl', 'cl');
		$qb->innerJoin(
			$qb->getDefaultSelectAlias(), self::TABLE_CALENDARS, 'cl',
			$qb->expr()->eq($qb->getDefaultSelectAlias() . '.resourceid', 'cl.id')
		);

		$this->linkToCalendarEvents($qb);

		return $this->getItemsFromRequest($qb);
	}


	/**
	 * @param string $groupName
	 *
	 * @return CalendarShare[]
	 */
	public function getSharesToGroup(string $groupName): array {
		$qb = $this->getCalendarShareSelectSql();
		$qb->limit('type', 'calendar');
		$qb->limit('principaluri', 'principals/groups/' . $groupName);

		$qb->generateSelectAlias(self::$externalTables[self::TABLE_CALENDARS], 'cl', 'cl');
		$qb->innerJoin(
			$qb->getDefaultSelectAlias(), self::TABLE_CALENDARS, 'cl',
			$qb->expr()->eq($qb->getDefaultSelectAlias() . '.resourceid', 'cl.id')
		);

		$this->linkToCalendarEvents($qb);

		return $this->getItemsFromRequest($qb);
	}


	/**
	 * @param string $userName
	 *
	 * @return CalendarShare[]
	 */
	public function getSharesToUser(string $userName): array {
		$qb = $this->getCalendarShareSelectSql();
		$qb->limit('type', 'calendar');
		$qb->limit('principaluri', 'principals/users/' . $userName);

		$qb->generateSelectAlias(self::$externalTables[self::TABLE_CALENDARS], 'cl', 'cl');
		$qb->innerJoin(
			$qb->getDefaultSelectAlias(), self::TABLE_CALENDARS, 'cl',
			$qb->expr()->eq($qb->getDefaultSelectAlias() . '.resourceid', 'cl.id')
		);

		$this->linkToCalendarEvents($qb);

		return $this->getItemsFromRequest($qb);
	}


	/**
	 * @param CoreRequestBuilder $qb
	 */
	private function linkToCalendarEvents(CoreRequestBuilder $qb): void {
		// check a range of time around right now for some events
		$qb->generateSelectAlias(self::$externalTables[self::TABLE_CAL_OBJECTS], 'co', 'co');

		$andXEvent = $qb->expr()->andX();
		$andXEvent->add($qb->expr()->eq($qb->getDefaultSelectAlias() . '.resourceid', 'co.calendarid'));
		$andXEvent->add($qb->exprGt('lastoccurence', time() - 7200, false, 'co'));
		$andXEvent->add($qb->exprLt('firstoccurence', time() + (7 * 86400), false, 'co'));

		$qb->innerJoin(
			$qb->getDefaultSelectAlias(),
			self::TABLE_CAL_OBJECTS,
			'co',
			$andXEvent
		);

		// get event's name
		$qb->generateSelectAlias(self::$externalTables[self::TABLE_CAL_OBJ_PROPS], 'cp', 'cp');

		$andXSummary = $qb->expr()->andX();
		$andXSummary->add($qb->expr()->eq('co.calendarid', 'cp.calendarid'));
		$andXSummary->add($qb->expr()->eq('co.id', 'cp.objectid'));
		$andXSummary->add($qb->exprLimit('name', 'SUMMARY', 'cp'));

		$qb->innerJoin(
			'co',
			self::TABLE_CAL_OBJ_PROPS,
			'cp',
			$andXSummary
		);
	}
}
