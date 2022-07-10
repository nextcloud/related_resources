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


namespace OCA\RelatedResources\Model;


use JsonSerializable;
use OCA\Circles\Model\FederatedUser;
use OCA\RelatedResources\Tools\Db\IQueryRow;
use OCA\RelatedResources\Tools\Traits\TArrayTools;


class CalendarShare implements IQueryRow, JsonSerializable {
	use TArrayTools;

	private int $calendarId = 0;
	private string $calendarName = '';
	private string $calendarPrincipalUri = '';
	private string $sharePrincipalUri = '';
	private int $eventDate = 0;
	private string $eventSummary = '';
	private ?FederatedUser $entity = null;

	public function __construct() {
	}

	public function setCalendarId(int $calendarId): self {
		$this->calendarId = $calendarId;

		return $this;
	}

	public function getCalendarId(): int {
		return $this->calendarId;
	}

	public function setCalendarName(string $calendarName): self {
		$this->calendarName = $calendarName;

		return $this;
	}

	public function getCalendarName(): string {
		return $this->calendarName;
	}

	public function setCalendarPrincipalUri(string $calendarPrincipalUri): self {
		$this->calendarPrincipalUri = $calendarPrincipalUri;

		return $this;
	}

	public function getCalendarPrincipalUri(): string {
		return $this->calendarPrincipalUri;
	}

	public function setSharePrincipalUri(string $sharePrincipalUri): self {
		$this->sharePrincipalUri = $sharePrincipalUri;

		return $this;
	}

	public function getSharePrincipalUri(): string {
		return $this->sharePrincipalUri;
	}

	public function setEventDate(int $eventDate): self {
		$this->eventDate = $eventDate;

		return $this;
	}

	public function getEventDate(): int {
		return $this->eventDate;
	}

	public function setEventSummary(string $eventSummary): self {
		$this->eventSummary = $eventSummary;

		return $this;
	}

	public function getEventSummary(): string {
		return $this->eventSummary;
	}

	public function setEntity(FederatedUser $entity): self {
		$this->entity = $entity;

		return $this;
	}

	public function getEntity(): ?FederatedUser {
		return $this->entity;
	}

	public function importFromDatabase(array $data): IQueryRow {
		$this->setCalendarId($this->getInt('resourceid', $data))
			 ->setCalendarName($this->get('cl_displayname', $data))
			 ->setCalendarPrincipalUri($this->get('cl_principaluri', $data))
			 ->setSharePrincipalUri($this->get('principaluri', $data))
			 ->setEventDate($this->getInt('co_firstoccurence', $data))
			 ->setEventSummary($this->get('cp_value', $data));

		return $this;
	}

	public function jsonSerialize(): array {
		return [
			'id' => $this->getCalendarId(),
			'calendarName' => $this->getCalendarName(),
			'calendarPrincipalUri' => $this->getSharePrincipalUri(),
			'sharePrincipalUri' => $this->getSharePrincipalUri(),
			'eventDate' => $this->getEventDate(),
			'eventSummary' => $this->getEventSummary()
		];
	}

}
