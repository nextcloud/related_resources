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


namespace OCA\RelatedResources\Service;


use OCA\Circles\CirclesManager;
use OCA\Circles\Exceptions\MembershipNotFoundException;
use OCA\Circles\Model\FederatedUser;
use OCA\Circles\Model\Member;
use OCA\RelatedResources\AppInfo\Application;
use OCA\RelatedResources\Exceptions\RelatedResourceProviderNotFound;
use OCA\RelatedResources\ILinkWeightCalculator;
use OCA\RelatedResources\IRelatedResource;
use OCA\RelatedResources\IRelatedResourceProvider;
use OCA\RelatedResources\LinkWeightCalculators\TimeWeightCalculator;
use OCA\RelatedResources\Model\RelatedResource;
use OCA\RelatedResources\RelatedResourceProviders\CalendarRelatedResourceProvider;
use OCA\RelatedResources\RelatedResourceProviders\DeckRelatedResourceProvider;
use OCA\RelatedResources\RelatedResourceProviders\FilesRelatedResourceProvider;
use OCA\RelatedResources\RelatedResourceProviders\TalkRelatedResourceProvider;
use OCA\RelatedResources\Tools\Exceptions\ItemNotFoundException;
use OCA\RelatedResources\Tools\Traits\TNCLogger;
use OCP\App\IAppManager;
use ReflectionClass;
use ReflectionException;

class RelatedService {
	use TNCLogger;

	private IAppManager $appManager;
	private CirclesManager $circlesManager;

	/** @var ILinkWeightCalculator[] */
	private array $weightCalculators = [];

	/** @var string[] */
	private static array $weightCalculators_ = [
		TimeWeightCalculator::class
	];

	public function __construct(IAppManager $appManager) {
		$this->appManager = $appManager;
		$this->circlesManager = \OC::$server->get(CirclesManager::class);

		$this->setup('app', Application::APP_ID);
	}


	/**
	 * @param string $providerId
	 * @param string $itemId
	 * @param int $chunk
	 *
	 * @return IRelatedResource[]
	 * @throws RelatedResourceProviderNotFound
	 */
	public function getRelatedToItem(string $providerId, string $itemId, int $chunk = -1): array {
		$result = $this->retrieveRelatedToItem($providerId, $itemId);

		usort($result, function (IRelatedResource $r1, IRelatedResource $r2): int {
			$a = $r1->getScore();
			$b = $r2->getScore();

			return ($a === $b) ? 0 : (($a > $b) ? -1 : 1);
		});

		return ($chunk > -1) ? array_slice($result, 0, $chunk) : $result;
	}


	/**
	 * @param string $providerId
	 * @param string $itemId
	 *
	 * @return IRelatedResource[]
	 * @throws RelatedResourceProviderNotFound
	 */
	private function retrieveRelatedToItem(string $providerId, string $itemId): array {
		$recipients = $this->getSharesRecipients($providerId, $itemId);

		$this->debug('recipients returned by ' . $providerId . ': ' . json_encode($recipients));
//		$this->filterRecipients($recipients);

		$result = $itemPaths = [];
		foreach ($this->getRelatedResourceProviders() as $provider) {
			$known = [];

			foreach ($recipients as $entity) {
				foreach ($provider->getRelatedToEntity($entity) as $related) {
					$related->setLinkRecipient($entity->getSingleId());

					// if RelatedResource is based on current item, store it for weightResult() later in the process
					// also we do not want to filter duplicate
					if ($provider->getProviderId() === $providerId && $related->getItemId() === $itemId) {
						$itemPaths[] = $related;
					}

					// improve score on duplicate result
					if (in_array($related->getItemId(), $known)) {
						try {
							$knownRecipient = $this->extractRecipientFromResult(
								$related->getProviderId(),
								$related->getItemId(),
								$result
							);

							$knownRecipient->improve(RelatedResource::$IMPROVE_OCCURRENCE, 'occurrence');
						} catch (ItemNotFoundException $e) {
						}

						continue;
					}

					if ($provider->getProviderId() !== $providerId || $related->getItemId() !== $itemId) {
						$result[] = $related;
					}

					$known[] = $related->getItemId();
				}
			}
		}

		$result = $this->filterUnavailableResults($result);
		if (!empty($itemPaths)) {
			$this->weightResult($itemPaths, $result);
		}

		return $result;
	}


	/**
	 * @param string $providerId
	 * @param string $itemId
	 *
	 * @return FederatedUser[]
	 * @throws RelatedResourceProviderNotFound
	 */
	public function getSharesRecipients(string $providerId, string $itemId): array {
		return $this->getRelatedResourceProvider($providerId)
					->getSharesRecipients($itemId);
	}

	/**
	 * @param IRelatedResource[] $result
	 *
	 * @return  IRelatedResource[]
	 */
	private function filterUnavailableResults(array $result): array {
		$filtered = [];
		$singleId = $this->circlesManager->getCurrentFederatedUser()->getSingleId();


		foreach ($result as $entry) {
			// check item owner, to not filter away item owner by current user.
			if ($entry->getLinkRecipient() === '') {
				continue;
			}

			try {
				$this->circlesManager->getLink($entry->getLinkRecipient(), $singleId);
				$filtered[] = $entry;
			} catch (MembershipNotFoundException $e) {
				$curr = $this->circlesManager->getCurrentFederatedUser();
				if ($curr->getUserType() === Member::TYPE_USER
					&& $entry->getItemOwner() === $curr->getUserId()) {
					$filtered[] = $entry;
				} else {
					// might be heavy, but fastest way to implement a fix to verify the access on shares to users
					$recipients = $this->getSharesRecipients($entry->getProviderId(), $entry->getItemId());
					foreach ($recipients as $recipient) {
						if ($recipient->getSingleId() === $curr->getSingleId()) {
							$filtered[] = $entry;
							break;
						}
					}
				}
			}
		}

		return $filtered;
	}


	/**
	 * @param IRelatedResource[] $paths
	 * @param IRelatedResource[] $result
	 *
	 * @return void
	 */
	private function weightResult(array $paths, array &$result): void {
		foreach ($this->getWeightCalculators() as $weightCalculator) {
			$weightCalculator->weight($paths, $result);
		}
	}

	/**
	 * @return ILinkWeightCalculator[]
	 */
	private function getWeightCalculators(): array {
		if (empty($this->weightCalculators)) {
			$classes = self::$weightCalculators_;
			foreach ($this->getRelatedResourceProviders() as $provider) {
				foreach ($provider->loadWeightCalculator() as $class) {
					$classes[] = $class;
				}
			}

			foreach ($classes as $class) {
				try {
					$test = new ReflectionClass($class);
					if (!in_array(ILinkWeightCalculator::class, $test->getInterfaceNames())) {
						throw new ReflectionException(
							$class . ' does not implements ILinkWeightCalculator'
						);
					}

					$this->weightCalculators[] = \OC::$server->get($class);
				} catch (ReflectionException $e) {
					$this->e($e);
				}
			}
		}

		return $this->weightCalculators;
	}

	/**
	 * @param string $providerId
	 * @param string $itemId
	 * @param IRelatedResource[] $resources
	 *
	 * @return RelatedResource
	 * @throws ItemNotFoundException
	 */
	private function extractRecipientFromResult(
		string $providerId,
		string $itemId,
		array $resources
	): IRelatedResource {
		foreach ($resources as $resource) {
			if ($providerId === $resource->getProviderId()
				&& $itemId === $resource->getItemId()) {
				return $resource;
			}
		}

		throw new ItemNotFoundException();
	}


	/**
	 * @return IRelatedResourceProvider[]
	 */
	private function getRelatedResourceProviders(): array {
		$providers = [\OC::$server->get(FilesRelatedResourceProvider::class)];

		if ($this->appManager->isInstalled('deck')) {
			$providers[] = \OC::$server->get(DeckRelatedResourceProvider::class);
		}

		if ($this->appManager->isInstalled('calendar')) {
			$providers[] = \OC::$server->get(CalendarRelatedResourceProvider::class);
		}

		if ($this->appManager->isInstalled('spreed')) {
			$providers[] = \OC::$server->get(TalkRelatedResourceProvider::class);
		}

		return $providers;
	}

	/**
	 * @param string $relatedProviderId
	 *
	 * @return IRelatedResourceProvider
	 * @throws RelatedResourceProviderNotFound
	 */
	public function getRelatedResourceProvider(string $relatedProviderId): IRelatedResourceProvider {
		foreach ($this->getRelatedResourceProviders() as $provider) {
			if ($provider->getProviderId() === $relatedProviderId) {
				return $provider;
			}
		}

		throw new RelatedResourceProviderNotFound();
	}
}

