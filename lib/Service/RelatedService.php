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


use Exception;
use OCA\Circles\CirclesManager;
use OCA\Circles\Exceptions\MembershipNotFoundException;
use OCA\Circles\Model\FederatedUser;
use OCA\Circles\Model\Member;
use OCA\RelatedResources\AppInfo\Application;
use OCA\RelatedResources\Exceptions\CacheNotFoundException;
use OCA\RelatedResources\Exceptions\RelatedResourceProviderNotFound;
use OCA\RelatedResources\ILinkWeightCalculator;
use OCA\RelatedResources\IRelatedResource;
use OCA\RelatedResources\IRelatedResourceProvider;
use OCA\RelatedResources\LinkWeightCalculators\AncienShareWeightCalculator;
use OCA\RelatedResources\LinkWeightCalculators\KeywordWeightCalculator;
use OCA\RelatedResources\LinkWeightCalculators\TimeWeightCalculator;
use OCA\RelatedResources\Model\RelatedResource;
use OCA\RelatedResources\RelatedResourceProviders\CalendarRelatedResourceProvider;
use OCA\RelatedResources\RelatedResourceProviders\DeckRelatedResourceProvider;
use OCA\RelatedResources\RelatedResourceProviders\FilesRelatedResourceProvider;
use OCA\RelatedResources\RelatedResourceProviders\TalkRelatedResourceProvider;
use OCA\RelatedResources\Tools\Exceptions\ItemNotFoundException;
use OCA\RelatedResources\Tools\Traits\TDeserialize;
use OCA\RelatedResources\Tools\Traits\TNCLogger;
use OCP\App\IAppManager;
use OCP\ICache;
use OCP\ICacheFactory;
use ReflectionClass;
use ReflectionException;

class RelatedService {
	use TNCLogger;
	use TDeserialize;

	public const CACHE_RELATED = 'related/related';
	public const CACHE_RECIPIENT_TTL = 600;
	public const CACHE_RELATED_TTL = 600;

	private IAppManager $appManager;
	private ICache $cache;
	private CirclesManager $circlesManager;
	private ConfigService $configService;

	/** @var ILinkWeightCalculator[] */
	private array $weightCalculators = [];

	/** @var string[] */
	private static array $weightCalculators_ = [
		TimeWeightCalculator::class,
		KeywordWeightCalculator::class,
		AncienShareWeightCalculator::class
	];

	public function __construct(
		IAppManager $appManager,
		ICacheFactory $cacheFactory,
		ConfigService $configService
	) {
		$this->appManager = $appManager;
		$this->cache = $cacheFactory->createDistributed(self::CACHE_RELATED);
		$this->configService = $configService;
		try {
			$this->circlesManager = \OC::$server->get(CirclesManager::class);
		} catch (Exception $e) {
		}

		// TODO: if we keep using ICache, we might need to clean the cache on some actions:
//				$this->cache->clear();
//				$this->cache->remove();

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

		$validRecipientIds = array_map(function (FederatedUser $federatedUser): string {
			return $federatedUser->getSingleId();
		}, $recipients);

		$this->debug('recipients returned by ' . $providerId . ': ' . json_encode($recipients));

		$result = $itemPaths = [];
		foreach ($this->getRelatedResourceProviders() as $provider) {
			$known = [];

			foreach ($recipients as $entity) {
				foreach ($this->getRelatedToEntity($provider, $entity) as $related) {
					$related->setMeta(RelatedResource::LINK_RECIPIENT, $entity->getSingleId());

					// if RelatedResource is based on current item, store it for weightResult() later in the process
					// also we do not want to filter duplicate
					if ($provider->getProviderId() === $providerId && $related->getItemId() === $itemId) {
						$itemPaths[] = $related;
					}

					// improve score on over-shared items
					$spread = $this->getSharesRecipients($related->getProviderId(), $related->getItemId());
					foreach ($spread as $shareRecipient) {
						if (!in_array($shareRecipient->getSingleId(), $validRecipientIds)) {
							$related->improve(RelatedResource::$UNRELATED, 'unrelated', false);
						}
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
		try {
			$shares = $this->getCachedSharesRecipients($providerId, $itemId);

			return $shares;
		} catch (CacheNotFoundException $e) {
		}

		$result = $this->getRelatedResourceProvider($providerId)
					   ->getSharesRecipients($itemId);

		$this->cacheSharesRecipients($providerId, $itemId, $result);

		return $result;
	}


	/**
	 * @param string $providerId
	 * @param string $itemId
	 *
	 * @return FederatedUser[]
	 * @throws CacheNotFoundException
	 */
	private function getCachedSharesRecipients(string $providerId, string $itemId): array {
		$key = $this->generateSharesCacheKey($providerId, $itemId);
		$cachedData = $this->cache->get($key);

		if (!is_string($cachedData) || empty($cachedData)) {
			throw new CacheNotFoundException();
		}

		/** @var FederatedUser[] $result */
		return $this->forceDeserializeArrayFromJson($cachedData, FederatedUser::class);
	}

	/**
	 * @param string $providerId
	 * @param string $itemId
	 * @param FederatedUser[] $recipients
	 */
	private function cacheSharesRecipients(string $providerId, string $itemId, array $recipients): void {
		$key = $this->generateSharesCacheKey($providerId, $itemId);
		$this->cache->set($key, json_encode($recipients), self::CACHE_RECIPIENT_TTL);
	}

	private function generateSharesCacheKey(string $providerId, string $itemId): string {
		return 'shares/' . $providerId . '::' . $itemId;
	}


	/**
	 * @param IRelatedResourceProvider $provider
	 * @param FederatedUser $entity
	 *
	 * @return IRelatedResource[]
	 */
	private function getRelatedToEntity(IRelatedResourceProvider $provider, FederatedUser $entity): array {
		try {
			$related = $this->getCachedRelatedToEntity($provider, $entity);

			return $related;
		} catch (CacheNotFoundException $e) {
		}

		$result = $provider->getRelatedToEntity($entity);
		$this->cacheRelatedToEntity($provider, $entity, $result);

		return $result;
	}


	/**
	 * @param string $providerId
	 * @param string $itemId
	 *
	 * @return RelatedResource[]
	 * @throws CacheNotFoundException
	 */
	private function getCachedRelatedToEntity(
		IRelatedResourceProvider $provider,
		FederatedUser $entity
	): array {
		$key = $this->generateRelatedToEntityCacheKey($provider, $entity);
		$cachedData = $this->cache->get($key);

		if (!is_string($cachedData) || empty($cachedData)) {
			throw new CacheNotFoundException();
		}

		/** @var RelatedResource[] $result */
		return $this->deserializeArrayFromJson($cachedData, RelatedResource::class);
	}

	/**
	 * @param IRelatedResourceProvider $provider
	 * @param FederatedUser $entity
	 * @param array $related
	 */
	private function cacheRelatedToEntity(
		IRelatedResourceProvider $provider,
		FederatedUser $entity,
		array $related
	): void {
		$key = $this->generateRelatedToEntityCacheKey($provider, $entity);
		$this->cache->set($key, json_encode($related), self::CACHE_RELATED_TTL);
	}

	private function generateRelatedToEntityCacheKey(
		IRelatedResourceProvider $provider,
		FederatedUser $entity
	): string {
		return 'relatedToEntity/' . $provider->getProviderId() . '::' . $entity->getSingleId();
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
			if (!$entry->hasMeta(RelatedResource::LINK_RECIPIENT)) {
				continue;
			}

			try {
				$this->circlesManager->getLink($entry->getMeta(RelatedResource::LINK_RECIPIENT), $singleId);
				$filtered[] = $entry;
			} catch (MembershipNotFoundException $e) {
				$curr = $this->circlesManager->getCurrentFederatedUser();
				if ($curr->getUserType() === Member::TYPE_USER
					&& $entry->hasMeta(RelatedResource::ITEM_OWNER)
					&& $entry->getMeta(RelatedResource::ITEM_OWNER) === $curr->getUserId()) {
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

