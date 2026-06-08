<?php

declare(strict_types=1);


/**
 * SPDX-FileCopyrightText: 2022 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */


namespace OCA\RelatedResources\Db;

use OCA\RelatedResources\Exceptions\CalendarDataNotFoundException;
use OCA\RelatedResources\Model\Calendar;
use OCA\RelatedResources\Model\CalendarShare;
use OCA\RelatedResources\Tools\Db\ExtendedQueryBuilder;
use OCA\RelatedResources\Tools\Exceptions\InvalidItemException;
use OCA\RelatedResources\Tools\Exceptions\RowNotFoundException;

class CalendarShareRequest extends CoreQueryBuilder {

	protected function getCalendarShareSelectSql(): ExtendedQueryBuilder {
		$qb = $this->getQueryBuilder();
		$qb->generateSelect(self::TABLE_DAV_SHARE, self::EXTERNAL_TABLES[self::TABLE_DAV_SHARE]);

		return $qb;
	}

	/**
	 * @return Calendar
	 * @throws CalendarDataNotFoundException
	 */
	public function getCalendarFromRequest(ExtendedQueryBuilder $qb): Calendar {
		/** @var Calendar $calendar */
		try {
			$calendar = $qb->asItem(Calendar::class);
		} catch (InvalidItemException|RowNotFoundException $e) {
			throw new CalendarDataNotFoundException();
		}

		return $calendar;
	}

	/**
	 * @return Calendar[]
	 */
	public function getCalendarsFromRequest(ExtendedQueryBuilder $qb): array {
		return $qb->asItems(Calendar::class);
	}

	/**
	 * @return CalendarShare[]
	 */
	public function getSharesFromRequest(ExtendedQueryBuilder $qb): array {
		return $qb->asItems(CalendarShare::class);
	}
	protected function getCalendarSelectSql():  ExtendedQueryBuilder {
		$qb = $this->getQueryBuilder();
		$qb->generateSelect(self::TABLE_CALENDARS, self::EXTERNAL_TABLES[self::TABLE_CALENDARS]);

		return $qb;
	}
	/**
	 * @param string $principalUri
	 * @param string $uri
	 *
	 * @return Calendar
	 * @throws CalendarDataNotFoundException
	 */
	public function getCalendarByUri(string $principalUri, string $uri): Calendar {
		$qb = $this->getCalendarSelectSql();
		$qb->limit('principaluri', $principalUri);
		$qb->limit('uri', $uri);

		return $this->getCalendarFromRequest($qb);
	}


	/**
	 * @param int $calendarId
	 *
	 * @return CalendarShare[]
	 */
	public function getSharesByCalendarId(int $calendarId): array {
		$qb = $this->getCalendarShareSelectSql();
		$qb->limit('type', 'calendar');
		$qb->limitInt('resourceid', $calendarId);

		return $this->getSharesFromRequest($qb);
	}


	/**
	 * @param string $singleId
	 *
	 * @return Calendar[]
	 */
	public function getCalendarAvailableToCircle(string $singleId): array {
		$qb = $this->getCalendarSelectSql();
		$qb->innerJoin(
			$qb->getDefaultSelectAlias(), self::TABLE_DAV_SHARE, 'ds',
			$qb->expr()->eq($qb->getDefaultSelectAlias() . '.id', 'ds.resourceid')
		);

		$qb->limit('type', 'calendar', 'ds');
		$qb->limit('principaluri', 'principals/circles/' . $singleId, 'ds');

		return $this->getCalendarsFromRequest($qb);
	}


	/**
	 * @param string $groupName
	 *
	 * @return Calendar[]
	 */
	public function getCalendarAvailableToGroup(string $groupName): array {
		$qb = $this->getCalendarSelectSql();
		$qb->innerJoin(
			$qb->getDefaultSelectAlias(), self::TABLE_DAV_SHARE, 'ds',
			$qb->expr()->eq($qb->getDefaultSelectAlias() . '.id', 'ds.resourceid')
		);

		$qb->limit('type', 'calendar', 'ds');
		$qb->limit('principaluri', 'principals/groups/' . $groupName, 'ds');

		return $this->getCalendarsFromRequest($qb);
	}


	/**
	 * @param string $userName
	 *
	 * @return Calendar[]
	 */
	public function getCalendarAvailableToUser(string $userName): array {
		$qb = $this->getCalendarSelectSql();
		$qb->innerJoin(
			$qb->getDefaultSelectAlias(), self::TABLE_DAV_SHARE, 'ds',
			$qb->expr()->eq($qb->getDefaultSelectAlias() . '.id', 'ds.resourceid')
		);

		$qb->limit('type', 'calendar', 'ds');
		$qb->limit('principaluri', 'principals/users/' . $userName, 'ds');

		return $this->getCalendarsFromRequest($qb);
	}
}
