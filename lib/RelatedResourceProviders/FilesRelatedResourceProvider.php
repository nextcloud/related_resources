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
use OCA\RelatedResources\Db\FilesShareRequest;
use OCA\RelatedResources\IRelatedResource;
use OCA\RelatedResources\IRelatedResourceProvider;
use OCA\RelatedResources\Model\FilesShare;
use OCA\RelatedResources\Model\RelatedResource;
use OCA\RelatedResources\Tools\Traits\TArrayTools;
use OCP\IURLGenerator;
use OCP\Share\IShare;

class FilesRelatedResourceProvider implements IRelatedResourceProvider {
	use TArrayTools;


	private const PROVIDER_ID = 'files';

	private IURLGenerator $urlGenerator;
	private FilesShareRequest $filesShareRequest;
	private CirclesManager $circlesManager;


	public function __construct(IURLGenerator $urlGenerator, FilesShareRequest $filesShareRequest) {
		$this->urlGenerator = $urlGenerator;
		$this->filesShareRequest = $filesShareRequest;
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

		// 1 is useless in our case, and can occur on failed int conversion via cast
		if ($itemId <= 1) {
			return [];
		}

		$shares = $this->filesShareRequest->getSharesByItemId($itemId);
		$this->generateSingleIds($shares);

		return array_filter(
			array_map(function (FilesShare $share): ?FederatedUser {
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
				$shares = $this->filesShareRequest->getSharesToGroup($federatedUser->getUserId());
				break;

			case Member::TYPE_CIRCLE:
				$shares = $this->filesShareRequest->getSharesToCircle($federatedUser->getSingleId());
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
		$related->setTitle($share->getFileTarget());
		$related->setDescription('Files');
		$related->setRange(1);
		$related->setLink('/index.php/f/' . $share->getFileId());

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
			switch ($share->getShareType()) {
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

			$entity = $this->circlesManager->getFederatedUser($share->getSharedWith(), $type);

			$share->setEntity($entity);
		} catch (Exception $e) {
		}
	}
}
