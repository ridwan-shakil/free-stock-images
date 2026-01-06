<?php

namespace FreeStockImages\API;

if (! defined('ABSPATH')) {
    exit;
}

interface ProviderInterface {
    /**
     * Search images
     *
     * @param string $query   Search term
     * @param array  $filters Optional filters (orientation, color, etc.)
     * @param int    $page    Page number
     * @param int    $perPage Results per page
     * @return array          Array of normalized image objects
     */
    public function search_images(string $query, array $filters = [], int $page = 1, int $perPage = 20): array;

    /**
     * Get API key to use (user key or demo key)
     *
     * @return string
     */
    public function get_api_key(): string;
}
