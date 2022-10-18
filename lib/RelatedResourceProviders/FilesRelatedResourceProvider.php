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
use OCA\RelatedResources\Service\MiscService;
use OCA\RelatedResources\Tools\Traits\TArrayTools;
use OCP\Files\InvalidPathException;
use OCP\Files\IRootFolder;
use OCP\Files\NotFoundException;
use OCP\Files\NotPermittedException;
use OCP\IL10N;
use OCP\IURLGenerator;

class FilesRelatedResourceProvider implements IRelatedResourceProvider {
	use TArrayTools;


	private const PROVIDER_ID = 'files';

	private IRootFolder $rootFolder;
	private IURLGenerator $urlGenerator;
	private IL10N $l10n;
	private FilesShareRequest $filesShareRequest;
	private CirclesManager $circlesManager;
	private MiscService $miscService;


	public function __construct(
		IRootFolder $rootFolder,
		IURLGenerator $urlGenerator,
		IL10N $l10n,
		FilesShareRequest $filesShareRequest,
		MiscService $miscService
	) {
		$this->rootFolder = $rootFolder;
		$this->urlGenerator = $urlGenerator;
		$this->l10n = $l10n;
		$this->filesShareRequest = $filesShareRequest;
		$this->miscService = $miscService;
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

		// 1 is useless in our case, and can occur on failed int conversion via cast
		if ($itemId <= 1) {
			return [];
		}

		$itemIds = $this->getItemIdsFromParentPath($itemId);
		$shares = $this->filesShareRequest->getSharesByItemIds($itemIds);
		$this->generateSingleIds($shares);

		return array_filter(
			array_map(function (FilesShare $share): ?FederatedUser {
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
//			case Member::TYPE_USER:
//				$shares = $this->filesShareRequest->getSharesToUser($entity->getUserId());
//				break;

			case Member::TYPE_GROUP:
				$shares = $this->filesShareRequest->getSharesToGroup($entity->getUserId());
				break;

			case Member::TYPE_CIRCLE:
				$shares = $this->filesShareRequest->getSharesToCircle($entity->getSingleId());
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
				RelatedResource::LINK_CREATOR => $share->getShareCreator(),
				RelatedResource::LINK_CREATION => $share->getShareTime()
			]
		);

		$kws = preg_split('/[\/_\-. ]/', ltrim(strtolower($share->getFileTarget()), '/'));
		if (is_array($kws)) {
			$related->setMetaArray(RelatedResource::ITEM_KEYWORDS, $kws);
		}

		return $related;
	}


	/**
	 * @param FilesShare[] $shares
	 */
	private function generateSingleIds(array $shares): void {
		foreach ($shares as $share) {
			$this->generateSingleId($share);
		}
	}

	/**
	 * @param FilesShare $share
	 */
	private function generateSingleId(FilesShare $share): void {
		try {
			$entity = $this->miscService->convertShareRecipient($share->getShareType(), $share->getSharedWith());

			$share->setEntity($entity);
		} catch (Exception $e) {
		}
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
}
