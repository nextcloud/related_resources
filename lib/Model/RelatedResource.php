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
use OCA\RelatedResources\IRelatedResource;
use OCA\RelatedResources\Tools\Traits\TArrayTools;


/**
 * Class RelatedResource
 *
 * @package OCA\RelatedResources\Model
 */
class RelatedResource implements IRelatedResource, JsonSerializable {
	use TArrayTools;


	private string $providerId;
	private string $itemId;
	private string $title = '';
	private string $subtitle = '';
	private string $tooltip = '';
	private string $link = '';
	private int $range = 0;
	private int $lastUpdate = 0;
	private int $occurrence = 1;


	public function __construct(string $providerId = '', string $itemId = '') {
		$this->providerId = $providerId;
		$this->itemId = $itemId;
	}


	public function getProviderId(): string {
		return $this->providerId;
	}

	public function getItemId(): string {
		return $this->itemId;
	}


	public function setTitle(string $title): IRelatedResource {
		$this->title = $title;

		return $this;
	}

	public function getTitle(): string {
		return $this->title;
	}


	public function setSubtitle(string $subtitle): IRelatedResource {
		$this->subtitle = $subtitle;

		return $this;
	}

	public function getSubtitle(): string {
		return $this->subtitle;
	}


	/**
	 * @param string $tooltip
	 *
	 * @return RelatedResource
	 */
	public function setTooltip(string $tooltip): self {
		$this->tooltip = $tooltip;

		return $this;
	}

	/**
	 * @return string
	 */
	public function getTooltip(): string {
		return $this->tooltip;
	}


	public function setLink(string $link): IRelatedResource {
		$this->link = $link;

		return $this;
	}

	public function getLink(): string {
		return $this->link;
	}

	public function setRange(int $range): IRelatedResource {
		$this->range = $range;

		return $this;
	}

	public function getRange(): int {
		return $this->range;
	}


	public function setLastUpdate(int $time): IRelatedResource {
		$this->lastUpdate = $time;

		return $this;
	}

	public function getLastUpdate(): int {
		return $this->lastUpdate;
	}


	/**
	 * @return IRelatedResource
	 */
	public function found(): IRelatedResource {
		$this->occurrence++;

		return $this;
	}

	/**
	 * @return int
	 */
	public function getOccurrence(): int {
		return $this->occurrence;
	}


	/**
	 * @return array
	 */
	public function jsonSerialize(): array {
		return [
			'providerId' => $this->getProviderId(),
			'itemId' => $this->getItemId(),
			'title' => $this->getTitle(),
			'subtitle' => $this->getSubtitle(),
			'tooltip' => '>>>> ' . $this->getTooltip(),
			'link' => $this->getLink(),
			'lastUpdate' => $this->getLastUpdate(),
			'occurrence' => $this->getOccurrence()
		];
	}
}
