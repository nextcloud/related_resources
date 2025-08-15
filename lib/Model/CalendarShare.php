<?php

declare(strict_types=1);


/**
 * SPDX-FileCopyrightText: 2022 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */


namespace OCA\RelatedResources\Model;

use JsonSerializable;
use OCA\RelatedResources\Tools\Db\IQueryRow;
use OCA\RelatedResources\Tools\Traits\TArrayTools;

class CalendarShare implements IQueryRow, JsonSerializable {
	use TArrayTools;

	private int $calendarId = 0;
	private string $sharePrincipalUri = '';
	private int $type = 0;
	private string $user = '';

	public function __construct() {
	}

	public function setCalendarId(int $calendarId): self {
		$this->calendarId = $calendarId;

		return $this;
	}

	public function getCalendarId(): int {
		return $this->calendarId;
	}

	public function setSharePrincipalUri(string $sharePrincipalUri): self {
		$this->sharePrincipalUri = $sharePrincipalUri;

		return $this;
	}

	public function getSharePrincipalUri(): string {
		return $this->sharePrincipalUri;
	}


	/**
	 * @param int $type
	 *
	 * @return CalendarShare
	 */
	public function setType(int $type): self {
		$this->type = $type;

		return $this;
	}

	/**
	 * @return int
	 */
	public function getType(): int {
		return $this->type;
	}

	/**
	 * @param string $user
	 *
	 * @return CalendarShare
	 */
	public function setUser(string $user): self {
		$this->user = $user;

		return $this;
	}

	/**
	 * @return string
	 */
	public function getUser(): string {
		return $this->user;
	}


	public function importFromDatabase(array $data): IQueryRow {
		$this->setCalendarId($this->getInt('resourceid', $data))
			->setSharePrincipalUri($this->get('principaluri', $data));

		return $this;
	}

	public function jsonSerialize(): array {
		return [
			'id' => $this->getCalendarId(),
			'sharePrincipalUri' => $this->getSharePrincipalUri(),
			'type' => $this->getType(),
			'user' => $this->getUser()
		];
	}
}
