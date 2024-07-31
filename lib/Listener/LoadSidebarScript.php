<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2019 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */


namespace OCA\RelatedResources\Listener;

use OCA\Files\Event\LoadSidebar;
use OCA\RelatedResources\AppInfo\Application;
use OCP\EventDispatcher\Event;
use OCP\EventDispatcher\IEventListener;
use OCP\Util;

/**
 * @template-implements IEventListener<Event>
 */
class LoadSidebarScript implements IEventListener {
	public function handle(Event $event): void {
		if (!($event instanceof LoadSidebar)) {
			return;
		}

		Util::addScript(Application::APP_ID, 'related_resources');
	}
}
