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

class DeckShare implements IQueryRow, JsonSerializable {
	use TArrayTools;

	private int $boardId = 0;
	private int $recipientType = 0;
	private string $recipientId = '';


	public function __construct() {
	}


	/**
	 * @param int $boardId
	 *
	 * @return DeckShare
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
	 * @param int $recipientType
	 *
	 * @return DeckShare
	 */
	public function setRecipientType(int $recipientType): self {
		$this->recipientType = $recipientType;

		return $this;
	}

	/**
	 * @return int
	 */
	public function getRecipientType(): int {
		return $this->recipientType;
	}


	/**
	 * @param string $recipientId
	 *
	 * @return DeckShare
	 */
	public function setRecipientId(string $recipientId): self {
		$this->recipientId = $recipientId;

		return $this;
	}

	/**
	 * @return string
	 */
	public function getRecipientId(): string {
		return $this->recipientId;
	}


	/**
	 * @param array $data
	 *
	 * @return IQueryRow
	 */
	public function importFromDatabase(array $data): IQueryRow {
		$this->setBoardId($this->getInt('board_id', $data))
			->setRecipientType($this->getInt('type', $data))
			->setRecipientId($this->get('participant', $data));

		return $this;
	}

	/**
	 * @return array
	 */
	public function jsonSerialize(): array {
		return [
			'boardId' => $this->getBoardId(),
			'recipientType' => $this->getRecipientType(),
			'recipientId' => $this->getRecipientId()
		];
	}
}
