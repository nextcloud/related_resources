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


namespace OCA\RelatedResources\RelatedResourceProviders;


use Exception;
use OCA\Circles\CirclesManager;
use OCA\Circles\Model\FederatedUser;
use OCA\Circles\Model\Member;
use OCA\RelatedResources\Db\DeckShareRequest;
use OCA\RelatedResources\IRelatedResource;
use OCA\RelatedResources\IRelatedResourceProvider;
use OCA\RelatedResources\Model\DeckShare;
use OCA\RelatedResources\Model\RelatedResource;
use OCA\RelatedResources\Tools\Traits\TArrayTools;
use OCP\IURLGenerator;
use OCP\Share\IShare;


/**
 * Class RelatedResource
 *
 * @package OCA\RelatedResources\Model
 */
class DeckRelatedResourceProvider implements IRelatedResourceProvider {
	use TArrayTools;


	private const PROVIDER_ID = 'deck';

	private IUrlGenerator $urlGenerator;
	private DeckShareRequest $deckSharesRequest;
	private CirclesManager $circlesManager;


	public function __construct(IUrlGenerator $urlGenerator, DeckShareRequest $deckSharesRequest) {
		$this->urlGenerator = $urlGenerator;
		$this->deckSharesRequest = $deckSharesRequest;
		$this->circlesManager = \OC::$server->get(CirclesManager::class);
	}


	public function getProviderId(): string {
		return self::PROVIDER_ID;
	}


	public function loadWeightCalculator(): array {
		return [];
	}


	/**
	 * @param string $itemId
	 *
	 * @return FederatedUser[]
	 */
	public function getSharesRecipients(string $itemId): array {
		$itemId = (int)$itemId;

		$shares = $this->deckSharesRequest->getSharesByItemId($itemId);
		$this->assignEntities($shares);

		return array_filter(
			array_map(function (DeckShare $share): ?FederatedUser {
				return $share->getEntity();
			}, $shares)
		);
	}


	/**
	 * @param FederatedUser $entity
	 *
	 * @return IRelatedResource[]
	 */
	public function getRelatedToEntity(FederatedUser $entity): array {
		switch ($entity->getBasedOn()->getSource()) {
			case Member::TYPE_USER:
				$shares = $this->deckSharesRequest->getSharesToUser($entity->getUserId());
				break;

			case Member::TYPE_GROUP:
				$shares = $this->deckSharesRequest->getSharesToGroup($entity->getUserId());
				break;

			case Member::TYPE_CIRCLE:
				$shares = $this->deckSharesRequest->getSharesToCircle($entity->getSingleId());
				break;

			default:
				return [];
		}

		$related = [];
		foreach ($shares as $share) {
			$related[] = $this->convertToRelatedResource($share);
		}

		return $related;
	}


	private function convertToRelatedResource(DeckShare $share): IRelatedResource {
		$url = '';
		try {
			$url =
				$this->urlGenerator->linkToRouteAbsolute('deck.page.index')
				. '#/board/' . $share->getBoardId();
		} catch (Exception $e) {
		}

		$related = new RelatedResource(self::PROVIDER_ID, (string)$share->getBoardId());
		$related->setTitle($share->getBoardName());
		$related->setSubtitle('Deck board');
		$related->setTooltip('Deck board \'' . $share->getBoardName() . '\'');
		$related->setUrl($url);
		$related->setMetaInt(RelatedResource::ITEM_LAST_UPDATE, $share->getLastModified());

		$kws = preg_split('/[\/_\-. ]/', ltrim(strtolower($share->getBoardName()), '/'));
		if (is_array($kws)) {
			$related->setMetaArray(RelatedResource::ITEM_KEYWORDS, $kws);
		}

		return $related;
	}


	/**
	 * @param DeckShare[] $shares
	 */
	private function assignEntities(array $shares): void {
		foreach ($shares as $share) {
			$this->assignEntity($share);
		}
	}

	/**
	 * @param DeckShare $share
	 */
	private function assignEntity(DeckShare $share): void {
		try {
			switch ($share->getType()) {
				case IShare::TYPE_USER:
					$type = Member::TYPE_USER;
					break;

				case IShare::TYPE_GROUP:
					$type = Member::TYPE_GROUP;
					break;

				case IShare::TYPE_CIRCLE:
					$type = Member::TYPE_SINGLE;
					break;

				default:
					throw new Exception();
			}

			$entity = $this->circlesManager->getFederatedUser($share->getParticipant(), $type);

			$share->setEntity($entity);
		} catch (Exception $e) {
		}
	}
}
