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

class DeckShare implements IQueryRow, JsonSerializable {
	use TArrayTools;

	private int $boardId = 0;
	private string $boardName = '';
	private int $type = 0;
	private FederatedUser $entity;
	private string $participant = '';
	private int $lastModified = 0;

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
	 * @param string $boardName
	 *
	 * @return DeckShare
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
	 * @param int $type
	 *
	 * @return DeckShare
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
	 * @param string $participant
	 *
	 * @return DeckShare
	 */
	public function setParticipant(string $participant): self {
		$this->participant = $participant;

		return $this;
	}

	/**
	 * @return string
	 */
	public function getParticipant(): string {
		return $this->participant;
	}


	/**
	 * @param int $lastModified
	 *
	 * @return DeckShare
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
	 * @param FederatedUser $entity
	 *
	 * @return DeckShare
	 */
	public function setEntity(FederatedUser $entity): self {
		$this->entity = $entity;

		return $this;
	}

	/**
	 * @return FederatedUser
	 */
	public function getEntity(): ?FederatedUser {
		return $this->entity;
	}

	/**
	 * @param array $data
	 *
	 * @return IQueryRow
	 */
	public function importFromDatabase(array $data): IQueryRow {
		$this->setBoardId($this->getInt('board_id', $data))
			 ->setBoardName($this->get('db_title', $data))
			 ->setType($this->getInt('type', $data))
			 ->setParticipant($this->get('participant', $data))
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
			'type' => $this->getType(),
			'participant' => $this->getParticipant(),
			'last_modified' => $this->getLastModified(),
			'entity' => $this->getEntity()
		];
	}
}
