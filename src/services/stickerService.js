import axios from '@nextcloud/axios'
import { generateOcsUrl } from '@nextcloud/router'

export function getCategories() {
	return axios.get(generateOcsUrl('apps/spreed/api/v1/sticker/categories'))
		.then((response) => response.data.ocs.data)
}

export function createCategory(name, order = 0) {
	return axios.post(generateOcsUrl('apps/spreed/api/v1/sticker/categories'), { name, order })
		.then((response) => response.data.ocs.data)
}

export function getStickers(categoryId) {
	return axios.get(generateOcsUrl(`apps/spreed/api/v1/sticker/categories/${categoryId}/stickers`))
		.then((response) => response.data.ocs.data)
}

export function uploadSticker(categoryId, file) {
	const formData = new FormData()
	formData.append('file', file)

	return axios.post(generateOcsUrl(`apps/spreed/api/v1/sticker?categoryId=${categoryId}`), formData)
		.then((response) => response.data.ocs.data)
}

export function deleteSticker(stickerId) {
	return axios.delete(generateOcsUrl(`apps/spreed/api/v1/sticker/${stickerId}`))
}
