<?php

declare(strict_types=1);


/**
 * SPDX-FileCopyrightText: 2022 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */


namespace OCA\RelatedResources\Service;

use OCA\RelatedResources\AppInfo\Application;
use OCA\RelatedResources\Tools\Traits\TArrayTools;
use OCP\IConfig;

class ConfigService {
	use TArrayTools;

	private IConfig $config;

	public const RESULT_MAX = 'result_max';

	private static $defaults = [
		self::RESULT_MAX => 7
	];

	public function __construct(IConfig $config) {
		$this->config = $config;
	}

	public function unsetAppConfig(): void {
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
}
