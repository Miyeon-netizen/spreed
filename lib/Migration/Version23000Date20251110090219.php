<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Talk\Migration;

use Closure;
use OCP\DB\ISchemaWrapper;
use OCP\Migration\IOutput;
use OCP\Migration\SimpleMigrationStep;

class Version23000Date20251110090219 extends SimpleMigrationStep {

	/**
	 * @param IOutput $output
	 * @param Closure $schemaClosure The `\Closure` returns a `ISchemaWrapper`
	 * @param array $options
	 * @return null|ISchemaWrapper
	 */
	public function changeSchema(IOutput $output, Closure $schemaClosure, array $options) {
		/** @var ISchemaWrapper $schema */
		$schema = $schemaClosure();

		if (!$schema->hasTable('talk_sticker_categories')) {
			$table = $schema->createTable('talk_sticker_categories');
			$table->addColumn('id', 'integer', [
				'autoincrement' => true,
				'notnull' => true,
			]);
			$table->addColumn('name', 'string', [
				'notnull' => true,
				'length' => 64,
			]);
			$table->addColumn('user_id', 'string', [
				'notnull' => true,
				'length' => 64,
			]);
			$table->addColumn('order', 'integer', [
				'notnull' => true,
				'default' => 0,
			]);
			$table->setPrimaryKey(['id']);
			// Name should be unique per user
			$table->addUniqueIndex(['user_id', 'name'], 'talk_sticker_cat_uid_name');
			$table->addIndex(['user_id'], 'talk_sticker_categories_uid');
		}

		if (!$schema->hasTable('talk_stickers')) {
			$table = $schema->createTable('talk_stickers');
			$table->addColumn('id', 'integer', [
				'autoincrement' => true,
				'notnull' => true,
			]);
			$table->addColumn('category_id', 'integer', [
				'notnull' => true,
			]);
			$table->addColumn('name', 'string', [
				'notnull' => true,
				'length' => 64,
			]);
			$table->addColumn('path', 'string', [
				'notnull' => true,
				'length' => 255,
			]);
			$table->addColumn('mime_type', 'string', [
				'notnull' => true,
				'length' => 255,
			]);
			$table->addColumn('uploaded_by', 'string', [
				'notnull' => false,
				'length' => 64,
			]);
			$table->addColumn('upload_time', 'integer', [
				'notnull' => true,
				'default' => 0,
			]);
			$table->setPrimaryKey(['id']);
			$table->addIndex(['category_id'], 'talk_stickers_category_id');
		}

		return $schema;
	}
}
