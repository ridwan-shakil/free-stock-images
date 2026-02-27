<?php

namespace FreeStockImages\Ajax;

use FreeStockImages\API\ProviderFactory;
use FreeStockImages\API\SourcePolicy;
use FreeStockImages\Services\Importer;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Handles plugin admin-ajax endpoints.
 */
class AjaxController {
	/**
	 * @var string
	 */
	private $nonce_action;

	/**
	 * @var string
	 */
	private $search_action;

	/**
	 * @var string
	 */
	private $import_action;

	/**
	 * @var string
	 */
	private $upload_cap;

	/**
	 * @var ProviderFactory
	 */
	private $provider_factory;

	/**
	 * @var SourcePolicy
	 */
	private $source_policy;

	/**
	 * @var Importer
	 */
	private $importer;

	/**
	 * @param string          $nonce_action Nonce action.
	 * @param string          $search_action Search action key.
	 * @param string          $import_action Import action key.
	 * @param string          $upload_cap Upload capability.
	 * @param ProviderFactory $provider_factory Provider factory.
	 * @param SourcePolicy    $source_policy Source policy.
	 * @param Importer        $importer Importer service.
	 */
	public function __construct( $nonce_action, $search_action, $import_action, $upload_cap, ProviderFactory $provider_factory, SourcePolicy $source_policy, Importer $importer ) {
		$this->nonce_action    = $nonce_action;
		$this->search_action   = $search_action;
		$this->import_action   = $import_action;
		$this->upload_cap      = $upload_cap;
		$this->provider_factory = $provider_factory;
		$this->source_policy   = $source_policy;
		$this->importer        = $importer;
	}

	/**
	 * @return void
	 */
	public function register() {
		add_action( 'wp_ajax_' . $this->search_action, array( $this, 'search' ) );
		add_action( 'wp_ajax_' . $this->import_action, array( $this, 'import' ) );
	}

	/**
	 * @return void
	 */
	public function search() {
		check_ajax_referer( $this->nonce_action );

		if ( ! current_user_can( $this->upload_cap ) ) {
			wp_send_json_error(
				array(
					'error_code' => 'unauthorized',
					'message'    => __( 'You are not allowed to search images.', 'free-stock-images' ),
					'images'     => array(),
				),
				403
			);
		}

		$query       = isset( $_POST['query'] ) ? sanitize_text_field( wp_unslash( $_POST['query'] ) ) : '';
		$source      = isset( $_POST['source'] ) ? sanitize_key( wp_unslash( $_POST['source'] ) ) : 'pixabay';
		$page        = isset( $_POST['page'] ) ? max( 1, absint( $_POST['page'] ) ) : 1;
		$per_page    = isset( $_POST['per_page'] ) ? max( 1, min( 50, absint( $_POST['per_page'] ) ) ) : 20;
		$orientation = isset( $_POST['orientation'] ) ? sanitize_key( wp_unslash( $_POST['orientation'] ) ) : '';
		$color       = isset( $_POST['color'] ) ? sanitize_key( wp_unslash( $_POST['color'] ) ) : '';

		if ( ! in_array( $orientation, array( '', 'landscape', 'portrait', 'square' ), true ) ) {
			$orientation = '';
		}

		$allowed_colors = array( '', 'grayscale', 'transparent', 'red', 'orange', 'yellow', 'green', 'turquoise', 'blue', 'lilac', 'pink', 'white', 'gray', 'black', 'brown' );
		if ( ! in_array( $color, $allowed_colors, true ) ) {
			$color = '';
		}

		if ( '' === $query ) {
			wp_send_json_success(
				array(
					'images' => array(),
					'page'   => $page,
				)
			);
		}

		if ( ! $this->source_policy->is_enabled( $source ) ) {
			wp_send_json_error(
				array(
					'error_code' => 'source_disabled',
					'message'    => __( 'This source is disabled until a valid API key is configured.', 'free-stock-images' ),
					'images'     => array(),
				),
				400
			);
		}

		$provider = $this->provider_factory->make( $source );
		if ( ! $provider ) {
			wp_send_json_error(
				array(
					'error_code' => 'invalid_source',
					'message'    => __( 'Invalid image source selected.', 'free-stock-images' ),
					'images'     => array(),
				),
				400
			);
		}

		try {
			$filters = array(
				'orientation' => $orientation,
				'color'       => $color,
			);
			$images  = $provider->search_images( $query, $filters, $page, $per_page );
			wp_send_json_success(
				array(
					'images' => $images,
					'page'   => $page,
				)
			);
		} catch ( \Throwable $exception ) {
			wp_send_json_error(
				array(
					'error_code' => 'provider_error',
					'message'    => $exception->getMessage(),
					'images'     => array(),
				),
				500
			);
		}
	}

	/**
	 * @return void
	 */
	public function import() {
		check_ajax_referer( $this->nonce_action );

		if ( ! current_user_can( $this->upload_cap ) ) {
			wp_send_json_error(
				array(
					'error_code' => 'unauthorized',
					'message'    => __( 'You are not allowed to import images.', 'free-stock-images' ),
				),
				403
			);
		}

		$image_url   = isset( $_POST['image_url'] ) ? esc_url_raw( wp_unslash( $_POST['image_url'] ) ) : '';
		$title       = isset( $_POST['title'] ) ? sanitize_text_field( wp_unslash( $_POST['title'] ) ) : '';
		$attribution = isset( $_POST['attribution'] ) ? sanitize_text_field( wp_unslash( $_POST['attribution'] ) ) : '';
		$source      = isset( $_POST['source'] ) ? sanitize_key( wp_unslash( $_POST['source'] ) ) : '';
		$remote_id   = isset( $_POST['remote_id'] ) ? sanitize_text_field( wp_unslash( $_POST['remote_id'] ) ) : '';

		if ( '' === $image_url ) {
			wp_send_json_error(
				array(
					'error_code' => 'missing_image_url',
					'message'    => __( 'Image URL is required.', 'free-stock-images' ),
				),
				400
			);
		}

		$result = $this->importer->import_from_url(
			$image_url,
			array(
				'title'       => $title,
				'attribution' => $attribution,
				'source'      => $source,
				'remote_id'   => $remote_id,
			)
		);

		if ( is_wp_error( $result ) ) {
			wp_send_json_error(
				array(
					'error_code' => 'import_failed',
					'message'    => $result->get_error_message(),
				),
				500
			);
		}

		$attachment_url = wp_get_attachment_url( $result );
		$mime_type      = get_post_mime_type( $result );
		$post           = get_post( $result );

		wp_send_json_success(
			array(
				'attachment_id' => $result,
				'url'           => $attachment_url ? $attachment_url : '',
				'title'         => $post ? get_the_title( $post ) : '',
				'mime'          => $mime_type ? $mime_type : '',
			)
		);
	}
}
