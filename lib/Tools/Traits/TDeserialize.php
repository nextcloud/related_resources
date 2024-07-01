<?php

declare(strict_types=1);


/**
 * SPDX-FileCopyrightText: 2022 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */


namespace OCA\RelatedResources\Tools\Traits;

use Exception;
use JsonSerializable;
use OCA\RelatedResources\Tools\Exceptions\InvalidItemException;
use OCA\RelatedResources\Tools\IDeserializable;
use ReflectionClass;

trait TDeserialize {
	/**
	 * @param JsonSerializable $model
	 *
	 * @return array
	 */
	public function serialize(JsonSerializable $model): array {
		return json_decode(json_encode($model), true);
	}

	/**
	 * @param array $data
	 *
	 * @return array
	 */
	public function serializeArray(array $data): array {
		return json_decode(json_encode($data), true);
	}


	/**
	 * @param array $data
	 * @param string $class
	 *
	 * @return IDeserializable
	 * @throws InvalidItemException
	 */
	public function deserialize(array $data, string $class): IDeserializable {
		try {
			$test = new ReflectionClass($class);
		} catch (\ReflectionException $e) {
			throw new InvalidItemException('cannot ReflectionClass ' . $class);
		}

		if (!in_array(IDeserializable::class, $test->getInterfaceNames())) {
			throw new InvalidItemException($class . ' does not implement IDeserializable');
		}

		/** @var IDeserializable $item */
		$item = new $class;
		$item->import($data);

		return $item;
	}


	/**
	 * force deserialize without checking for implementation of IDeserializable.
	 * quickest solution to deserialize model from other apps.
	 *
	 * @param string $json
	 * @param string $class
	 *
	 * @return array
	 */
	public function forceDeserializeArrayFromJson(string $json, string $class): array {
		$data = json_decode($json, true);
		if (!is_array($data)) {
			return [];
		}

		$arr = [];
		foreach ($data as $entry) {
			try {
				$item = new $class;
				$arr[] = $item->import($entry);
			} catch (Exception $e) {
			}
		}

		return $arr;
	}

	/**
	 * @param string $json
	 * @param string $class
	 *
	 * @return IDeserializable[]
	 */
	public function deserializeArrayFromJson(string $json, string $class): array {
		$data = json_decode($json, true);
		if (!is_array($data)) {
			return [];
		}

		return $this->deserializeArray($data, $class);
	}

	/**
	 * @param array $data
	 * @param string $class
	 *
	 * @return array
	 */
	public function deserializeArray(array $data, string $class): array {
		$arr = [];
		foreach ($data as $entry) {
			try {
				$arr[] = $this->deserialize($entry, $class);
			} catch (InvalidItemException $e) {
			}
		}

		return $arr;
	}


	/**
	 * @param string $json
	 * @param string $class
	 *
	 * @return IDeserializable
	 * @throws InvalidItemException
	 */
	public function deserializeJson(string $json, string $class): IDeserializable {
		$data = json_decode($json, true);

		return $this->deserialize($data, $class);
	}
}
