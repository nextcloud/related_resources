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
use OCA\Circles\Model\FederatedUser;
use OCA\Circles\Model\Member;
use OCP\Share\IShare;

class MiscService {
	private ?CirclesManager $circlesManager = null;

	public function __construct() {
		try {
			$this->circlesManager = \OC::$server->get(CirclesManager::class);
		} catch (Exception $e) {
		}
	}


	/**
	 * @param int $shareType
	 * @param string $sharedWith
	 *
	 * @return FederatedUser
	 * @throws Exception
	 */
	public function convertShareRecipient(int $shareType, string $sharedWith): FederatedUser {
		if (is_null($this->circlesManager)) {
			throw new Exception('Circles needs to be enabled');
		}

		switch ($shareType) {
//			case IShare::TYPE_USER:
//				$type = Member::TYPE_USER;
//				break;

			case IShare::TYPE_GROUP:
				$type = Member::TYPE_GROUP;
				break;

			case IShare::TYPE_CIRCLE:
				$type = Member::TYPE_SINGLE;
				break;

			default:
				throw new Exception();
		}

		return $this->circlesManager->getFederatedUser($sharedWith, $type);
	}
}
