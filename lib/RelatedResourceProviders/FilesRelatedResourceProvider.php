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
use OC\User\NoUserException;
use OCA\Circles\CirclesManager;
use OCA\Circles\Model\FederatedUser;
use OCA\Circles\Model\Member;
use OCA\RelatedResources\Db\FilesShareRequest;
use OCA\RelatedResources\IRelatedResource;
use OCA\RelatedResources\IRelatedResourceProvider;
use OCA\RelatedResources\Model\FilesShare;
use OCA\RelatedResources\Model\RelatedResource;
use OCA\RelatedResources\Tools\Traits\TArrayTools;
use OCP\AutoloadNotAllowedException;
use OCP\Files\InvalidPathException;
use OCP\Files\IRootFolder;
use OCP\Files\NotFoundException;
use OCP\Files\NotPermittedException;
use OCP\IL10N;
use OCP\IURLGenerator;
use OCP\Server;
use OCP\Share\IShare;
use Psr\Container\ContainerExceptionInterface;

class FilesRelatedResourceProvider implements IRelatedResourceProvider {
	use TArrayTools;


	private const PROVIDER_ID = 'files';

	private IRootFolder $rootFolder;
	private IURLGenerator $urlGenerator;
	private IL10N $l10n;
	private FilesShareRequest $filesShareRequest;
	private ?CirclesManager $circlesManager = null;


	public function __construct(
		IRootFolder $rootFolder,
		IURLGenerator $urlGenerator,
		IL10N $l10n,
		FilesShareRequest $filesShareRequest
	) {
		$this->rootFolder = $rootFolder;
		$this->urlGenerator = $urlGenerator;
		$this->l10n = $l10n;
		$this->filesShareRequest = $filesShareRequest;
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


	public function getRelatedFromItem(string $itemId): ?IRelatedResource {
		if ($this->circlesManager === null) {
			return null;
		}

		$itemId = (int)$itemId;
		if ($itemId <= 1) {
			return null;
		}

		$related = null;
		try {
			$itemIds = $this->getItemIdsFromParentPath($itemId);
		} catch (Exception $e) {
			$itemIds = [$itemId];
		}

		foreach ($this->filesShareRequest->getSharesByItemIds($itemIds) as $share) {
			if ($related === null) {
				$related = $this->convertToRelatedResource($share);
			}
			$this->processShareRecipient($related, $share);
		}

		return $related;
	}


	public function getItemsAvailableToEntity(FederatedUser $entity): array {
		switch ($entity->getBasedOn()->getSource()) {
			case Member::TYPE_USER:
				$shares = $this->filesShareRequest->getSharesToUser($entity->getUserId());
				break;

			case Member::TYPE_GROUP:
				$shares = $this->filesShareRequest->getSharesToGroup($entity->getUserId());
				break;

			case Member::TYPE_CIRCLE:
				$shares = $this->filesShareRequest->getSharesToCircle($entity->getSingleId());
				break;

			default:
				return [];
		}

		return array_map(function (FilesShare $share): string {
			return (string)$share->getFileId();
		}, $shares);
	}


	public function improveRelatedResource(IRelatedResource $entry): void {
		$current = $this->circlesManager->getCurrentFederatedUser();
		if (!$current->isLocal() || $current->getUserType() !== Member::TYPE_USER) {
			return;
		}

		$paths = $this->rootFolder->getUserFolder($current->getUserId())
								  ->getById((int) $entry->getItemId());

		if (sizeof($paths) > 0) {
			$entry->setTitle($paths[0]->getName());
		}
	}


	private function convertToRelatedResource(FilesShare $share): IRelatedResource {
		$related = new RelatedResource(self::PROVIDER_ID, (string)$share->getFileId());
		$related->setTitle(trim($share->getFileTarget(), '/'));
		$related->setSubtitle($this->l10n->t('Files'));
		$related->setTooltip($this->l10n->t('File "%s"', $share->getFileTarget()));
		$related->setIcon(
			$this->urlGenerator->getAbsoluteURL(
				$this->urlGenerator->imagePath(
					'files',
					'app.svg'
				)
			)
		);

		$related->setUrl(
			$this->urlGenerator->linkToRouteAbsolute('files.View.showFile', ['fileid' => $share->getFileId()])
		);
		$related->setMetas(
			[
				RelatedResource::ITEM_LAST_UPDATE => $share->getFileLastUpdate(),
				RelatedResource::ITEM_OWNER => $share->getFileOwner(),
				//				RelatedResource::LINK_CREATOR => $share->getShareCreator(),
				RelatedResource::LINK_CREATION => $share->getShareTime()
			]
		);

		$keywords = preg_split('/[\/_\-. ]/', ltrim(strtolower($share->getFileTarget()), '/'));
		if (is_array($keywords)) {
			$related->setMetaArray(RelatedResource::ITEM_KEYWORDS, $keywords);
		}

		return $related;
	}


	/**
	 * @param int $itemId
	 *
	 * @return int[]
	 * @throws InvalidPathException
	 * @throws NotFoundException
	 * @throws NotPermittedException
	 * @throws NoUserException
	 */
	private function getItemIdsFromParentPath(int $itemId): array {
		$itemIds = [$itemId];
		$current = $this->circlesManager->getCurrentFederatedUser();
		if (!$current->isLocal() || $current->getUserType() !== Member::TYPE_USER) {
			return $itemIds;
		}

		$paths = $this->rootFolder->getUserFolder($current->getUserId())
								  ->getById($itemId);

		foreach ($paths as $path) {
			while (true) {
				$path = $path->getParent();
				if ($path->getId() === 0) {
					break;
				}
				$itemIds[] = $path->getId();
			}
		}

		return $itemIds;
	}


	/**
	 * @param RelatedResource $related
	 * @param FilesShare $share
	 */
	private function processShareRecipient(RelatedResource $related, FilesShare $share) {
		try {
			$sharedWith = $this->convertShareRecipient(
				$share->getShareType(),
				$share->getSharedWith()
			);

			if ($share->getShareType() === IShare::TYPE_USER) {
				$shareCreator = $this->convertShareRecipient(
					IShare::TYPE_USER,
					$share->getShareCreator()
				);

				$related->mergeVirtualGroup(
					[
						$sharedWith->getSingleId(),
						$shareCreator->getSingleId()
					]
				);
			} else {
				$related->addRecipient($sharedWith->getSingleId())
						->setAsGroupShared();
			}
		} catch (Exception $e) {
		}
	}


	/**
	 * @param int $shareType
	 * @param string $sharedWith
	 *
	 * @return FederatedUser
	 * @throws Exception
	 */
	private function convertShareRecipient(int $shareType, string $sharedWith): FederatedUser {
		if (is_null($this->circlesManager)) {
			throw new Exception('Circles needs to be enabled');
		}

		switch ($shareType) {
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
				throw new Exception('unknown share type (' . $shareType . ')');
		}

		return $this->circlesManager->getFederatedUser($sharedWith, $type);
	}
}
