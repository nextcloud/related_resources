<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2022 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\RelatedResources\AppInfo;

use OCA\Files\Event\LoadSidebar;
use OCA\RelatedResources\Listener\FileShareUpdate;
use OCA\RelatedResources\Listener\LoadSidebarScript;
use OCP\AppFramework\App;
use OCP\AppFramework\Bootstrap\IBootContext;
use OCP\AppFramework\Bootstrap\IBootstrap;
use OCP\AppFramework\Bootstrap\IRegistrationContext;
use OCP\Share\Events\ShareCreatedEvent;
use OCP\Share\Events\ShareDeletedEvent;
use Throwable;

/**
 * Class Application
 *
 * @package OCA\RelatedResources\AppInfo
 */
class Application extends App implements IBootstrap {
	public const APP_ID = 'related_resources';


	/**
	 * @param array $params
	 */
	public function __construct(array $params = []) {
		parent::__construct(self::APP_ID, $params);
	}


	/**
	 * @param IRegistrationContext $context
	 */
	public function register(IRegistrationContext $context): void {
		$context->registerEventListener(LoadSidebar::class, LoadSidebarScript::class);
		$context->registerEventListener(ShareCreatedEvent::class, FileShareUpdate::class);
		$context->registerEventListener(ShareDeletedEvent::class, FileShareUpdate::class);
	}


	/**
	 * @param IBootContext $context
	 *
	 * @throws Throwable
	 */
	public function boot(IBootContext $context): void {
	}
}
