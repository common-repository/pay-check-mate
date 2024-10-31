<?php

namespace PayCheckMate\REST;

use PayCheckMate\REST\RestController;
use WP_Error;
use Exception;
use WP_REST_Request;
use WP_REST_Response;
use PayCheckMate\Requests\DesignationRequest;
use PayCheckMate\Contracts\HookAbleApiInterface;
use PayCheckMate\Models\DesignationModel;

class DesignationApi extends RestController implements HookAbleApiInterface {

    public function __construct() {
        $this->namespace = 'pay-check-mate/v1';
        $this->rest_base = 'designations';
    }

    /**
     * Register the necessary Routes.
     *
     * @since 1.0.0
     *
     * @return void
     */
    public function register_api_routes(): void {
        register_rest_route(
            $this->namespace, '/' . $this->rest_base, [
				[
					'methods'             => \WP_REST_Server::READABLE,
					'callback'            => [ $this, 'get_items' ],
					'args'                => $this->get_collection_params(),
					'permission_callback' => [ $this, 'get_items_permissions_check' ],
				],
				[
					'methods'             => \WP_REST_Server::CREATABLE,
					'callback'            => [ $this, 'create_item' ],
					'permission_callback' => [ $this, 'create_item_permissions_check' ],
					'args'                => $this->get_endpoint_args_for_item_schema( \WP_REST_Server::CREATABLE ),
				],
				'schema' => [ $this, 'get_public_item_schema' ],
			],
        );

        register_rest_route(
            $this->namespace, '/' . $this->rest_base . '/(?P<id>[\d]+)', [
				[
					'methods'             => \WP_REST_Server::READABLE,
					'callback'            => [ $this, 'get_item' ],
					'permission_callback' => [ $this, 'get_item_permissions_check' ],
					'args'                => [
						'id' => [
							'description' => __( 'Unique identifier for the object.', 'pay-check-mate' ),
							'type'        => 'integer',
							'required'    => true,
						],
					],
				],
				[
					'methods'             => \WP_REST_Server::EDITABLE,
					'callback'            => [ $this, 'update_item' ],
					'permission_callback' => [ $this, 'update_item_permissions_check' ],
					'args'                => [
						'id'     => [
							'description' => __( 'Unique identifier for the object.', 'pay-check-mate' ),
							'type'        => 'integer',
							'required'    => true,
						],
						'name'   => [
							'description' => __( 'Designation name.', 'pay-check-mate' ),
							'type'        => 'string',
							'required'    => true,
						],
						'status' => [
							'description' => __( 'Designation status.', 'pay-check-mate' ),
							'type'        => 'number',
						],
					],
				],
				[
					'methods'             => \WP_REST_Server::DELETABLE,
					'callback'            => [ $this, 'delete_item' ],
					'permission_callback' => [ $this, 'delete_item_permissions_check' ],
					'args'                => [
						'id' => [
							'description' => __( 'Unique identifier for the object.', 'pay-check-mate' ),
							'type'        => 'integer',
							'required'    => true,
						],
					],
				],
				'schema' => [ $this, 'get_public_item_schema' ],
			],
        );
    }

    /**
     * Checks if a given request has access to read designations.
     *
     * @since 1.0.0
     *
     * @param WP_REST_Request<array<string, mixed>> $request Full details about the request.
     *
     * @return bool
     */
    public function get_items_permissions_check( $request ): bool {
        return true;
    }

    /**
     * Checks if a given request has access to create a designation.
     *
     * @since 1.0.0
     *
     * @param WP_REST_Request<array<string, mixed>> $request Full details about the request.
     *
     * @return bool
     */
    public function create_item_permissions_check( $request ): bool {
        return true;
    }

    /**
     * Checks if a given request has access to read a designation.
     *
     * @since 1.0.0
     *
     * @param WP_REST_Request<array<string, mixed>> $request Full details about the request.
     *
     * @return bool
     */
    public function get_item_permissions_check( $request ): bool {
        return true;
    }

    /**
     * Checks if a given request has access to update a designation.
     *
     * @since 1.0.0
     *
     * @param WP_REST_Request<array<string, mixed>> $request Full details about the request.
     *
     * @return bool
     */
    public function update_item_permissions_check( $request ): bool {
        return true;
    }

    /**
     * Checks if a given request has access to delete a designation.
     *
     * @since 1.0.0
     *
     * @param WP_REST_Request<array<string, mixed>> $request Full details about the request.
     *
     * @return bool
     */
    public function delete_item_permissions_check( $request ): bool {
        return true;
    }

    /**
     * Retrieves a collection of designations.
     *
     * @since 1.0.0
     *
     * @param WP_REST_Request<array<string>> $request Full details about the request.
     *
     * @throws \Exception
     * @return WP_REST_Response Response object on success, or WP_Error object on failure.
     */
    public function get_items( $request ): WP_REST_Response {
        $designation = new DesignationModel();
        $args        = [
            'limit'   => $request->get_param( 'per_page' ) ? $request->get_param( 'per_page' ) : 10,
            'offset'  => $request->get_param( 'page' ) ? ( $request->get_param( 'page' ) - 1 ) * $request->get_param( 'per_page' ) : 0,
            'order'   => $request->get_param( 'order' ) ? $request->get_param( 'order' ) : 'DESC',
            'order_by' => $request->get_param( 'order_by' ) ? $request->get_param( 'order_by' ) : 'id',
            'status'  => $request->get_param( 'status' ) !== null ? $request->get_param( 'status' ) : 'all',
            'search'   => $request->get_param( 'search' ) ? $request->get_param( 'search' ) : '',
        ];

        $designations = $designation->all( $args );
        $data         = [];
        foreach ( $designations as $value ) {
            $item   = $this->prepare_item_for_response( $value, $request );
            $data[] = $this->prepare_response_for_collection( $item );
        }

        $total     = $designation->count( $args );
        $max_pages = ceil( $total / (int) $args['limit'] );

        $response = new WP_REST_Response( $data );

        $response->header( 'X-WP-Total', (string) $total );
        $response->header( 'X-WP-TotalPages', (string) $max_pages );

        return new WP_REST_Response( $response, 200 );
    }

    /**
     * Creates one item from the collection.
     *
     * @since 1.0.0
     *
     * @param WP_REST_Request<array<string>> $request Full details about the request.
     *
     * @return WP_REST_Response|WP_Error Response object on success, or WP_Error object on failure.
     * @throws Exception
     */
    public function create_item( $request ) {
        $designation    = new DesignationModel();
        $validated_data = new DesignationRequest( $request->get_params() );
        if ( ! empty( $validated_data->error ) ) {
            return new WP_Error( 500, __( 'Invalid data.', 'pay-check-mate' ), [ $validated_data->error ] );
        }

        $designation = $designation->create( $validated_data );
        if ( is_wp_error( $designation ) ) {
            return new WP_Error( 500, __( 'Could not create department.', 'pay-check-mate' ) );
        }

        $designation = $this->prepare_item_for_response( $designation, $request );
        $data = $this->prepare_response_for_collection( $designation );
        $response = new WP_REST_Response( $data );
        $response->set_status( 201 );

        return new WP_REST_Response( $response, 201 );
    }

    /**
     * Retrieves one item from the collection.
     *
     * @since 1.0.0
     *
     * @param WP_REST_Request<array<string>> $request Full details about the request.
     *
     * @throws \Exception
     *
     * @return WP_REST_Response|WP_Error Response object on success, or WP_Error object on failure.
     */
    public function get_item( $request ) {
        $designation = new DesignationModel();
        $designation = $designation->find( $request->get_param( 'id' ) );

        if ( is_wp_error( $designation ) ) {
            return new WP_Error( 404, $designation->get_error_message(), [ 'status' => 404 ] );
        }

        $item = $this->prepare_item_for_response( $designation, $request );
        $data = $this->prepare_response_for_collection( $item );

        return new WP_REST_Response( $data, 200 );
    }

    /**
     * Updates one item from the collection.
     *
     * @since 1.0.0
     *
     * @param WP_REST_Request<array<string>> $request Full details about the request.
     *
     * @return WP_REST_Response|WP_Error Response object on success, or WP_Error object on failure.
     * @throws \Exception
     */
    public function update_item( $request ) {
        $designation    = new DesignationModel();
        $validated_data = new DesignationRequest( $request->get_params() );
        if ( ! empty( $validated_data->error ) ) {
            return new WP_Error( 500, __( 'Invalid data.', 'pay-check-mate' ), [ $validated_data->error ] );
        }

        $updated = $designation->update( $request->get_param( 'id' ), $validated_data );
        if ( is_wp_error( $updated ) ) {
            return new WP_Error( 500, $updated->get_error_message(), [ 'status' => 500 ] );
        }

        $designation = $designation->find( $request->get_param( 'id' ) );
        $item        = $this->prepare_item_for_response( $designation, $request );
        $data        = $this->prepare_response_for_collection( $item );
        $response = new WP_REST_Response( $data );
        $response->set_status( 201 );

        return new WP_REST_Response( $response, 201 );
    }

    /**
     * Deletes one item from the collection.
     *
     * @since 1.0.0
     *
     * @param WP_REST_Request<array<string, mixed>> $request Full details about the request.
     *
     * @return WP_REST_Response|WP_Error Response object on success, or WP_Error object on failure.
     */
    public function delete_item( $request ) {
        $designation = new DesignationModel();
        try {
            $designation = $designation->delete( $request['id'] );
        } catch ( Exception $e ) {
            return new WP_Error( 500, __( 'Could not delete designation.', 'pay-check-mate' ) );
        }

        if ( ! $designation ) {
            return new WP_Error( 500, __( 'Could not delete designation.', 'pay-check-mate' ) );
        }

        return new WP_REST_Response( $designation, 200 );
    }

    /**
     * Retrieves the item's schema, conforming to JSON Schema.
     *
     * @since 1.0.0
     *
     * @return array<string, mixed> Item schema data.
     */
    public function get_item_schema(): array {
        return [
            '$schema'    => 'http://json-schema.org/draft-04/schema#',
            'title'      => 'designation',
            'type'       => 'object',
            'properties' => [
                'id'         => [
                    'description' => __( 'Unique identifier for the object.', 'pay-check-mate' ),
                    'type'        => 'integer',
                    'context'     => [ 'view', 'edit', 'embed' ],
                    'readonly'    => true,
                ],
                'name'       => [
                    'description' => __( 'The name for the designation.', 'pay-check-mate' ),
                    'type'        => 'string',
                    'context'     => [ 'view', 'edit', 'embed' ],
                    'required'    => true,
                ],
                'status'     => [
                    'description' => __( 'The status for the designation.', 'pay-check-mate' ),
                    'type'        => 'string',
                    'context'     => [ 'view', 'edit', 'embed' ],
                    'required'    => false,
                ],
                'created_on' => [
                    'description' => __( 'The date the designation was created.', 'pay-check-mate' ),
                    'type'        => 'string',
                    'context'     => [ 'view', 'edit', 'embed' ],
                    'readonly'    => false,
                ],
                'updated_at' => [
                    'description' => __( 'The date the designation was last updated.', 'pay-check-mate' ),
                    'type'        => 'string',
                    'context'     => [ 'view', 'edit', 'embed' ],
                    'readonly'    => false,
                ],
            ],
        ];
    }
}
