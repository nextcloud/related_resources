<?php

declare(strict_types=1);


/**
 * SPDX-FileCopyrightText: 2022 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */


namespace OCA\RelatedResources;

interface ILinkWeightCalculator {
	/**
	 * @param IRelatedResource $current
	 * @param IRelatedResource[] $result
	 */
	public function weight(IRelatedResource $current, array &$result): void;
}
