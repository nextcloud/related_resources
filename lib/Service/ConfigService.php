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


use OCA\RelatedResources\AppInfo\Application;
use OCA\RelatedResources\Tools\Traits\TArrayTools;
use OCP\IConfig;


/**
 * Class ConfigService
 *
 * @package OCA\RelatedResources\Service
 */
class ConfigService {
	use TArrayTools;

	private IConfig $config;

	public const RESULT_MAX = 'result_max';
	public const STATS_CACHE = 'stats_cache';
	public const LIMIT_CIRCLE = 'limit_circle';

	private static $defaults = [
		self::RESULT_MAX => 7,
		self::STATS_CACHE => '[]',
		self::LIMIT_CIRCLE => ''
	];

	public function __construct(IConfig $config) {
		$this->config = $config;
	}

	public function unsetAppConfig() {
		$this->config->deleteAppValues(Application::APP_ID);
	}

	public function setAppValue(string $key, string $value): void {
		$this->config->setAppValue(Application::APP_ID, $key, $value);
	}

	public function getAppValue(string $key): string {
		if (($value = $this->config->getAppValue(Application::APP_ID, $key, '')) !== '') {
			return $value;
		}

		if (($value = $this->config->getSystemValue(Application::APP_ID . '.' . $key, '')) !== '') {
			return $value;
		}

		return $this->get($key, self::$defaults);
	}

	/**
	 * @param string $key
	 *
	 * @return int
	 */
	public function getAppValueInt(string $key): int {
		return (int)$this->getAppValue($key);
	}

	/**
	 * @param string $key
	 *
	 * @return bool
	 */
	public function getAppValueBool(string $key): bool {
		return ($this->getAppValueInt($key) === 1);
	}

	public function updateConfigValues(
		int $requestShares,
		int $cachedShares,
		int $requestRelated,
		int $cachedRelated
	) {
		$curr = json_decode($this->getAppValue(self::STATS_CACHE), true);
		$new = [
			'requestShares' => ($curr['requestShares'] ?? 0) + $requestShares,
			'cachedShares' => ($curr['cachedShares'] ?? 0) + $cachedShares,
			'requestRelated' => ($curr['requestRelated'] ?? 0) + $requestRelated,
			'cachedRelated' => ($curr['cachedRelated'] ?? 0) + $cachedRelated
		];

		$this->setAppValue(self::STATS_CACHE, json_encode($new));
	}
}

