<?php

namespace WebMoves\PluginBase\Examples\Components;

use WebMoves\PluginBase\Taxonomies\AbstractTaxonomy;

class Genre extends AbstractTaxonomy {

    /**
     * @inheritDoc
     */
    public function get_taxonomy(): string {
        return 'genre';
    }

    /**
     * @inheritDoc
     */
    public function get_object_types(): array {
        return ['book_review'];
    }

    /**
     * All configuration in one place - clean and simple!
     */
    protected function get_args(): array {
        return [
            'hierarchical' => true, // Allow parent/child genres
            'show_in_rest' => true, // REST API support
            'show_in_menu' => true,
            'show_admin_column' => true,
            'show_tagcloud' => true,
            'rewrite' => ['slug' => 'genre'],
            'labels' => [
                'menu_name' => $this->__t('Book Genres'),
                'all_items' => $this->__t('All Genres'),
                'popular_items' => $this->__t('Popular Genres'),
            ],
        ];
    }

    /**
     * Add some default genres when taxonomy is registered
     */
    public function register(): void {
        parent::register();
        
        // Add default genres if they don't exist
        add_action('init', [$this, 'maybe_create_default_genres'], 20);
    }

    /**
     * Create default genres if none exist
     */
    public function maybe_create_default_genres(): void {
        // Only run once
        if (get_option('genre_defaults_created')) {
            return;
        }

        $default_genres = [
            'Fiction' => [
                'description' => $this->__t('Fictional stories and novels'),
                'children' => [
                    'Science Fiction' => $this->__t('Stories set in the future or alternative worlds'),
                    'Fantasy' => $this->__t('Stories with magical or supernatural elements'),
                    'Mystery' => $this->__t('Stories involving puzzles, crimes, or unexplained events'),
                    'Romance' => $this->__t('Stories focusing on romantic relationships'),
                    'Thriller' => $this->__t('Fast-paced stories designed to keep readers on edge'),
                    'Historical Fiction' => $this->__t('Stories set in the past'),
                ]
            ],
            'Non-Fiction' => [
                'description' => $this->__t('Factual books and real-world topics'),
                'children' => [
                    'Biography' => $this->__t('Life stories of real people'),
                    'History' => $this->__t('Books about historical events and periods'),
                    'Science' => $this->__t('Books about scientific topics and discoveries'),
                    'Self-Help' => $this->__t('Books designed to help readers improve their lives'),
                    'Business' => $this->__t('Books about business, economics, and entrepreneurship'),
                    'Health & Fitness' => $this->__t('Books about health, wellness, and fitness'),
                ]
            ],
            'Poetry' => [
                'description' => $this->__t('Collections of poems and poetic works')
            ],
            'Drama' => [
                'description' => $this->__t('Plays and dramatic works')
            ],
        ];

        foreach ($default_genres as $parent_name => $parent_data) {
            // Create parent genre
            $parent_term = wp_insert_term($parent_name, 'genre', [
                'description' => $parent_data['description']
            ]);

            if (!is_wp_error($parent_term) && isset($parent_data['children'])) {
                $parent_id = $parent_term['term_id'];
                
                // Create child genres
                foreach ($parent_data['children'] as $child_name => $child_description) {
                    wp_insert_term($child_name, 'genre', [
                        'description' => $child_description,
                        'parent' => $parent_id
                    ]);
                }
            }
        }

        // Mark as completed
        update_option('genre_defaults_created', true);
    }
}