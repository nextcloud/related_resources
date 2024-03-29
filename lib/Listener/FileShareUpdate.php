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
		RelatedService $relatedService
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
