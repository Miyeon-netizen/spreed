<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Talk\Model;

use OCP\AppFramework\Db\Entity;

/**
 * @method string getName()
 * @method void setName(string $name)
 * @method string getUserId()
 * @method void setUserId(string $userId)
 * @method int getOrder()
 * @method void setOrder(int $order)
 */
class StickerCategory extends Entity {
	protected $name;
	protected $userId;
	protected $order;

	public function __construct() {
		$this->addType('name', 'string');
		$this->addType('userId', 'string');
		$this->addType('order', 'integer');
	}

	public function jsonSerialize(): array {
		return [
			'id' => $this->id,
			'name' => $this->name,
			'userId' => $this->userId,
			'order' => $this->order,
		];
	}
}
