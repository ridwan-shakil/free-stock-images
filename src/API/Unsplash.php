<?php

namespace FreeStockImages\API;

if (! defined('ABSPATH')) {
    exit;
}

class Unsplash implements ProviderInterface {

    // Demo key for Hybrid UX
    private $demo_key = 'YOUR_UNSPLASH_DEMO_KEY_HERE';

    /**
     * Search images on Unsplash
     */
    public function search_images(string $query, array $filters = [], int $page = 1, int $perPage = 20): array {
        $api_key = $this->get_api_key();

        $url = add_query_arg([
            'query' => rawurlencode($query),
            'page'  => $page,
            'per_page' => $perPage,
            'client_id' => $api_key,
        ], 'https://api.unsplash.com/search/photos');

        $response = wp_remote_get($url);

        if (is_wp_error($response)) {
            return [];
        }

        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body, true);

        if (empty($data['results'])) {
            return [];
        }

        $images = [];
        foreach ($data['results'] as $img) {
            $images[] = [
                'id'          => $img['id'],
                'thumbnail'   => $img['urls']['small'],
                'full'        => $img['urls']['full'],
                'width'       => $img['width'],
                'height'      => $img['height'],
                'author'      => $img['user']['name'],
                'author_url'  => $img['user']['links']['html'],
                'source'      => 'unsplash',
                'title'       => $img['alt_description'] ?? '',
                'attribution' => sprintf('Photo by %s on Unsplash', $img['user']['name']),
            ];
        }

        return $images;
    }

    /**
     * Return user key if set, otherwise demo key
     */
    public function get_api_key(): string {
        $user_key = get_option('fsi_unsplash_key', '');
        return $user_key ?: $this->demo_key;
    }
}
