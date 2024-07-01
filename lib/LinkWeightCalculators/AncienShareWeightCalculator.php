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

class AncienShareWeightCalculator implements ILinkWeightCalculator {
	use TArrayTools;


	private static float $RATIO_5Y = 0.4;
	private static float $RATIO_3Y = 0.7;
	private static float $RATIO_1Y = 0.85;


	/**
	 * @inheritDoc
	 */
	public function weight(IRelatedResource $current, array &$result): void {
		if (!$current->hasMeta(RelatedResource::LINK_CREATION)) {
			return;
		}

		foreach ($result as $entry) {
			if (!$entry->hasMeta(RelatedResource::LINK_CREATION)) {
				continue;
			}

			$now = time();
			$entryCreation = $entry->getMetaInt(RelatedResource::LINK_CREATION);
			if ($entryCreation < $now - (5 * 360 * 24 * 3600)) { // 5y
				$entry->improve(self::$RATIO_5Y, 'ancien_5y');
			} elseif ($entryCreation < $now - (3 * 360 * 24 * 3600)) { // 3y
				$entry->improve(self::$RATIO_3Y, 'ancien_3y');
			} elseif ($entryCreation < $now - (360 * 24 * 3600)) { // 1y
				$entry->improve(self::$RATIO_1Y, 'ancien_1y');
			}

			$diff = abs(
				$current->getMetaInt(RelatedResource::LINK_CREATION)
				- $entry->getMetaInt(RelatedResource::LINK_CREATION)
			);

			// calculate an improvement base on 0.75 up to 1.2, based on difference of time between 2 shares
			// with 1.0 score for a 3 month period
			$neutral = 90 * 24 * 3600;
			$ratio = $diff - $neutral;
			$impr = 1 - ($ratio * 0.2 / $neutral);
			$impr = max($impr, 0.75);
			$entry->improve($impr, 'ancien_3m');
		}
	}
}
