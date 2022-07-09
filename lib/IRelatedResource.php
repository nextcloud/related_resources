<?php

declare(strict_types=1);


/**
 * Nextcloud - Related Resources
 *
 * This file is licensed under the Affero General Public License version 3 or
 * later. See the COPYING file.
 *
 * @author Maxence Lange <maxence@pontapreta.net>
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


namespace OCA\RelatedResources;


interface IRelatedResource {

	// too many method, needs to create sub-model ?
	public function getProviderId(): string;

	public function getItemId(): string;

	public function setTitle(string $title): self;

	public function getTitle(): string;

	public function setSubtitle(string $subtitle): self;

	public function getSubtitle(): string;

	public function setUrl(string $url): self;

	public function getUrl(): string;

	public function setRange(int $range): self;

	public function getRange(): int;

	public function getLinkCreator(): string;

	public function getLinkRecipient(): string;

	public function setLinkCreator(string $linkCreator): self;

	public function setLinkRecipient(string $linkRecipient): self;

	public function getLinkCreation(): int;

	public function getItemCreation(): int;

	public function setItemLastUpdate(int $time): self;

	public function getItemLastUpdate(): int;

	public function improve(float $quality = 0, string $type = 'undefined'): self;

	public function getImprovements(): array;

	public function getScore(): float;
}

