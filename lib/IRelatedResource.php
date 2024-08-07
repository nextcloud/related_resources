<?php

declare(strict_types=1);


/**
 * SPDX-FileCopyrightText: 2022 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */


namespace OCA\RelatedResources;

interface IRelatedResource {
	public function getProviderId(): string;

	public function getItemId(): string;

	public function setTitle(string $title): self;

	public function getTitle(): string;

	public function setSubtitle(string $subtitle): self;

	public function getSubtitle(): string;

	public function setTooltip(string $tooltip): self;

	public function getTooltip(): string;

	public function setIcon(string $icon): self;

	public function getIcon(): string;

	public function setPreview(string $preview): self;

	public function getPreview(): string;

	public function setUrl(string $url): self;

	public function getUrl(): string;

	public function improve(float $quality, string $type, bool $diminishingReturn = true): self;

	public function getImprovements(): array;

	public function getScore(): float;

	public function setVirtualGroup(array $virtualGroup): self;

	public function getVirtualGroup(): array;

	public function addToVirtualGroup(string $singleId): self;

	public function mergeVirtualGroup(array $virtualGroup): self;

	public function setRecipients(array $recipients): self;

	public function getRecipients(): array;

	public function addRecipient(string $singleId): self;

	public function mergeRecipients(array $recipients): self;

	public function setAsGroupShared(bool $groupShared = true): self;

	public function isGroupShared(): bool;

	public function setMeta(string $k, string $v): self;

	public function setMetaInt(string $k, int $v): self;

	public function setMetaArray(string $k, array $v): self;

	public function setMetas(array $metas): self;

	public function hasMeta(string $k): bool;

	public function getMeta(string $k): string;

	public function getMetaInt(string $k): int;

	public function getMetaArray(string $k): array;

	public function getMetas(): array;
}
