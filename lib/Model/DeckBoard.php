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

class DeckBoard implements IQueryRow, JsonSerializable {
	use TArrayTools;

	private int $boardId = 0;
	private string $boardName = '';
	private string $owner = '';
	private int $lastModified = 0;

	public function __construct() {
	}


	/**
	 * @param int $boardId
	 *
	 * @return DeckBoard
	 */
	public function setBoardId(int $boardId): self {
		$this->boardId = $boardId;

		return $this;
	}

	/**
	 * @return int
	 */
	public function getBoardId(): int {
		return $this->boardId;
	}


	/**
	 * @param string $boardName
	 *
	 * @return DeckBoard
	 */
	public function setBoardName(string $boardName): self {
		$this->boardName = $boardName;

		return $this;
	}

	/**
	 * @return string
	 */
	public function getBoardName(): string {
		return $this->boardName;
	}

	/**
	 * @param string $owner
	 *
	 * @return DeckBoard
	 */
	public function setOwner(string $owner): self {
		$this->owner = $owner;

		return $this;
	}

	/**
	 * @return string
	 */
	public function getOwner(): string {
		return $this->owner;
	}


	/**
	 * @param int $lastModified
	 *
	 * @return DeckBoard
	 */
	public function setLastModified(int $lastModified): self {
		$this->lastModified = $lastModified;

		return $this;
	}

	/**
	 * @return int
	 */
	public function getLastModified(): int {
		return $this->lastModified;
	}

	/**
	 * @param array $data
	 *
	 * @return IQueryRow
	 */
	public function importFromDatabase(array $data): IQueryRow {
		$this->setBoardId($this->getInt('id', $data))
			->setBoardName($this->get('title', $data))
			->setOwner($this->get('owner', $data))
			->setLastModified($this->getInt('last_modified', $data));

		return $this;
	}

	/**
	 * @return array
	 */
	public function jsonSerialize(): array {
		return [
			'boardId' => $this->getBoardId(),
			'boardName' => $this->getBoardName(),
			'owner' => $this->getOwner(),
			'last_modified' => $this->getLastModified()
		];
	}
}
