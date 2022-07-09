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


namespace OCA\RelatedResources\LinkWeightCalculators;


use OCA\RelatedResources\ILinkWeightCalculator;
use OCA\RelatedResources\Model\RelatedResource;
use OCA\RelatedResources\Tools\Traits\TArrayTools;


class TimeWeightCalculator implements ILinkWeightCalculator {
	use TArrayTools;

	private const DELAY_1 = 120;
	private const DELAY_2 = 900;
	private const DELAY_3 = 7200;

	/**
	 * @inheritDoc
	 */
	public function weight(array $paths, array &$result): void {
		foreach ($paths as $path) {
			if ($path->getLinkCreation() === 0
				|| $path->getLinkCreator() === ''
				|| $path->getLinkRecipient() === '') {
				continue;
			}

			foreach ($result as $entry) {
				if ($entry->getLinkCreation() === 0
					|| $entry->getLinkCreator() === ''
					|| $entry->getLinkRecipient() === '') {
					continue;
				}

				// check if link is initiated from same entity
				if ($entry->getLinkCreator() !== $path->getLinkCreator()) {
					continue;
				}

				if ($entry->getLinkCreation() < $path->getLinkCreation() + self::DELAY_1
					&& $entry->getLinkCreation() > $path->getLinkCreation() - self::DELAY_1) {
					$entry->improve(RelatedResource::$IMPROVE_HIGH_LINK, 'time_delay_1');
					continue;
				}

				if ($entry->getLinkCreation() < $path->getLinkCreation() + self::DELAY_2
					&& $entry->getLinkCreation() > $path->getLinkCreation() - self::DELAY_2) {
					$entry->improve(RelatedResource::$IMPROVE_MEDIUM_LINK, 'time_delay_2');
					continue;
				}

				if ($entry->getLinkCreation() < $path->getLinkCreation() + self::DELAY_3
					&& $entry->getLinkCreation() > $path->getLinkCreation() - self::DELAY_3) {
					$entry->improve(RelatedResource::$IMPROVE_LOW_LINK, 'time_delay_3');
					continue;
				}
			}
		}
	}
}
