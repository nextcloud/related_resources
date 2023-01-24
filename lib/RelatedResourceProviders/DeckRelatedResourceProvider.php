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
use OCA\RelatedResources\Db\DeckRequest;
use OCA\RelatedResources\Exceptions\DeckDataNotFoundException;
use OCA\RelatedResources\IRelatedResource;
use OCA\RelatedResources\IRelatedResourceProvider;
use OCA\RelatedResources\Model\DeckBoard;
use OCA\RelatedResources\Model\DeckShare;
use OCA\RelatedResources\Model\RelatedResource;
use OCA\RelatedResources\Tools\Traits\TArrayTools;
use OCP\AutoloadNotAllowedException;
use OCP\IL10N;
use OCP\IURLGenerator;
use OCP\Server;
use OCP\Share\IShare;
use Psr\Container\ContainerExceptionInterface;

class DeckRelatedResourceProvider implements IRelatedResourceProvider {
	use TArrayTools;


	private const PROVIDER_ID = 'deck';

	private IUrlGenerator $urlGenerator;
	private IL10N $l10n;
	private DeckRequest $deckSharesRequest;
	private ?CirclesManager $circlesManager = null;


	public function __construct(
		IUrlGenerator $urlGenerator,
		IL10N $l10n,
		DeckRequest $deckSharesRequest
	) {
		$this->urlGenerator = $urlGenerator;
		$this->l10n = $l10n;
		$this->deckSharesRequest = $deckSharesRequest;
		try {
			$this->circlesManager = Server::get(CirclesManager::class);
		} catch (ContainerExceptionInterface | AutoloadNotAllowedException $e) {
		}
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
	 * @return IRelatedResource|null
	 */
	public function getRelatedFromItem(string $itemId): ?IRelatedResource {
		if ($this->circlesManager === null) {
			return null;
		}

		$itemId = (int)$itemId;

		/** @var DeckBoard $board */
		try {
			$board = $this->deckSharesRequest->getBoardById($itemId);
		} catch (DeckDataNotFoundException $e) {
			return null;
		}

		$related = $this->convertToRelatedResource($board);
		$owner = $this->circlesManager->getFederatedUser($board->getOwner(), Member::TYPE_USER);
		$related->addToVirtualGroup($owner->getSingleId());

		foreach ($this->deckSharesRequest->getSharesByBoardId($itemId) as $share) {
			$this->processDeckShare($related, $share);
		}

		return $related;
	}


	public function getItemsAvailableToEntity(FederatedUser $entity): array {
		switch ($entity->getBasedOn()->getSource()) {
			case Member::TYPE_USER:
				$shares = $this->deckSharesRequest->getDeckAvailableToUser($entity->getUserId());
				break;

			case Member::TYPE_GROUP:
				$shares = $this->deckSharesRequest->getDeckAvailableToGroup($entity->getUserId());
				break;

			case Member::TYPE_CIRCLE:
				$shares = $this->deckSharesRequest->getDeckAvailableToCircle($entity->getSingleId());
				break;

			default:
				return [];
		}

		return array_map(function (DeckBoard $board): string {
			return (string)$board->getBoardId();
		}, $shares);
	}


	public function improveRelatedResource(IRelatedResource $entry): void {
	}

	private function convertToRelatedResource(DeckBoard $board): IRelatedResource {
		$url = '';
		try {
			$url =
				$this->urlGenerator->linkToRouteAbsolute('deck.page.index')
				. '#/board/' . $board->getBoardId();
		} catch (Exception $e) {
		}

		$related = new RelatedResource(self::PROVIDER_ID, (string)$board->getBoardId());
		$related->setTitle($board->getBoardName())
				->setSubtitle($this->l10n->t('Deck'))
				->setTooltip($this->l10n->t('Deck board "%s"', $board->getBoardName()))
				->setIcon(
					$this->urlGenerator->getAbsoluteURL(
						$this->urlGenerator->imagePath(
							'deck',
							'deck.svg'
						)
					)
				)
				->setUrl($url);

		$related->setMetaInt(RelatedResource::ITEM_LAST_UPDATE, $board->getLastModified());

		$keywords = preg_split('/[\/_\-. ]/', ltrim(strtolower($board->getBoardName()), '/'));
		if (is_array($keywords)) {
			$related->setMetaArray(RelatedResource::ITEM_KEYWORDS, $keywords);
		}

		return $related;
	}


	/**
	 * @param RelatedResource $related
	 * @param DeckShare $share
	 */
	private function processDeckShare(RelatedResource $related, DeckShare $share) {
		try {
			$participant = $this->convertDeckShare($share);
			if ($share->getRecipientType() === IShare::TYPE_USER) {
				$related->addToVirtualGroup($participant->getSingleId());
			} else {
				$related->addRecipient($participant->getSingleId())
						->setAsGroupShared();
			}
		} catch (Exception $e) {
		}
	}


	/**
	 * @param DeckShare $share
	 *
	 * @return FederatedUser
	 * @throws Exception
	 */
	public function convertDeckShare(DeckShare $share): FederatedUser {
		if (is_null($this->circlesManager)) {
			throw new Exception('Circles needs to be enabled');
		}

		switch ($share->getRecipientType()) {
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
				throw new Exception('unknown deck share type (' . $share->getRecipientType() . ')');
		}

		return $this->circlesManager->getFederatedUser($share->getRecipientId(), $type);
	}
}
