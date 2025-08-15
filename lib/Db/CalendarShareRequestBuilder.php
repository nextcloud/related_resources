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
use OCA\RelatedResources\Tools\Exceptions\InvalidItemException;
use OCA\RelatedResources\Tools\Exceptions\RowNotFoundException;

class CalendarShareRequestBuilder extends CoreQueryBuilder {
	/**
	 * @return CoreRequestBuilder
	 */
	protected function getCalendarSelectSql(): CoreRequestBuilder {
		$qb = $this->getQueryBuilder();
		$qb->generateSelect(self::TABLE_CALENDARS, self::$externalTables[self::TABLE_CALENDARS]);

		return $qb;
	}

	/**
	 * @return CoreRequestBuilder
	 */
	protected function getCalendarShareSelectSql(): CoreRequestBuilder {
		$qb = $this->getQueryBuilder();
		$qb->generateSelect(self::TABLE_DAV_SHARE, self::$externalTables[self::TABLE_DAV_SHARE]);

		return $qb;
	}

	/**
	 * @param CoreRequestBuilder $qb
	 *
	 * @return Calendar
	 * @throws CalendarDataNotFoundException
	 */
	public function getCalendarFromRequest(CoreRequestBuilder $qb): Calendar {
		/** @var Calendar $calendar */
		try {
			$calendar = $qb->asItem(Calendar::class);
		} catch (InvalidItemException|RowNotFoundException $e) {
			throw new CalendarDataNotFoundException();
		}

		return $calendar;
	}

	/**
	 * @param CoreRequestBuilder $qb
	 *
	 * @return Calendar[]
	 */
	public function getCalendarsFromRequest(CoreRequestBuilder $qb): array {
		return $qb->asItems(Calendar::class);
	}

	/**
	 * @param CoreRequestBuilder $qb
	 *
	 * @return CalendarShare[]
	 */
	public function getSharesFromRequest(CoreRequestBuilder $qb): array {
		return $qb->asItems(CalendarShare::class);
	}
}
