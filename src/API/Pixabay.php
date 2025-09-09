<?php

namespace FreeStockImages\API;

if (! defined('ABSPATH')) {
    exit;
}

class Pixabay implements ProviderInterface {

    private $demo_key = 'YOUR_PIXABAY_DEMO_KEY_HERE';

    public function search_images(string $query, array $filters = [], int $page = 1, int $perPage = 20): array {
        $api_key = $this->get_api_key();

        $url = add_query_arg([
            'key'   => $api_key,
            'q'     => rawurlencode($query),
            'page'  => $page,
            'per_page' => $perPage,
            'image_type' => 'photo',
        ], 'https://pixabay.com/api/');

        $response = wp_remote_get($url);

        if (is_wp_error($response)) return [];

        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body, true);

        if (empty($data['hits'])) return [];

        $images = [];
        foreach ($data['hits'] as $img) {
            $images[] = [
                'id'          => $img['id'],
                'thumbnail'   => $img['previewURL'],
                'full'        => $img['largeImageURL'],
                'width'       => $img['imageWidth'],
                'height'      => $img['imageHeight'],
                'author'      => $img['user'],
                'author_url'  => '',
                'source'      => 'pixabay',
                'title'       => $img['tags'] ?? '',
                'attribution' => sprintf('Photo by %s on Pixabay', $img['user']),
            ];
        }

        return $images;
    }

    public function get_api_key(): string {
        $user_key = get_option('fsi_pixabay_key', '');
        return $user_key ?: $this->demo_key;
    }
}
