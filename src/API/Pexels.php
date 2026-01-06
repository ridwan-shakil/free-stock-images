<?php

namespace FreeStockImages\API;

if (! defined('ABSPATH')) {
    exit;
}

/**
 * The Pexels class implements a ProviderInterface to search for images using the Pexels API and retrieve image data. 
 */
class Pexels implements ProviderInterface {

    private $demo_key = 'iyHCPNGUtD3m5G2mIQ6oSbg6p6FkZcMOTwKSbHvLQJfY7V2UIOdNV4Fd';

    public function search_images(string $query, array $filters = [], int $page = 1, int $perPage = 20): array {
        $api_key = $this->get_api_key();

        $url = add_query_arg([
            'query' => rawurlencode($query),
            'page'  => $page,
            'per_page' => $perPage,
        ], 'https://api.pexels.com/v1/search');

        $response = wp_remote_get($url, [
            'headers' => [
                'Authorization' => $api_key
            ]
        ]);

        if (is_wp_error($response)) return [];

        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body, true);

        if (empty($data['photos'])) return [];

        $images = [];
        foreach ($data['photos'] as $img) {
            $images[] = [
                'id'          => $img['id'],
                'thumbnail'   => $img['src']['medium'],
                'full'        => $img['src']['original'],
                'width'       => $img['width'],
                'height'      => $img['height'],
                'author'      => $img['photographer'],
                'author_url'  => $img['photographer_url'] ?? '',
                'source'      => 'pexels',
                'title'       => '',
                'attribution' => sprintf('Photo by %s on Pexels', $img['photographer']),
            ];
        }

        return $images;
    }

    public function get_api_key(): string {
        $user_key = get_option('fsi_pexels_key', '');
        return $user_key ?: $this->demo_key;
    }
}
