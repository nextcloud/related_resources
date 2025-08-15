<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2022 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\RelatedResources\Listener;

use Exception;
use OCA\RelatedResources\Service\RelatedService;
use OCP\EventDispatcher\Event;
use OCP\EventDispatcher\IEventListener;
use OCP\Share\Events\ShareCreatedEvent;
use OCP\Share\Events\ShareDeletedEvent;

/**
 * @template-implements IEventListener<Event>
 */
class FileShareUpdate implements IEventListener {
	private RelatedService $relatedService;

	public function __construct(
		RelatedService $relatedService,
	) {
		$this->relatedService = $relatedService;
	}

	public function handle(Event $event): void {
		if (!($event instanceof ShareCreatedEvent)
			&& !($event instanceof ShareDeletedEvent)) {
			return;
		}

		try {
			$this->relatedService->flushCache();
		} catch (Exception $e) {
		}
	}
}
