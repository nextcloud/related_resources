<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2020 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OC\DB;

use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Schema\Schema;
use OC\DB\QueryBuilder\Sharded\CrossShardMoveHelper;
use OC\DB\QueryBuilder\Sharded\ShardDefinition;
use OCP\DB\IPreparedStatement;
use OCP\DB\IResult;
use OCP\DB\QueryBuilder\IQueryBuilder;
use OCP\DB\QueryBuilder\ITypedQueryBuilder;
use OCP\IDBConnection;

/**
 * Adapts the public API to our internal DBAL connection wrapper
 */
class ConnectionAdapter implements IDBConnection {
	public function __construct(
		private Connection $inner,
	) {
	}

	#[\Override]
	public function getQueryBuilder(): IQueryBuilder {
	}

	#[\Override]
	public function getTypedQueryBuilder(): ITypedQueryBuilder {
	}

	#[\Override]
	public function prepare($sql, $limit = null, $offset = null): IPreparedStatement {
	}

	#[\Override]
	public function executeQuery(string $sql, array $params = [], $types = []): IResult {
	}

	#[\Override]
	public function executeUpdate(string $sql, array $params = [], array $types = []): int {
	}

	#[\Override]
	public function executeStatement($sql, array $params = [], array $types = []): int {
	}

	#[\Override]
	public function lastInsertId(string $table): int {
	}

	#[\Override]
	public function insertIfNotExist(string $table, array $input, ?array $compare = null) {
	}

	#[\Override]
	public function insertIgnoreConflict(string $table, array $values): int {
	}

	#[\Override]
	public function setValues($table, array $keys, array $values, array $updatePreconditionValues = []): int {
	}

	#[\Override]
	public function lockTable($tableName): void {
	}

	#[\Override]
	public function unlockTable(): void {
	}

	#[\Override]
	public function beginTransaction(): void {
	}

	#[\Override]
	public function inTransaction(): bool {
	}

	#[\Override]
	public function commit(): void {
	}

	#[\Override]
	public function rollBack(): void {
	}

	#[\Override]
	public function getError(): string {
	}

	#[\Override]
	public function errorCode() {
	}

	#[\Override]
	public function errorInfo() {
	}

	#[\Override]
	public function connect(): bool {
	}

	#[\Override]
	public function close(): void {
	}

	#[\Override]
	public function quote($input, $type = IQueryBuilder::PARAM_STR) {
	}

	/**
	 * @todo we are leaking a 3rdparty type here
	 */
	#[\Override]
	public function getDatabasePlatform(): AbstractPlatform {
	}

	#[\Override]
	public function dropTable(string $table): void {
	}

	#[\Override]
	public function truncateTable(string $table, bool $cascade): void {
	}

	#[\Override]
	public function tableExists(string $table): bool {
	}

	#[\Override]
	public function escapeLikeParameter(string $param): string {
	}

	#[\Override]
	public function supports4ByteText(): bool {
	}

	/**
	 * @todo leaks a 3rdparty type
	 */
	#[\Override]
	public function createSchema(): Schema {
	}

	#[\Override]
	public function migrateToSchema(Schema $toSchema): void {
	}

	public function getInner(): Connection {
	}

	/**
	 * @return self::PLATFORM_MYSQL|self::PLATFORM_ORACLE|self::PLATFORM_POSTGRES|self::PLATFORM_SQLITE|self::PLATFORM_MARIADB
	 */
	#[\Override]
	public function getDatabaseProvider(bool $strict = false): string {
	}

	/**
	 * @internal Should only be used inside the QueryBuilder, ExpressionBuilder and FunctionBuilder
	 * All apps and API code should not need this and instead use provided functionality from the above.
	 */
	public function getServerVersion(): string {
	}

	public function logDatabaseException(\Exception $exception) {
	}

	#[\Override]
	public function getShardDefinition(string $name): ?ShardDefinition {
	}

	#[\Override]
	public function getCrossShardMoveHelper(): CrossShardMoveHelper {
	}
}
