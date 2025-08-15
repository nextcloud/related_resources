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

class Calendar implements IQueryRow, JsonSerializable {
	use TArrayTools;

	private int $calendarId = 0;
	private string $calendarName = '';
	private string $calendarPrincipalUri = '';
	private string $calendarUri = '';

	public function __construct() {
	}

	public function getId(): string {
		return $this->getCalendarPrincipalUri() . ':' . $this->getCalendarUri();
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

	public function setCalendarUri(string $calendarUri): self {
		$this->calendarUri = $calendarUri;

		return $this;
	}

	public function getCalendarUri(): string {
		return $this->calendarUri;
	}

	public function importFromDatabase(array $data): IQueryRow {
		$this->setCalendarId($this->getInt('id', $data))
			->setCalendarName($this->get('displayname', $data))
			->setCalendarPrincipalUri($this->get('principaluri', $data))
			->setCalendarUri($this->get('uri', $data));

		return $this;
	}

	public function jsonSerialize(): array {
		return [
			'calendarName' => $this->getCalendarName(),
			'calendarPrincipalUri' => $this->getCalendarPrincipalUri(),
			'calendarUri' => $this->getCalendarUri()
		];
	}
}
