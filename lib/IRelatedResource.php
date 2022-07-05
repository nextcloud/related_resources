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

	public function getProviderId(): string;

	public function getItemId(): string;

	public function setTitle(string $title): self;

	public function getTitle(): string;

	public function setSubtitle(string $subtitle): self;

	public function getSubtitle(): string;

	public function setLink(string $link): self;

	public function getLink(): string;

	public function setRange(int $range): self;

	public function getRange(): int;

	public function setLastUpdate(int $time): self;

	public function getLastUpdate(): int;

	public function found(): self;

	public function getOccurrence(): int;
}

