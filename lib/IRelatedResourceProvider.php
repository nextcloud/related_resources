<?php

declare(strict_types=1);


/**
 * SPDX-FileCopyrightText: 2022 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */


namespace OCA\RelatedResources;

use OCA\Circles\CirclesManager;
use OCA\Circles\Model\FederatedUser;

interface IRelatedResourceProvider {
	public function getProviderId(): string;

	/**
	 * returns the list of ILinkWeightCalculator provided by this app
	 *
	 * @return string[]
	 */
	public function loadWeightCalculator(): array;

	/**
	 * convert item to IRelatedResource, based on available shares
	 *
	 * @param CirclesManager $circlesManager
	 * @param string $itemId
	 *
	 * @return IRelatedResource|null
	 */
	public function getRelatedFromItem(CirclesManager $circlesManager, string $itemId): ?IRelatedResource;

	/**
	 * returns itemIds (as string) the entity have access to
	 *
	 * @param FederatedUser $entity
	 *
	 * @return string[]
	 */
	public function getItemsAvailableToEntity(FederatedUser $entity): array;

	/**
	 * improve a related resource before sending result to front-end.
	 *
	 * @param CirclesManager $circlesManager
	 * @param IRelatedResource $entry
	 *
	 * @return void
	 */
	public function improveRelatedResource(CirclesManager $circlesManager, IRelatedResource $entry): void;
}
