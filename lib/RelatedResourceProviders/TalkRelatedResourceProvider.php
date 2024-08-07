<?php

declare(strict_types=1);


/**
 * SPDX-FileCopyrightText: 2022 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */


namespace OCA\RelatedResources\RelatedResourceProviders;

use Exception;
use OCA\Circles\CirclesManager;
use OCA\Circles\Exceptions\FederatedUserNotFoundException;
use OCA\Circles\Model\FederatedUser;
use OCA\Circles\Model\Member;
use OCA\RelatedResources\Db\TalkRoomRequest;
use OCA\RelatedResources\Exceptions\TalkDataNotFoundException;
use OCA\RelatedResources\IRelatedResource;
use OCA\RelatedResources\IRelatedResourceProvider;
use OCA\RelatedResources\Model\RelatedResource;
use OCA\RelatedResources\Model\TalkActor;
use OCA\RelatedResources\Model\TalkRoom;
use OCA\RelatedResources\Tools\Traits\TArrayTools;
use OCP\IL10N;
use OCP\IURLGenerator;
use Psr\Log\LoggerInterface;

class TalkRelatedResourceProvider implements IRelatedResourceProvider {
	use TArrayTools;

	private const PROVIDER_ID = 'talk';

	public function __construct(
		private IURLGenerator $urlGenerator,
		private IL10N $l10n,
		private TalkRoomRequest $talkRoomRequest,
		private LoggerInterface $logger
	) {
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
	public function getRelatedFromItem(CirclesManager $circlesManager, string $itemId): ?IRelatedResource {
		/** @var TalkRoom $room */
		try {
			$room = $this->talkRoomRequest->getRoomByToken($itemId);
		} catch (TalkDataNotFoundException $e) {
			return null;
		}

		$related = $this->convertToRelatedResource($room);
		foreach ($this->talkRoomRequest->getActorsByToken($room->getToken()) as $actor) {
			$this->processRoomParticipant($circlesManager, $related, $actor);
		}

		if (!$related->isGroupShared()) {
			$countActor = count($related->getVirtualGroup());
			if ($countActor === 1) { // room is still in preparation
				return null;
			}

			if ($countActor === 2 && $room->getRoomType() === \OCA\Talk\Room::TYPE_ONE_TO_ONE) {
				$related->setTitle($this->l10n->t('Talk conversation'))
						->setMetaArray('1on1', json_decode($room->getRoomName()));
			}
		}

		return $related;
	}


	public function improveRelatedResource(CirclesManager $circlesManager, IRelatedResource $entry): void {
		if (!$entry->hasMeta('1on1')) {
			return;
		}

		try {
			$current = $circlesManager->getCurrentFederatedUser();
		} catch (FederatedUserNotFoundException $e) {
			$circlesManager->startSession(); // enforce new session if not available
			$current = $circlesManager->getCurrentFederatedUser();
			$this->logger->info('session restarted', ['current' => $current]);
		}

		if (!$current->isLocal() || $current->getUserType() !== Member::TYPE_USER) {
			return;
		}

		foreach ($entry->getMetaArray('1on1') as $actor) {
			if ($actor !== $current->getUserId()) {
				$entry->setTitle($this->l10n->t('Conversation with %s', $actor));

				return;
			}
		}
	}

	public function getItemsAvailableToEntity(FederatedUser $entity): array {
		switch ($entity->getBasedOn()->getSource()) {
			case Member::TYPE_USER:
				$shares = $this->talkRoomRequest->getRoomsAvailableToUser($entity->getUserId());
				break;

			case Member::TYPE_GROUP:
				$shares = $this->talkRoomRequest->getRoomsAvailableToGroup($entity->getUserId());
				break;

			case Member::TYPE_CIRCLE:
				// We skip circle shares for related resources as they are covered by team resources
				return [];

			default:
				return [];
		}

		return array_map(function (TalkRoom $room): string {
			return $room->getToken();
		}, $shares);
	}


	private function convertToRelatedResource(TalkRoom $share): IRelatedResource {
		$url = '';
		try {
			$url = $this->urlGenerator->linkToRouteAbsolute(
				'spreed.Page.showCall',
				[
					'token' => $share->getToken()
				]
			);
		} catch (Exception $e) {
		}
		$related = new RelatedResource(self::PROVIDER_ID, $share->getToken());
		$related->setTitle($share->getRoomName())
				->setSubtitle($this->l10n->t('Talk'))
				->setTooltip($this->l10n->t('Talk conversation "%s"', $share->getRoomName()))
				->setIcon(
					$this->urlGenerator->getAbsoluteURL(
						$this->urlGenerator->imagePath(
							'spreed',
							'app.svg'
						)
					)
				)
			->setPreview(
				$this->urlGenerator->linkToOCSRouteAbsolute(
					'spreed.Avatar.getAvatar',
					['token' => $share->getToken(), 'apiVersion' => 'v1']
				)
			)
			->setUrl($url);

		$keywords = preg_split('/[\/_\-. ]/', ltrim(strtolower($share->getRoomName()), '/'));
		if (is_array($keywords)) {
			$related->setMetaArray(RelatedResource::ITEM_KEYWORDS, $keywords);
		}

		return $related;
	}


	/**
	 * @param RelatedResource $related
	 * @param TalkActor $actor
	 */
	private function processRoomParticipant(
		CirclesManager $circlesManager,
		RelatedResource $related,
		TalkActor $actor
	) {
		try {
			$participant = $this->convertRoomParticipant($circlesManager, $actor);
			if ($actor->getActorType() === 'users') {
				$related->addToVirtualGroup($participant->getSingleId());
			} else {
				$related->addRecipient($participant->getSingleId())
						->setAsGroupShared();
			}
		} catch (Exception $e) {
		}
	}


	/**
	 * @param TalkActor $actor
	 *
	 * @return FederatedUser
	 * @throws Exception
	 */
	public function convertRoomParticipant(CirclesManager $circlesManager, TalkActor $actor): FederatedUser {
		switch ($actor->getActorType()) {
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
				throw new Exception('unknown actor type (' . $actor->getActorType() . ')');
		}

		return $circlesManager->getFederatedUser($actor->getActorId(), $type);
	}
}
