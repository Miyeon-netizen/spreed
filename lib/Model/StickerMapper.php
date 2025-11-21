<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Talk\Model;

use OCP\AppFramework\Db\QBMapper;
use OCP\DB\QueryBuilder\IQueryBuilder;
use OCP\IDBConnection;

/**
 * @template-extends QBMapper<Sticker>
 */
class StickerMapper extends QBMapper {
	public function __construct(IDBConnection $db) {
		parent::__construct($db, 'talk_stickers', Sticker::class);
	}

	/**
	 * @return Sticker[]
	 */
	public function findByCategory(int $categoryId): array {
		$qb = $this->db->getQueryBuilder();

		$qb->select('*')
			->from($this->getTableName())
			->where($qb->expr()->eq('category_id', $qb->createNamedParameter($categoryId)))
			->orderBy('id', 'ASC');

		return $this->findEntities($qb);
	}

	public function findByNameAndCategory(string $name, int $categoryId): Sticker {
		$qb = $this->db->getQueryBuilder();

		$qb->select('*')
			->from($this->getTableName())
			->where($qb->expr()->eq('name', $qb->createNamedParameter($name)))
			->andWhere($qb->expr()->eq('category_id', $qb->createNamedParameter($categoryId)));

		return $this->findEntity($qb);
	}
}
