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

class KeywordWeightCalculator implements ILinkWeightCalculator {
	use TArrayTools;


	/**
	 * @inheritDoc
	 */
	public function weight(IRelatedResource $current, array &$result): void {
		if (!$current->hasMeta(RelatedResource::ITEM_KEYWORDS)) {
			return;
		}

		foreach ($result as $entry) {
			if (!$entry->hasMeta(RelatedResource::ITEM_KEYWORDS)) {
				continue;
			}

			foreach ($entry->getMetaArray(RelatedResource::ITEM_KEYWORDS) as $kw) {
				if (strlen($kw) <= 3) {
					continue;
				}
				if (in_array($kw, $current->getMetaArray(RelatedResource::ITEM_KEYWORDS))) {
					$entry->improve(RelatedResource::$IMPROVE_HIGH_LINK, 'keyword');
				}
			}
		}
	}
}
