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
use OCA\RelatedResources\AppInfo\Application;
use OCA\RelatedResources\Exceptions\RelatedResourceProviderNotFound;
use OCA\RelatedResources\ILinkWeightCalculator;
use OCA\RelatedResources\IRelatedResource;
use OCA\RelatedResources\IRelatedResourceProvider;
use OCA\RelatedResources\LinkWeightCalculators\TimeWeightCalculator;
use OCA\RelatedResources\Model\RelatedResource;
use OCA\RelatedResources\RelatedResourceProviders\DeckRelatedResourceProvider;
use OCA\RelatedResources\RelatedResourceProviders\FilesRelatedResourceProvider;
use OCA\RelatedResources\RelatedResourceProviders\TalkRelatedResourceProvider;
use OCA\RelatedResources\Tools\Exceptions\ItemNotFoundException;
use OCA\RelatedResources\Tools\Traits\TNCLogger;
use ReflectionClass;
use ReflectionException;

class RelatedService {
	use TNCLogger;

	private CirclesManager $circlesManager;

	/** @var ILinkWeightCalculator[] */
	private array $weightCalculators = [];

	/** @var string[] */
	private static array $weightCalculators_ = [
		TimeWeightCalculator::class
	];

	public function __construct() {
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
		$recipients = $this->getRelatedResourceProvider($providerId)
						   ->getSharesRecipients($itemId);

		$this->debug('recipients returned by ' . $providerId . ': ' . json_encode($recipients));
//		$this->filterRecipients($recipients);

		$result = $itemPaths = [];
		foreach ($this->getRelatedResourceProviders() as $provider) {
			$known = [];

			foreach ($recipients as $entity) {
				foreach ($provider->getRelatedToEntity($entity) as $related) {
					$related->setLinkRecipient($entity->getSingleId());

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

					// if RelatedResource is based on current item, store it for weightResult() later in the process
					if ($provider->getProviderId() === $providerId && $related->getItemId() === $itemId) {
						$itemPaths[] = $related;
					} else {
						$result[] = $related;
					}

					$known[] = $related->getItemId();
				}
			}
		}

		if (!empty($itemPaths)) {
			$this->weightResult($itemPaths, $result);
		}

		return $result;
	}


	/**
	 * @param IRelatedResource[] $paths
	 * @param IRelatedResource[] $result
	 *
	 * @return void
	 */
	private function weightResult(array $paths, array &$result): void {
		// eventually, add the score of paths to related result if doable.

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
		return [
			\OC::$server->get(FilesRelatedResourceProvider::class),
			\OC::$server->get(DeckRelatedResourceProvider::class),
			\OC::$server->get(TalkRelatedResourceProvider::class),
		];
	}

	/**
	 * @param string $relatedProviderId
	 *
	 * @return IRelatedResourceProvider
	 * @throws RelatedResourceProviderNotFound
	 */
	private function getRelatedResourceProvider(string $relatedProviderId): IRelatedResourceProvider {
		foreach ($this->getRelatedResourceProviders() as $provider) {
			if ($provider->getProviderId() === $relatedProviderId) {
				return $provider;
			}
		}

		throw new RelatedResourceProviderNotFound();
	}
}

