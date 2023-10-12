<?php

declare(strict_types=1);

/**
 * Nextcloud - Related Resources
 *
 * This file is licensed under the Affero General Public License version 3 or
 * later. See the COPYING file.
 *
 * @author Maxence Lange <maxence@artificial-owl.com>
 * @copyright 2023
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

namespace OCA\RelatedResources\RelatedResourceProviders;

use OCA\Circles\CirclesManager;
use OCA\Circles\Model\FederatedUser;
use OCA\Circles\Model\Member;
use OCA\RelatedResources\IRelatedResource;
use OCA\RelatedResources\IRelatedResourceProvider;
use OCA\RelatedResources\Model\RelatedResource;

class AccountRelatedResourceProvider implements IRelatedResourceProvider {
	private const PROVIDER_ID = 'account';

	public function __construct() {
	}

	public function getProviderId(): string {
		return self::PROVIDER_ID;
	}

	public function loadWeightCalculator(): array {
		return [];
	}

	public function getRelatedFromItem(CirclesManager $circlesManager, string $itemId): ?IRelatedResource {
		$related = new RelatedResource(self::PROVIDER_ID, $itemId);
		$related->setTitle('Account ' . $itemId);

		$card = $circlesManager->getFederatedUser($itemId, Member::TYPE_USER);
		$curr = $circlesManager->getCurrentFederatedUser();

		$related->mergeVirtualGroup(
			[
				$curr->getSingleId(),
				$card->getSingleId()
			]
		);

		return $related;
	}

	public function improveRelatedResource(CirclesManager $circlesManager, IRelatedResource $entry): void {
	}

	public function getItemsAvailableToEntity(FederatedUser $entity): array {
		return [];
	}
}
