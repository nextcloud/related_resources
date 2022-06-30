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


namespace OCA\RelatedResources\Db;


use OC\DB\Connection;
use OC\DB\SchemaWrapper;
use OCA\RelatedResources\AppInfo\Application;
use OCA\RelatedResources\Service\ConfigService;


/**
 *
 */
class CoreQueryBuilder {


	public const TABLE_FILES_SHARE = 'share';

	public const TABLE_DECK_SHARE = 'deck_board_acl';
	public const TABLE_DECK_BOARD = 'deck_boards';

	public const TABLE_TALK_ATTENDEE = 'talk_attendees';
	public const TABLE_TALK_ROOM = 'talk_rooms';

	protected ConfigService $configService;
	private array $tables = [
	];


	public static array $externalTables = [
		self::TABLE_FILES_SHARE => [
			'share_type',
			'share_with',
			'file_source',
			'file_target'
		],
		self::TABLE_DECK_SHARE => [
			'board_id',
			'type',
			'participant'
		],
		self::TABLE_DECK_BOARD => [
			'title'
		],
		self::TABLE_TALK_ATTENDEE => [
			'room_id',
			'actor_type',
			'actor_id'
		],
		self::TABLE_TALK_ROOM => [
			'name',
			'token'
		]
	];


	/**
	 * @param ConfigService $configService
	 */
	public function __construct(ConfigService $configService) {
		$this->configService = $configService;
	}


	/**
	 * @return CoreRequestBuilder
	 */
	public function getQueryBuilder(): CoreRequestBuilder {
		return new CoreRequestBuilder();
	}


	/**
	 *
	 */
	public function cleanDatabase(): void {
		foreach ($this->tables as $table) {
			$qb = $this->getQueryBuilder();
			$qb->delete($table);
			$qb->execute();
		}
	}

	/**
	 *
	 */
	public function uninstall(): void {
		$this->uninstallAppTables();
		$this->uninstallFromMigrations();
		$this->configService->unsetAppConfig();
	}

	/**
	 * this just empty all tables from the app.
	 */
	public function uninstallAppTables() {
		$dbConn = \OC::$server->get(Connection::class);
		$schema = new SchemaWrapper($dbConn);

		foreach ($this->tables as $table) {
			if ($schema->hasTable($table)) {
				$schema->dropTable($table);
			}
		}

		$schema->performDropTableCalls();
	}


	/**
	 *
	 */
	public function uninstallFromMigrations() {
		$qb = $this->getQueryBuilder();
		$qb->delete('migrations');
		$qb->limit('app', Application::APP_ID);

		$qb->execute();
	}

}

