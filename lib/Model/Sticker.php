<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Talk\Model;

use OCP\AppFramework\Db\Entity;

/**
 * @method int getCategoryId()
 * @method void setCategoryId(int $categoryId)
 * @method string getName()
 * @method void setName(string $name)
 * @method string getPath()
 * @method void setPath(string $path)
 * @method string getMimeType()
 * @method void setMimeType(string $mimeType)
 * @method string|null getUploadedBy()
 * @method void setUploadedBy(string|null $uploadedBy)
 * @method int getUploadTime()
 * @method void setUploadTime(int $uploadTime)
 */
class Sticker extends Entity {
	protected $categoryId;
	protected $name;
	protected $path;
	protected $mimeType;
	protected $uploadedBy;
	protected $uploadTime;

	public function __construct() {
		$this->addType('categoryId', 'integer');
		$this->addType('name', 'string');
		$this->addType('path', 'string');
		$this->addType('mimeType', 'string');
		$this->addType('uploadedBy', 'string');
		$this->addType('uploadTime', 'integer');
	}

	public function jsonSerialize(): array {
		return [
			'id' => $this->id,
			'categoryId' => $this->categoryId,
			'name' => $this->name,
			'path' => $this->path,
			'mimeType' => $this->mimeType,
			'uploadedBy' => $this->uploadedBy,
			'uploadTime' => $this->uploadTime,
		];
	}
}
