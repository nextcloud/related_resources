<?php

declare(strict_types=1);


/**
 * SPDX-FileCopyrightText: 2022 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */


namespace OCA\RelatedResources\LinkWeightCalculators;

use OCA\RelatedResources\ILinkWeightCalculator;
use OCA\RelatedResources\IRelatedResource;
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
	public function weight(IRelatedResource $current, array &$result): void {
		if (!$current->hasMeta(RelatedResource::LINK_CREATION)
			|| !$current->hasMeta(RelatedResource::LINK_CREATOR)
			|| !$current->hasMeta(RelatedResource::LINK_RECIPIENT)) {
			return;
		}

		foreach ($result as $entry) {
			if (!$entry->hasMeta(RelatedResource::LINK_CREATION)
				|| !$entry->hasMeta(RelatedResource::LINK_CREATOR)
				|| !$entry->hasMeta(RelatedResource::LINK_RECIPIENT)) {
				continue;
			}

			// check if link is initiated from same entity
			if ($entry->getMeta(RelatedResource::LINK_CREATOR)
				!== $current->getMeta(RelatedResource::LINK_CREATOR)) {
				continue;
			}

			if ($entry->getMetaInt(RelatedResource::LINK_CREATION)
				< $current->getMetaInt(RelatedResource::LINK_CREATION) + self::DELAY_1
				&& $entry->getMetaInt(RelatedResource::LINK_CREATION)
				   > $current->getMetaInt(RelatedResource::LINK_CREATION) - self::DELAY_1) {
				$entry->improve(RelatedResource::$IMPROVE_HIGH_LINK, 'time_delay_1');
				continue;
			}

			if ($entry->getMetaInt(RelatedResource::LINK_CREATION)
				< $current->getMetaInt(RelatedResource::LINK_CREATION) + self::DELAY_2
				&& $entry->getMetaInt(RelatedResource::LINK_CREATION)
				   > $current->getMetaInt(RelatedResource::LINK_CREATION) - self::DELAY_2) {
				$entry->improve(RelatedResource::$IMPROVE_MEDIUM_LINK, 'time_delay_2');
				continue;
			}

			if ($entry->getMetaInt(RelatedResource::LINK_CREATION)
				< $current->getMetaInt(RelatedResource::LINK_CREATION) + self::DELAY_3
				&& $entry->getMetaInt(RelatedResource::LINK_CREATION)
				   > $current->getMetaInt(RelatedResource::LINK_CREATION) - self::DELAY_3) {
				$entry->improve(RelatedResource::$IMPROVE_LOW_LINK, 'time_delay_3');
				continue;
			}
		}
	}
}
