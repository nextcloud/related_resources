<?php

declare(strict_types=1);


/**
 * SPDX-FileCopyrightText: 2022 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */


namespace OCA\RelatedResources\Db;

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

	public const TABLE_DAV_SHARE = 'dav_shares';
	public const TABLE_CALENDARS = 'calendars';
	public const TABLE_CAL_OBJECTS = 'calendarobjects';
	public const TABLE_CAL_OBJ_PROPS = 'calendarobjects_props';

	protected ConfigService $configService;

	public static array $externalTables = [
		self::TABLE_FILES_SHARE => [
			'share_type',
			'share_with',
			'uid_owner',
			'uid_initiator',
			'file_source',
			'file_target',
			'stime'
		],
		self::TABLE_DECK_SHARE => [
			'board_id',
			'type',
			'participant'
		],
		self::TABLE_DECK_BOARD => [
			'id',
			'title',
			'owner',
			'last_modified'
		],
		self::TABLE_TALK_ATTENDEE => [
			'room_id',
			'actor_type',
			'actor_id'
		],
		self::TABLE_TALK_ROOM => [
			'name',
			'type',
			'token'
		],
		self::TABLE_DAV_SHARE => [
			'principaluri',
			'resourceid'
		],
		self::TABLE_CALENDARS => [
			'id',
			'principaluri',
			'uri',
			'displayname'
		],
		self::TABLE_CAL_OBJECTS => [
			'firstoccurence',
			'lastoccurence'
		],
		self::TABLE_CAL_OBJ_PROPS => [
			'value'
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
}
