<template>
	<div class="sticker-picker">
		<div class="sticker-picker__categories">
			<button
				v-for="category in categories"
				:key="category.id"
				:class="{ active: currentCategory === category.id }"
				@click="selectCategory(category.id)">
				{{ category.name }}
			</button>
			<button @click="isCreatingCategory = true" class="add-category">+</button>
		</div>

		<div v-if="isCreatingCategory" class="sticker-picker__new-category">
			<input v-model="newCategoryName" placeholder="New category name" />
			<button @click="addCategory">Create</button>
			<button @click="isCreatingCategory = false">Cancel</button>
		</div>

		<div v-if="currentCategory" class="sticker-picker__stickers">
			<div v-if="loading" class="loading">Loading...</div>
			<div v-else class="stickers-grid">
				<div
					v-for="sticker in stickers"
					:key="sticker.id"
					class="sticker-item"
					@click="$emit('select', sticker)">
					<img :src="sticker.url" :alt="sticker.name" />
				</div>
				<div class="upload-sticker">
					<input type="file" @change="handleUpload" accept="image/*" />
					<span>Upload Sticker</span>
				</div>
			</div>
		</div>
		<div v-else class="no-category-selected">
			Select a category to view stickers
		</div>
	</div>
</template>

<script>
import { getCategories, getStickers, uploadSticker, createCategory } from '../../services/stickerService'

export default {
	name: 'StickerPicker',
	data() {
		return {
			categories: [],
			stickers: [],
			currentCategory: null,
			loading: false,
			isCreatingCategory: false,
			newCategoryName: '',
		}
	},
	async mounted() {
		await this.loadCategories()
	},
	methods: {
		async loadCategories() {
			try {
				this.categories = await getCategories()
				if (this.categories.length > 0) {
					this.selectCategory(this.categories[0].id)
				}
			} catch (error) {
				console.error('Failed to load sticker categories', error)
			}
		},
		async selectCategory(categoryId) {
			this.currentCategory = categoryId
			this.loading = true
			try {
				this.stickers = await getStickers(categoryId)
			} catch (error) {
				console.error('Failed to load stickers', error)
			} finally {
				this.loading = false
			}
		},
		async handleUpload(event) {
			const file = event.target.files[0]
			if (!file || !this.currentCategory) return

			try {
				const newSticker = await uploadSticker(this.currentCategory, file)
				this.stickers.push(newSticker)
			} catch (error) {
				console.error('Failed to upload sticker', error)
			}
		},
		async addCategory() {
			if (!this.newCategoryName) return
			try {
				const newCategory = await createCategory(this.newCategoryName)
				this.categories.push(newCategory)
				this.isCreatingCategory = false
				this.newCategoryName = ''
				this.selectCategory(newCategory.id)
			} catch (error) {
				console.error('Failed to create category', error)
			}
		}
	}
}
</script>

<style scoped>
.sticker-picker {
	width: 300px;
	height: 400px;
	background: var(--color-main-background);
	border: 1px solid var(--color-border);
	display: flex;
	flex-direction: column;
}

.sticker-picker__categories {
	display: flex;
	overflow-x: auto;
	border-bottom: 1px solid var(--color-border);
	padding: 5px;
}

.sticker-picker__categories button {
	background: none;
	border: none;
	padding: 5px 10px;
	cursor: pointer;
	white-space: nowrap;
}

.sticker-picker__categories button.active {
	font-weight: bold;
	border-bottom: 2px solid var(--color-primary);
}

.sticker-picker__stickers {
	flex: 1;
	overflow-y: auto;
	padding: 10px;
}

.stickers-grid {
	display: grid;
	grid-template-columns: repeat(auto-fill, minmax(64px, 1fr));
	gap: 10px;
}

.sticker-item img {
	width: 100%;
	height: auto;
	cursor: pointer;
}

.sticker-item:hover {
	background-color: var(--color-background-hover);
}

.upload-sticker {
	display: flex;
	flex-direction: column;
	align-items: center;
	justify-content: center;
	border: 1px dashed var(--color-border);
	cursor: pointer;
	font-size: 0.8em;
	padding: 5px;
	text-align: center;
}

.sticker-picker__new-category {
	padding: 5px;
	border-bottom: 1px solid var(--color-border);
}
</style>
