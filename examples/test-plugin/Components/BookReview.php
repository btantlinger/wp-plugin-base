<?php

namespace WebMoves\PluginBase\Examples\Components;

use WebMoves\PluginBase\PostTypes\AbstractPostType;

class BookReview extends AbstractPostType {

	/**
	 * @inheritDoc
	 */
	public function get_post_type(): string {
		return 'book_review';
	}

	/**
	 * Custom configuration for Book Review post type
	 */
	protected function get_args(): array {
		return [
			'public' => true,
			'show_in_rest' => false, // Enable Gutenberg editor
			'menu_icon' => 'dashicons-book-alt',
			'supports' => [
				'title',
				'editor',
				'thumbnail',
				'excerpt',
				'custom-fields',
				'comments'
			],
			'taxonomies' => ['genre'], // Connect to Genre taxonomy
			'has_archive' => true,
			'rewrite' => [
				'slug' => 'book-reviews',
				'with_front' => false
			],
		];
	}

	/**
	 * Custom labels for better UX
	 */
	protected function get_custom_labels(): array {
		return [
			'menu_name' => $this->__t('Book Reviews'),
			'add_new' => $this->__t('Add New Review'),
			'add_new_item' => $this->__t('Add New Book Review'),
			'edit_item' => $this->__t('Edit Book Review'),
			'new_item' => $this->__t('New Book Review'),
			'view_item' => $this->__t('View Book Review'),
			'search_items' => $this->__t('Search Book Reviews'),
			'not_found' => $this->__t('No book reviews found'),
			'not_found_in_trash' => $this->__t('No book reviews found in trash'),
			'all_items' => $this->__t('All Book Reviews'),
			'featured_image' => $this->__t('Book Cover'),
			'set_featured_image' => $this->__t('Set book cover'),
			'remove_featured_image' => $this->__t('Remove book cover'),
			'use_featured_image' => $this->__t('Use as book cover'),
		];
	}

	/**
	 * Add custom meta boxes for book-specific fields
	 */
	public function register(): void {
		parent::register();
		
		// Add meta boxes on admin_init
		add_action('add_meta_boxes', [$this, 'add_book_meta_boxes']);
		add_action('save_post_' . $this->get_post_type(), [$this, 'save_book_meta']);
	}

	/**
	 * Add custom meta boxes for book details
	 */
	public function add_book_meta_boxes(): void {
		add_meta_box(
			'book_details',
			$this->__t('Book Details'),
			[$this, 'render_book_details_meta_box'],
			$this->get_post_type(),
			'side',
			'default'
		);

		add_meta_box(
			'review_details',
			$this->__t('Review Details'),
			[$this, 'render_review_details_meta_box'],
			$this->get_post_type(),
			'normal',
			'default'
		);
	}

	/**
	 * Render book details meta box
	 */
	public function render_book_details_meta_box(\WP_Post $post): void {
		wp_nonce_field('book_review_meta', 'book_review_meta_nonce');
		
		$author = get_post_meta($post->ID, '_book_author', true);
		$isbn = get_post_meta($post->ID, '_book_isbn', true);
		$pages = get_post_meta($post->ID, '_book_pages', true);
		$publication_year = get_post_meta($post->ID, '_book_publication_year', true);
		$publisher = get_post_meta($post->ID, '_book_publisher', true);
		
		?>
		<table class="form-table">
			<tr>
				<th><label for="book_author"><?php echo esc_html($this->__t('Author')); ?></label></th>
				<td><input type="text" id="book_author" name="book_author" value="<?php echo esc_attr($author); ?>" class="widefat" /></td>
			</tr>
			<tr>
				<th><label for="book_isbn"><?php echo esc_html($this->__t('ISBN')); ?></label></th>
				<td><input type="text" id="book_isbn" name="book_isbn" value="<?php echo esc_attr($isbn); ?>" class="widefat" /></td>
			</tr>
			<tr>
				<th><label for="book_pages"><?php echo esc_html($this->__t('Pages')); ?></label></th>
				<td><input type="number" id="book_pages" name="book_pages" value="<?php echo esc_attr($pages); ?>" class="widefat" /></td>
			</tr>
			<tr>
				<th><label for="book_publication_year"><?php echo esc_html($this->__t('Publication Year')); ?></label></th>
				<td><input type="number" id="book_publication_year" name="book_publication_year" value="<?php echo esc_attr($publication_year); ?>" class="widefat" min="1000" max="<?php echo date('Y'); ?>" /></td>
			</tr>
			<tr>
				<th><label for="book_publisher"><?php echo esc_html($this->__t('Publisher')); ?></label></th>
				<td><input type="text" id="book_publisher" name="book_publisher" value="<?php echo esc_attr($publisher); ?>" class="widefat" /></td>
			</tr>
		</table>
		<?php
	}

	/**
	 * Render review details meta box
	 */
	public function render_review_details_meta_box(\WP_Post $post): void {
		$rating = get_post_meta($post->ID, '_review_rating', true);
		$reading_date = get_post_meta($post->ID, '_reading_date', true);
		$recommended = get_post_meta($post->ID, '_review_recommended', true);
		
		?>
		<table class="form-table">
			<tr>
				<th><label for="review_rating"><?php echo esc_html($this->__t('Rating (1-5 stars)')); ?></label></th>
				<td>
					<select id="review_rating" name="review_rating">
						<option value=""><?php echo esc_html($this->__t('Select rating...')); ?></option>
						<?php for ($i = 1; $i <= 5; $i++): ?>
							<option value="<?php echo $i; ?>" <?php selected($rating, $i); ?>>
								<?php echo str_repeat('★', $i) . str_repeat('☆', 5-$i) . " ({$i}/5)"; ?>
							</option>
						<?php endfor; ?>
					</select>
				</td>
			</tr>
			<tr>
				<th><label for="reading_date"><?php echo esc_html($this->__t('Date Read')); ?></label></th>
				<td><input type="date" id="reading_date" name="reading_date" value="<?php echo esc_attr($reading_date); ?>" /></td>
			</tr>
			<tr>
				<th><label for="review_recommended"><?php echo esc_html($this->__t('Recommended')); ?></label></th>
				<td>
					<label>
						<input type="checkbox" id="review_recommended" name="review_recommended" value="1" <?php checked($recommended, '1'); ?> />
						<?php echo esc_html($this->__t('I recommend this book')); ?>
					</label>
				</td>
			</tr>
		</table>
		<?php
	}

	/**
	 * Save book meta data
	 */
	public function save_book_meta(int $post_id): void {
		// Verify nonce
		if (!isset($_POST['book_review_meta_nonce']) || !wp_verify_nonce($_POST['book_review_meta_nonce'], 'book_review_meta')) {
			return;
		}

		// Check if user can edit post
		if (!current_user_can('edit_post', $post_id)) {
			return;
		}

		// Save book details
		$book_fields = ['book_author', 'book_isbn', 'book_pages', 'book_publication_year', 'book_publisher'];
		foreach ($book_fields as $field) {
			if (isset($_POST[$field])) {
				update_post_meta($post_id, "_{$field}", sanitize_text_field($_POST[$field]));
			}
		}

		// Save review details
		if (isset($_POST['review_rating'])) {
			update_post_meta($post_id, '_review_rating', intval($_POST['review_rating']));
		}

		if (isset($_POST['reading_date'])) {
			update_post_meta($post_id, '_reading_date', sanitize_text_field($_POST['reading_date']));
		}

		$recommended = isset($_POST['review_recommended']) ? '1' : '0';
		update_post_meta($post_id, '_review_recommended', $recommended);
	}
}