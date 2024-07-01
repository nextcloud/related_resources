<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2023 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
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
