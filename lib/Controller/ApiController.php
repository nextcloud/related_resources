<?php

declare(strict_types=1);


/**
 * SPDX-FileCopyrightText: 2022 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */


namespace OCA\RelatedResources\Controller;

use Exception;
use OCA\Circles\CirclesManager;
use OCA\RelatedResources\Model\RelatedResource;
use OCA\RelatedResources\Service\ConfigService;
use OCA\RelatedResources\Service\RelatedService;
use OCA\RelatedResources\Tools\Traits\TDeserialize;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\DataResponse;
use OCP\AppFramework\OCS\OCSException;
use OCP\AppFramework\OCSController;
use OCP\AutoloadNotAllowedException;
use OCP\IRequest;
use OCP\IUserSession;
use OCP\Server;
use Psr\Container\ContainerExceptionInterface;
use Psr\Log\LoggerInterface;

class ApiController extends OcsController {
	use TDeserialize;


	private LoggerInterface $logger;
	private IUserSession $userSession;
	private RelatedService $relatedService;
	private ConfigService $configService;
	private ?CirclesManager $circlesManager = null;

	public function __construct(
		string $appName,
		IRequest $request,
		LoggerInterface $logger,
		IUserSession $userSession,
		RelatedService $relatedService,
		ConfigService $configService,
	) {
		parent::__construct($appName, $request);

		$this->logger = $logger;
		$this->userSession = $userSession;
		$this->relatedService = $relatedService;
		$this->configService = $configService;
		try {
			$this->circlesManager = Server::get(CirclesManager::class);
		} catch (ContainerExceptionInterface|AutoloadNotAllowedException $e) {
		}
	}


	/**
	 * @NoAdminRequired
	 *
	 * @param string $providerId
	 * @param string $itemId
	 * @param string $resourceType
	 * @return DataResponse
	 * @throws OCSException
	 */
	public function getRelatedResources(
		string $providerId,
		string $itemId,
		int $limit = 0,
		string $resourceType = '',
	): DataResponse {
		if (is_null($this->circlesManager)) {
			$this->logger->info('RelatedResources require Circles');

			return new DataResponse([]);
		}

		$limit = ($limit > 0) ? $limit : $this->configService->getAppValueInt(ConfigService::RESULT_MAX);
		try {
			$this->circlesManager->startSession();

			$result = $this->relatedService->getRelatedToItem(
				$providerId,
				$itemId,
				$limit,
				$resourceType
			);

			// cleanData on result, to not send useless data.
			$new = [];
			foreach ($result as $related) {
				$new[] = RelatedResource::cleanData($this->serialize($related));
			}

			return new DataResponse($new);
		} catch (Exception $e) {
			$this->logger->error($e->getMessage(), ['exception' => $e]);
			throw new OCSException(
				($e->getMessage() === '') ? get_class($e) : $e->getMessage(),
				Http::STATUS_BAD_REQUEST
			);
		}
	}
}
