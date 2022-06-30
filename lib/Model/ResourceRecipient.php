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
use OCA\RelatedResources\Tools\Db\IQueryRow;
use OCA\RelatedResources\Tools\IDeserializable;
use OCA\RelatedResources\Tools\Traits\TArrayTools;


/**
 * Class RelatedResource
 *
 * @package OCA\RelatedResources\Model
 */
class ResourceRecipient implements IQueryRow, JsonSerializable, IDeserializable {
	use TArrayTools;


	private string $singleId;
	private int $range = 0;

	public function __construct() {
	}


	/**
	 * @param string $singleId
	 *
	 * @return ResourceRecipient
	 */
	public function setSingleId(string $singleId): self {
		$this->singleId = $singleId;

		return $this;
	}

	/**
	 * @return string
	 */
	public function getSingleId(): string {
		return $this->singleId;
	}


	/**
	 * @param int $range
	 *
	 * @return ResourceRecipient
	 */
	public function setRange(int $range): self {
		$this->range = $range;

		return $this;
	}

	/**
	 * @return int
	 */
	public function getRange(): int {
		return $this->range;
	}


	/**
	 * @param array $data
	 *
	 * @return IDeserializable
	 */
	public function import(array $data): IDeserializable {

		return $this;
	}

	public function importFromDatabase(array $data): IQueryRow {
//		$this->setSingleId($this->get('single_id'));
		// TODO: Implement importFromDatabase() method.

		return $this;
	}

	/**
	 * @return array
	 */
	public function jsonSerialize(): array {
		return [
			'singleId' => $this->getSingleId()
		];
	}

}
