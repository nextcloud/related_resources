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


	public static float $IMPROVE_LOW_LINK = 1.1;
	public static float $IMPROVE_MEDIUM_LINK = 1.3;
	public static float $IMPROVE_HIGH_LINK = 1.8;
	public static float $IMPROVE_OCCURRENCE = 1.3;
	public static float $UNRELATED = 0.85;
	private static float $DIMINISHING_RETURN = 0.7;


	private string $providerId;
	private string $itemId;
	private string $title = '';
	private string $subtitle = '';
	private string $tooltip = '';
	private string $url = '';
	private int $range = 0;
	private int $linkCreation = 0;
	private int $itemCreation = 0;
	private string $itemOwner = '';
	private int $itemLastUpdate = 0;
	private float $score = 1;
	private array $improvements = [];
	private string $linkCreator = '';
	private string $linkRecipient = '';
	private array $currentQuality = [];

//	private ?FederatedUser $entity = null;

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


	public function setUrl(string $url): IRelatedResource {
		$this->url = $url;

		return $this;
	}

	public function getUrl(): string {
		return $this->url;
	}

	public function setRange(int $range): IRelatedResource {
		$this->range = $range;

		return $this;
	}

	public function getRange(): int {
		return $this->range;
	}


	/**
	 * @param int $linkCreation
	 *
	 * @return RelatedResource
	 */
	public function setLinkCreation(int $linkCreation): self {
		$this->linkCreation = $linkCreation;

		return $this;
	}

	/**
	 * @return int
	 */
	public function getLinkCreation(): int {
		return $this->linkCreation;
	}


	public function setLinkCreator(string $linkCreator): self {
		$this->linkCreator = $linkCreator;

		return $this;
	}

	public function getLinkCreator(): string {
		return $this->linkCreator;
	}


	public function setLinkRecipient(string $linkRecipient): self {
		$this->linkRecipient = $linkRecipient;

		return $this;
	}

	public function getLinkRecipient(): string {
		return $this->linkRecipient;
	}


	/**
	 * @param int $itemCreation
	 *
	 * @return RelatedResource
	 */
	public function setItemCreation(int $itemCreation): self {
		$this->itemCreation = $itemCreation;

		return $this;
	}

	/**
	 * @return int
	 */
	public function getItemCreation(): int {
		return $this->itemCreation;
	}


	public function setItemOwner(string $itemOwner): self {
		$this->itemOwner = $itemOwner;

		return $this;
	}

	public function getItemOwner(): string {
		return $this->itemOwner;
	}


	public function setItemLastUpdate(int $time): IRelatedResource {
		$this->itemLastUpdate = $time;

		return $this;
	}

	public function getItemLastUpdate(): int {
		return $this->itemLastUpdate;
	}


	/**
	 * @param float|int $quality
	 * @param string $type
	 * @param bool $diminishingReturn
	 *
	 * @return IRelatedResource
	 */
	public function improve(
		float $quality,
		string $type,
		bool $diminishingReturn = true
	): IRelatedResource {
		$quality = ($this->currentQuality[$type] ?? $quality);
		$this->score = $this->score * $quality;
		$this->improvements[] = [
			'type' => $type,
			'quality' => $quality
		];

		if ($diminishingReturn) {
			$quality = 1 + (($quality - 1) * self::$DIMINISHING_RETURN);
		}

		$this->currentQuality[$type] = $quality;

		return $this;
	}


	/**
	 * @return float
	 */
	public function getScore(): float {
		return $this->score;
	}


	/**
	 * @return array
	 */
	public function getImprovements(): array {
		return $this->improvements;
	}

//
//	/**
//	 * @param FederatedUser $entity
//	 *
//	 * @return RelatedResource
//	 */
//	public function setEntity(FederatedUser $entity): self {
//		$this->entity = $entity;
//
//		return $this;
//	}
//
//	/**
//	 * @return FederatedUser
//	 */
//	public function getEntity(): ?FederatedUser {
//		return $this->entity;
//	}
//

	/**
	 * @return array
	 */
	public function jsonSerialize(): array {
		return [
			'providerId' => $this->getProviderId(),
			'itemId' => $this->getItemId(),
			'title' => $this->getTitle(),
			'subtitle' => $this->getSubtitle(),
			'tooltip' => $this->getTooltip(),
			'url' => $this->getUrl(),
			'lastUpdate' => $this->getItemLastUpdate(),
			'linkRecipient' => $this->getLinkRecipient(),
			'linkCreator' => $this->getLinkCreator(),
			'creation' => $this->getLinkCreation(),
			'score' => $this->getScore(),
			'improvements' => $this->getImprovements()
		];
	}
}
