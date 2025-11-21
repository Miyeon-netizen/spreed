<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Talk\Controller;

use OCA\Talk\Model\Sticker;
use OCA\Talk\Model\StickerCategory;
use OCA\Talk\Model\StickerCategoryMapper;
use OCA\Talk\Model\StickerMapper;
use OCP\AppFramework\Db\DoesNotExistException;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\Attribute\ApiRoute;
use OCP\AppFramework\Http\DataResponse;
use OCP\AppFramework\OCS\OCSNotFoundException;
use OCP\Files\IAppData;
use OCP\Files\NotFoundException;
use OCP\IRequest;
use OCP\IURLGenerator;
use OCP\IUserSession;

class StickerController extends AEnvironmentAwareOCSController {
	private StickerCategoryMapper $stickerCategoryMapper;
	private StickerMapper $stickerMapper;
	private IAppData $appData;
	private IURLGenerator $urlGenerator;
	private IUserSession $userSession;

	public function __construct(
		string $appName,
		IRequest $request,
		StickerCategoryMapper $stickerCategoryMapper,
		StickerMapper $stickerMapper,
		IAppData $appData,
		IURLGenerator $urlGenerator,
		IUserSession $userSession
	) {
		parent::__construct($appName, $request);
		$this->stickerCategoryMapper = $stickerCategoryMapper;
		$this->stickerMapper = $stickerMapper;
		$this->appData = $appData;
		$this->urlGenerator = $urlGenerator;
		$this->userSession = $userSession;
	}

	#[ApiRoute(verb: 'GET', url: '/api/{apiVersion}/sticker/categories', requirements: ['apiVersion' => '(v1)'])]
	public function getCategories(): DataResponse {
		$user = $this->userSession->getUser();
		if (!$user) {
			return new DataResponse([], Http::STATUS_UNAUTHORIZED);
		}

		$categories = $this->stickerCategoryMapper->findAllForUser($user->getUID());
		return new DataResponse($categories);
	}

	#[ApiRoute(verb: 'POST', url: '/api/{apiVersion}/sticker/categories', requirements: ['apiVersion' => '(v1)'])]
	public function createCategory(string $name, int $order = 0): DataResponse {
		$user = $this->userSession->getUser();
		if (!$user) {
			return new DataResponse([], Http::STATUS_UNAUTHORIZED);
		}

		try {
			$this->stickerCategoryMapper->findByNameForUser($name, $user->getUID());
			return new DataResponse(['error' => 'Category already exists'], Http::STATUS_BAD_REQUEST);
		} catch (DoesNotExistException $e) {
			// Category does not exist, continue
		}

		$category = new StickerCategory();
		$category->setName($name);
		$category->setUserId($user->getUID());
		$category->setOrder($order);

		$this->stickerCategoryMapper->insert($category);

		return new DataResponse($category, Http::STATUS_CREATED);
	}

	#[ApiRoute(verb: 'GET', url: '/api/{apiVersion}/sticker/categories/{categoryId}/stickers', requirements: ['apiVersion' => '(v1)', 'categoryId' => '[0-9]+'])]
	public function getStickers(int $categoryId): DataResponse {
		$user = $this->userSession->getUser();
		if (!$user) {
			return new DataResponse([], Http::STATUS_UNAUTHORIZED);
		}

		try {
			$category = $this->stickerCategoryMapper->find($categoryId);
			if ($category->getUserId() !== $user->getUID()) {
				throw new OCSNotFoundException('Category not found');
			}
		} catch (DoesNotExistException|OCSNotFoundException $e) {
			return new DataResponse(['error' => 'Category not found'], Http::STATUS_NOT_FOUND);
		}

		$stickers = $this->stickerMapper->findByCategory($categoryId);

		$result = [];
		foreach ($stickers as $sticker) {
			$s = $sticker->jsonSerialize();
			$s['url'] = $this->urlGenerator->linkToRouteAbsolute('spreed.sticker.download', ['stickerId' => $sticker->getId()]);
			$result[] = $s;
		}

		return new DataResponse($result);
	}

	#[ApiRoute(verb: 'POST', url: '/api/{apiVersion}/sticker', requirements: ['apiVersion' => '(v1)'])]
	public function uploadSticker(int $categoryId): DataResponse {
		$user = $this->userSession->getUser();
		if (!$user) {
			return new DataResponse([], Http::STATUS_UNAUTHORIZED);
		}

		try {
			$category = $this->stickerCategoryMapper->find($categoryId);
			if ($category->getUserId() !== $user->getUID()) {
				throw new OCSNotFoundException('Category not found');
			}
		} catch (DoesNotExistException|OCSNotFoundException $e) {
			return new DataResponse(['error' => 'Category not found'], Http::STATUS_NOT_FOUND);
		}

		$file = $this->request->getUploadedFile('file');
		if (!$file) {
			return new DataResponse(['error' => 'No file provided'], Http::STATUS_BAD_REQUEST);
		}

		// Max size 1MB
		if ($file->getSize() > 1024 * 1024) {
			return new DataResponse(['error' => 'File too large. Max 1MB.'], Http::STATUS_BAD_REQUEST);
		}

		// Validate MIME type
		$finfo = new \finfo(FILEINFO_MIME_TYPE);
		$realMimeType = $finfo->file($file->getTempName());

		$allowedMimeTypes = [
			'image/png',
			'image/jpeg',
			'image/gif',
			'image/webp',
			'image/svg+xml'
		];

		if (!in_array($realMimeType, $allowedMimeTypes, true)) {
			return new DataResponse(['error' => 'Invalid file type. Allowed types: PNG, JPEG, GIF, WEBP, SVG'], Http::STATUS_BAD_REQUEST);
		}

		// Save file to AppData
		try {
			$folder = $this->appData->getFolder('stickers');
		} catch (NotFoundException $e) {
			$folder = $this->appData->newFolder('stickers');
		}

		try {
			$categoryFolder = $folder->getFolder((string)$categoryId);
		} catch (NotFoundException $e) {
			$categoryFolder = $folder->newFolder((string)$categoryId);
		}

		$fileName = uniqid() . '-' . $file->getClientOriginalName();

		try {
			$newFile = $categoryFolder->newFile($fileName);
			$newFile->putContent(file_get_contents($file->getTempName()));
		} catch (\Exception $e) {
			return new DataResponse(['error' => 'Could not save file'], Http::STATUS_INTERNAL_SERVER_ERROR);
		}

		$sticker = new Sticker();
		$sticker->setCategoryId($categoryId);
		$sticker->setName($file->getClientOriginalName());
		$sticker->setPath('stickers/' . $categoryId . '/' . $fileName);
		$sticker->setMimeType($realMimeType);
		$sticker->setUploadedBy($user->getUID());
		$sticker->setUploadTime(time());

		$this->stickerMapper->insert($sticker);

		$s = $sticker->jsonSerialize();
		$s['url'] = $this->urlGenerator->linkToRouteAbsolute('spreed.sticker.download', ['stickerId' => $sticker->getId()]);

		return new DataResponse($s, Http::STATUS_CREATED);
	}

	#[ApiRoute(verb: 'DELETE', url: '/api/{apiVersion}/sticker/{stickerId}', requirements: ['apiVersion' => '(v1)', 'stickerId' => '[0-9]+'])]
	public function deleteSticker(int $stickerId): DataResponse {
		try {
			$sticker = $this->stickerMapper->find($stickerId);
		} catch (OCSNotFoundException|DoesNotExistException $e) {
			return new DataResponse(['error' => 'Sticker not found'], Http::STATUS_NOT_FOUND);
		}

		$user = $this->userSession->getUser();
		if (!$user || $sticker->getUploadedBy() !== $user->getUID()) {
			return new DataResponse(['error' => 'Forbidden'], Http::STATUS_FORBIDDEN);
		}

		try {
			$file = $this->appData->getFile($sticker->getPath());
			$file->delete();
		} catch (NotFoundException $e) {
			// File missing, just delete the record
		}

		$this->stickerMapper->delete($sticker);

		return new DataResponse([], Http::STATUS_OK);
	}

	#[ApiRoute(verb: 'GET', url: '/api/{apiVersion}/sticker/{stickerId}/image', requirements: ['apiVersion' => '(v1)', 'stickerId' => '[0-9]+'])]
	public function download(int $stickerId): Http\Response {
		// Check if authenticated
		if (!$this->userSession->isLoggedIn()) {
			return new DataResponse([], Http::STATUS_UNAUTHORIZED);
		}

		try {
			$sticker = $this->stickerMapper->find($stickerId);
		} catch (OCSNotFoundException|DoesNotExistException $e) {
			return new DataResponse(['error' => 'Sticker not found'], Http::STATUS_NOT_FOUND);
		}

		try {
			$file = $this->appData->getFile($sticker->getPath());
		} catch (NotFoundException $e) {
			return new DataResponse(['error' => 'File not found'], Http::STATUS_NOT_FOUND);
		}

		return new Http\DataDownloadResponse($file->getContent(), $sticker->getName(), $sticker->getMimeType());
	}
}
