<?php

declare(strict_types=1);


/**
 * SPDX-FileCopyrightText: 2022 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */


namespace OCA\RelatedResources\Tools\Db;

/**
 * Interface IQueryRow
 *
 * @package OCA\RelatedResources\Tools\Db
 */
interface IQueryRow {
	/**
	 * import data to feed the model.
	 *
	 * @param array $data
	 *
	 * @return IQueryRow
	 */
	public function importFromDatabase(array $data): self;
}
