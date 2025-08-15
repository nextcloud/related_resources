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

class TalkActor implements IQueryRow, JsonSerializable {
	use TArrayTools;

	private string $actorType = '';
	private string $actorId = '';

	public function __construct() {
	}

	/**
	 * @param string $actorType
	 *
	 * @return TalkActor
	 */
	public function setActorType(string $actorType): self {
		$this->actorType = $actorType;

		return $this;
	}

	/**
	 * @return string
	 */
	public function getActorType(): string {
		return $this->actorType;
	}

	/**
	 * @param string $actorId
	 *
	 * @return TalkActor
	 */
	public function setActorId(string $actorId): self {
		$this->actorId = $actorId;

		return $this;
	}

	/**
	 * @return string
	 */
	public function getActorId(): string {
		return $this->actorId;
	}


	/**
	 * @param array $data
	 *
	 * @return IQueryRow
	 */
	public function importFromDatabase(array $data): IQueryRow {
		$this->setActorType($this->get('actor_type', $data))
			->setActorId($this->get('actor_id', $data));

		return $this;
	}

	/**
	 * @return array
	 */
	public function jsonSerialize(): array {
		return [
			'actorType' => $this->getActorType(),
			'actorId' => $this->getActorId()
		];
	}
}
