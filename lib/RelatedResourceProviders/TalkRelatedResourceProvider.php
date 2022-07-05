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
use OCA\RelatedResources\Db\TalkRoomRequest;
use OCA\RelatedResources\IRelatedResource;
use OCA\RelatedResources\IRelatedResourceProvider;
use OCA\RelatedResources\Model\RelatedResource;
use OCA\RelatedResources\Model\TalkRoom;
use OCA\RelatedResources\Tools\Traits\TArrayTools;
use OCP\IURLGenerator;


class TalkRelatedResourceProvider implements IRelatedResourceProvider {
	use TArrayTools;


	private const PROVIDER_ID = 'talk';

	private TalkRoomRequest $talkRoomRequest;
	private CirclesManager $circlesManager;
	private IURLGenerator $urlGenerator;


	public function __construct(IURLGenerator $urlGenerator, TalkRoomRequest $talkRoomRequest) {
		$this->urlGenerator = $urlGenerator;
		$this->talkRoomRequest = $talkRoomRequest;
		$this->circlesManager = \OC::$server->get(CirclesManager::class);
	}


	public function getProviderId(): string {
		return self::PROVIDER_ID;
	}


	/**
	 * @param string $itemId
	 *
	 * @return FederatedUser[]
	 */
	public function getSharesRecipients(string $itemId): array {
		$itemId = (int)$itemId;

		if ($itemId < 1) {
			return [];
		}

		$shares = $this->talkRoomRequest->getSharesByItemId($itemId);
		$this->generateSingleIds($shares);

		return array_filter(
			array_map(function (TalkRoom $share): ?FederatedUser {
				return $share->getEntity();
			}, $shares)
		);
	}


	/**
	 * @param FederatedUser[] $entities
	 *
	 * @return IRelatedResource[]
	 */
	public function getRelatedToEntities(array $entities): array {
		$result = [];
		foreach ($entities as $entity) {
			$result = array_merge($result, $this->getRelatedToEntity($entity));
		}

		return $result;
	}

	/**
	 * @param FederatedUser $federatedUser
	 *
	 * @return IRelatedResource[]
	 */
	public function getRelatedToEntity(FederatedUser $federatedUser): array {
		switch ($federatedUser->getBasedOn()->getSource()) {
			case Member::TYPE_USER:
				// TODO: check other direct share from the same origin and from around the same time of creation !?
				return [];

			case Member::TYPE_GROUP:
				$shares = $this->talkRoomRequest->getSharesToGroup($federatedUser->getUserId());
				break;

			case Member::TYPE_CIRCLE:
				$shares = $this->talkRoomRequest->getSharesToCircle($federatedUser->getSingleId());
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


	private function convertToRelatedResource(TalkRoom $share): IRelatedResource {
		$related = new RelatedResource(self::PROVIDER_ID, (string)$share->getRoomId());
		$related->setTitle($share->getRoomName());
		$related->setSubtitle('Talk Room');
		$related->setTooltip('Talk Room \'' . $share->getRoomName() . '\'');
		$related->setRange(1);
		$related->setLink(
			$this->urlGenerator->linkToRouteAbsolute('spreed.Page.showCall',
													 [
														 'token' => $share->getToken()
													 ]
			)
		);

		//['name' => 'Page#showCall', 'url' => '/call/{token}', 'root' => '', 'verb' => 'GET'],

		return $related;
	}


	/**
	 * @param TalkRoom[] $shares
	 */
	private function generateSingleIds(array $shares): void {
		foreach ($shares as $share) {
			$this->generateSingleId($share);
		}
	}

	/**
	 * @param TalkRoom $share
	 */
	private function generateSingleId(TalkRoom $share): void {
		try {
			switch ($share->getActorType()) {
				case 'users':
					$type = Member::TYPE_USER;
					break;

				case 'groups':
					$type = Member::TYPE_GROUP;
					break;

				case 'circles':
					$type = Member::TYPE_SINGLE;
					break;

				default:
					throw new Exception();
			}

			$entity = $this->circlesManager->getFederatedUser($share->getActorId(), $type);

			$share->setEntity($entity);
		} catch (Exception $e) {
		}
	}
}
