<?php

namespace PayCheckMate\REST;

use Exception;
use PayCheckMate\REST\RestController;
use WP_Error;
use WP_REST_Request;
use WP_REST_Response;
use PayCheckMate\Requests\SalaryHeadRequest;
use PayCheckMate\Contracts\HookAbleApiInterface;
use PayCheckMate\Models\SalaryHeadModel;

class SalaryHeadApi extends RestController implements HookAbleApiInterface {

    public function __construct() {
        $this->namespace = 'pay-check-mate/v1';
        $this->rest_base = 'salary-heads';
    }

    /**
     * Register the routes for the objects of the controller.
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
						'id'                  => [
							'description' => __( 'Unique identifier for the object.', 'pay-check-mate' ),
							'type'        => 'integer',
							'required'    => true,
						],
						'head_name'           => [
							'description' => __( 'Salary head name.', 'pay-check-mate' ),
							'type'        => 'string',
							'required'    => true,
						],
						'head_type'           => [
							'description' => __( 'Salary head type.', 'pay-check-mate' ),
							'type'        => 'integer',
							'required'    => true,
						],
						'head_amount'         => [
							'description' => __( 'Salary head amount.', 'pay-check-mate' ),
							'type'        => 'number',
							'required'    => true,
						],
						'is_percentage'       => [
							'description' => __( 'Salary head is percentage.', 'pay-check-mate' ),
							'type'        => 'boolean',
							'required'    => true,
						],
						'is_variable'         => [
							'description' => __( 'Is Changeable in every month?', 'pay-check-mate' ),
							'type'        => 'boolean',
							'required'    => true,
						],
						'is_taxable'          => [
							'description' => __( 'Salary head is taxable.', 'pay-check-mate' ),
							'type'        => 'boolean',
							'required'    => true,
						],
						'is_personal_savings' => [
							'description' => __( 'Salary head is personal savings.', 'pay-check-mate' ),
							'type'        => 'boolean',
							'required'    => true,
						],
						'priority'            => [
							'description' => __( 'Salary head priority.', 'pay-check-mate' ),
							'type'        => 'integer',
							'required'    => true,
						],
						'status'              => [
							'description' => __( 'Department status.', 'pay-check-mate' ),
							'type'        => 'boolean',
							'required'    => true,
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
     * Checks if a given request has access to create items.
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
     * Checks if a given request has access to create items.
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
     * Checks if a given request has access to read a item.
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
     * Checks if a given request has access to update a item.
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
     * Checks if a given request has access to delete a item.
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
        $salary_head = new SalaryHeadModel();
        $args        = [
            'limit'    => $request->get_param( 'per_page' ) ? $request->get_param( 'per_page' ) : 10,
            'offset'   => $request->get_param( 'page' ) ? ( $request->get_param( 'page' ) - 1 ) * $request->get_param( 'per_page' ) : 0,
            'order'    => $request->get_param( 'order' ) ? $request->get_param( 'order' ) : 'DESC',
            'order_by' => $request->get_param( 'order_by' ) ? $request->get_param( 'order_by' ) : 'id',
            'status'   => $request->get_param( 'status' ) ? $request->get_param( 'status' ) : 'all',
            'search'   => $request->get_param( 'search' ) ? $request->get_param( 'search' ) : '',
        ];

        $salary_heads = $salary_head->all( $args );
        $data         = [];
        foreach ( $salary_heads as $value ) {
            $item   = $this->prepare_item_for_response( $value, $request );
            $data[] = $this->prepare_response_for_collection( $item );
        }

        $total     = $salary_head->count( $args );
        $max_pages = ceil( $total / (int) $args['limit'] );

        $response = new WP_REST_Response( $data );

        $response->header( 'X-WP-Total', (string) $total );
        $response->header( 'X-WP-TotalPages', (string) $max_pages );
        $response->header( 'per_page', (string) $args['limit'] );

        return new WP_REST_Response( $response, 200 );
    }

    /**
     * Create a new item.
     *
     * @since 1.0.0
     *
     * @param WP_REST_Request<array<string>> $request
     *
     * @throws Exception
     *
     * @return WP_REST_Response|WP_Error Response object on success, or WP_Error object on failure.
     */
    public function create_item( $request ) {
        $salary_head    = new SalaryHeadModel();
        $validated_data = new SalaryHeadRequest( $request->get_params() );
        if ( ! empty( $validated_data->error ) ) {
            return new WP_Error( 500, __( 'Invalid data.', 'pay-check-mate' ), [ $validated_data->error ] );
        }

        $head = $salary_head->create( $validated_data );

        if ( is_wp_error( $head ) ) {
            return new WP_Error( 500, __( 'Could not create salary head.', 'pay-check-mate' ) );
        }

        $item     = $this->prepare_item_for_response( $head, $request );
        $data     = $this->prepare_response_for_collection( $item );
        $response = new WP_REST_Response( $data );
        $response->set_status( 201 );

        return new WP_REST_Response( $response, 201 );
    }

    /**
     * Get one item from the collection.
     *
     * @since 1.0.0
     *
     * @param WP_REST_Request<array<string>> $request Request object.
     *
     * @return WP_REST_Response|WP_Error Response object on success, or WP_Error object on failure.
     */
    public function get_item( $request ) {
        $department = new SalaryHeadModel();
        $department = $department->find( $request->get_param( 'id' ) );

        if ( is_wp_error( $department ) ) {
            return new WP_Error( 404, $department->get_error_message(), [ 'status' => 404 ] );
        }

        $item = $this->prepare_item_for_response( $department, $request );
        $data = $this->prepare_response_for_collection( $item );

        return new WP_REST_Response( $data, 200 );
    }

    /**
     * Update one item from the collection.
     *
     * @since 1.0.0
     *
     * @param WP_REST_Request<array<string>> $request Request object.
     *
     * @throws Exception
     *
     * @return WP_REST_Response|WP_Error Response object on success, or WP_Error object on failure.
     */
    public function update_item( $request ) {
        $salary_head    = new SalaryHeadModel();
        $validated_data = new SalaryHeadRequest( $request->get_params() );
        if ( ! empty( $validated_data->error ) ) {
            return new WP_Error( 500, __( 'Invalid data.', 'pay-check-mate' ), [ $validated_data->error ] );
        }

        $updated = $salary_head->update( $request->get_param( 'id' ), $validated_data );
        if ( is_wp_error( $updated ) ) {
            return new WP_Error( 500, $updated->get_error_message(), [ 'status' => 500 ] );
        }

        $salary_head = $salary_head->find( $request->get_param( 'id' ) );
        $item        = $this->prepare_item_for_response( $salary_head, $request );
        $data        = $this->prepare_response_for_collection( $item );
        $response    = new WP_REST_Response( $data );
        $response->set_status( 201 );

        return new WP_REST_Response( $response, 201 );
    }

    /**
     * Delete one item from the collection.
     *
     * @since 1.0.0
     *
     * @param WP_REST_Request<array<string>> $request Request object.
     *
     * @return WP_REST_Response|WP_Error Response object on success, or WP_Error object on failure.
     */
    public function delete_item( $request ) {
        $department = new SalaryHeadModel();
        $department = $department->delete( $request->get_param( 'id' ) );

        if ( ! $department ) {
            return new WP_Error( 500, __( 'Could not delete designation.', 'pay-check-mate' ) );
        }

        return new WP_REST_Response( __( 'Department deleted', 'pay-check-mate' ), 200 );
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
            'title'      => 'salary_head',
            'type'       => 'object',
            'properties' => [
                'id'                  => [
                    'description' => __( 'Unique identifier for the object.', 'pay-check-mate' ),
                    'type'        => 'integer',
                    'context'     => [ 'view', 'edit', 'embed' ],
                    'readonly'    => true,
                ],
                'head_name'           => [
                    'description' => __( 'Salary Head Name', 'pay-check-mate' ),
                    'type'        => 'string',
                    'context'     => [ 'view', 'edit', 'embed' ],
                    'required'    => true,
                ],
                'head_type'           => [
                    'description' => __( 'Salary Head Type', 'pay-check-mate' ),
                    'type'        => 'integer',
                    'context'     => [ 'view', 'edit', 'embed' ],
                ],
                'head_type_text'      => [
                    'description' => __( 'Salary Head Type in Text', 'pay-check-mate' ),
                    'type'        => 'string',
                    'context'     => [ 'view' ],
                    'readonly'    => true,
                ],
                'head_amount'         => [
                    'description' => __( 'Salary Head Amount', 'pay-check-mate' ),
                    'type'        => 'number',
                    'context'     => [ 'view', 'edit', 'embed' ],
                    'required'    => true,
                ],
                'is_percentage'       => [
                    'description' => __( 'Salary Head is Percentage', 'pay-check-mate' ),
                    'type'        => 'boolean',
                    'required'    => true,
                ],
                'is_variable'         => [
                    'description' => __( 'Is Changeable in every month?', 'pay-check-mate' ),
                    'type'        => 'boolean',
                    'required'    => true,
                ],
                'is_taxable'          => [
                    'description' => __( 'Salary Head is Taxable', 'pay-check-mate' ),
                    'type'        => 'boolean',
                    'required'    => true,
                ],
                'is_personal_savings' => [
                    'description' => __( 'Salary Head is Personal Savings', 'pay-check-mate' ),
                    'type'        => 'boolean',
                    'required'    => true,
                ],
                'priority'            => [
                    'description' => __( 'Salary Head Priority', 'pay-check-mate' ),
                    'type'        => 'integer',
                    'context'     => [ 'view', 'edit', 'embed' ],
                ],
                'status'              => [
                    'description' => __( 'Salary Head Status', 'pay-check-mate' ),
                    'type'        => 'boolean',
                    'context'     => [ 'view', 'edit', 'embed' ],
                ],
                'created_on'          => [
                    'description' => __( 'The date the object was created.', 'pay-check-mate' ),
                    'type'        => 'string',
                    'format'      => 'date',
                    'context'     => [ 'view', 'edit', 'embed' ],
                ],
                'updated_at'          => [
                    'description' => __( 'The date the object was last updated.', 'pay-check-mate' ),
                    'type'        => 'string',
                    'format'      => 'date',
                    'context'     => [ 'view', 'edit' ],
                ],
            ],
        ];
    }
}
