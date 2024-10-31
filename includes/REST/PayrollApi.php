<?php

namespace PayCheckMate\REST;

use PayCheckMate\Classes\Helper;
use PayCheckMate\Models\DepartmentModel;
use PayCheckMate\Models\DesignationModel;
use PayCheckMate\Models\PayrollModel;
use PayCheckMate\Models\PayrollDetailsModel;
use PayCheckMate\Models\EmployeeModel;
use PayCheckMate\Contracts\HookAbleApiInterface;
use PayCheckMate\Models\SalaryHistoryModel;
use PayCheckMate\Requests\PayrollDetailsRequest;
use PayCheckMate\Requests\PayrollRequest;
use WP_Error;
use WP_REST_Request;
use WP_REST_Response;
use WP_REST_Server;

class PayrollApi extends RestController implements HookAbleApiInterface {

    public function __construct() {
        $this->namespace = 'pay-check-mate/v1';
        $this->rest_base = 'payrolls';
    }

    /**
     * Register routes.
     *
     * @since 1.0.0
     *
     * @return void
     */
    public function register_api_routes(): void {
        register_rest_route(
            $this->namespace,
            '/' . $this->rest_base, [
                [
                    'methods'             => WP_REST_Server::READABLE,
                    'callback'            => [ $this, 'get_payrolls' ],
                    'permission_callback' => [ $this, 'get_payrolls_permissions_check' ],
                    'args'                => $this->get_collection_params(),
                ],
                [
                    'methods'             => WP_REST_Server::CREATABLE,
                    'callback'            => [ $this, 'generate_payroll' ],
                    'permission_callback' => [ $this, 'generate_payroll_permissions_check' ],
                    'args'                => $this->get_endpoint_args_for_item_schema( \WP_REST_Server::CREATABLE ),
                ],
                'schema' => [ $this, 'get_public_item_schema' ],
            ]
        );
        register_rest_route(
            $this->namespace,
            '/' . $this->rest_base . '/save-payroll', [
                [
                    'methods'             => WP_REST_Server::CREATABLE,
                    'callback'            => [ $this, 'save_payroll' ],
                    'permission_callback' => [ $this, 'save_payroll_permissions_check' ],
                    'args'                => $this->get_endpoint_args_for_item_schema( \WP_REST_Server::CREATABLE ),
                ],
                'schema' => [ $this, 'get_public_item_schema' ],
            ]
        );
        // Update payroll sheet
        register_rest_route(
            $this->namespace,
            '/' . $this->rest_base . '/(?P<id>[\d]+)/update-payroll', [
                [
                    'methods'             => WP_REST_Server::EDITABLE,
                    'callback'            => [ $this, 'update_payroll_sheet' ],
                    'permission_callback' => [ $this, 'update_payroll_sheet_permissions_check' ],
                    'args'                => $this->get_endpoint_args_for_item_schema( \WP_REST_Server::CREATABLE ),
                ],
                'schema' => [ $this, 'get_public_item_schema' ],
            ]
        );
        register_rest_route(
            $this->namespace,
            '/' . $this->rest_base . '/payroll-register', [
                [
                    'methods'             => WP_REST_Server::CREATABLE,
                    'callback'            => [ $this, 'get_payroll_report' ],
                    'permission_callback' => [ $this, 'get_payroll_permissions_check' ],
                    'args'                => $this->get_payroll_register_collection_params(),
                ],
                'schema' => [ $this, 'get_public_item_schema' ],
            ]
        );
        register_rest_route(
            $this->namespace,
            '/' . $this->rest_base . '/payroll-ledger', [
                [
                    'methods'             => WP_REST_Server::CREATABLE,
                    'callback'            => [ $this, 'get_payroll_ledger' ],
                    'permission_callback' => [ $this, 'get_payroll_permissions_check' ],
                    'args'                => $this->get_payroll_ledger_collection_params(),
                ],
                'schema' => [ $this, 'get_public_item_schema' ],
            ]
        );
        register_rest_route(
            $this->namespace,
            '/' . $this->rest_base . '/(?P<id>[\d]+)', [
                [
                    'methods'             => WP_REST_Server::READABLE,
                    'callback'            => [ $this, 'get_payroll' ],
                    'permission_callback' => [ $this, 'get_payroll_permissions_check' ],
                    'args'                => $this->get_collection_params(),
                ],
                [
                    'methods'             => WP_REST_Server::EDITABLE,
                    'callback'            => [ $this, 'update_payroll' ],
                    'permission_callback' => [ $this, 'update_payroll_permissions_check' ],
                    'args'                => $this->get_endpoint_args_for_item_schema( \WP_REST_Server::EDITABLE ),
                ],
                [
                    'methods'             => WP_REST_Server::DELETABLE,
                    'callback'            => [ $this, 'delete_payroll' ],
                    'permission_callback' => [ $this, 'delete_payroll_permissions_check' ],
                    'args'                => $this->get_endpoint_args_for_item_schema( \WP_REST_Server::DELETABLE ),
                ],
                'schema' => [ $this, 'get_public_item_schema' ],
            ]
        );
    }

    public function get_payrolls_permissions_check(): bool {
        return current_user_can( 'pay_check_mate_view_payroll_list' );
    }

    public function generate_payroll_permissions_check(): bool {
        return current_user_can( 'pay_check_mate_add_payroll' );
    }

    public function save_payroll_permissions_check(): bool {
        return current_user_can( 'pay_check_mate_add_payroll' );
    }

    public function update_payroll_sheet_permissions_check(): bool {
        return current_user_can( 'pay_check_mate_edit_payroll' );
    }

    public function get_payroll_permissions_check(): bool {
        return current_user_can( 'pay_check_mate_view_payroll_details' );
    }

    public function update_payroll_permissions_check(): bool {
        return current_user_can( 'pay_check_mate_edit_payroll' );
    }

    public function delete_payroll_permissions_check(): bool {
        return current_user_can( 'pay_check_mate_delete_payroll' );
    }

    /**
     * Get a collection of items
     *
     * @since 1.0.0
     *
     * @param WP_REST_Request<array<string>> $request Full details about the request.
     *
     * @throws \Exception
     *
     * @return \WP_REST_Response Response object on success, or WP_Error object on failure.
     */
    public function get_payrolls( WP_REST_Request $request ): WP_REST_Response {
        $args = [
            'status'   => $request->get_param( 'status' ) ? sanitize_text_field( $request->get_param( 'status' ) ) : 'all',
            'limit'    => $request->get_param( 'per_page' ) ? sanitize_text_field( $request->get_param( 'per_page' ) ) : 10,
            'page'     => $request->get_param( 'page' ) ? sanitize_text_field( $request->get_param( 'page' ) ) : 1,
            'order'    => $request->get_param( 'order' ) ? sanitize_text_field( $request->get_param( 'order' ) ) : 'DESC',
            'order_by' => $request->get_param( 'order_by' ) ? sanitize_text_field( $request->get_param( 'order_by' ) ) : 'id',
            'search'   => $request->get_param( 'search' ) ? sanitize_text_field( $request->get_param( 'search' ) ) : '',
            'group_by' => $request->get_param( 'group_by' ) ? sanitize_text_field( $request->get_param( 'group_by' ) ) : '',
        ];

        $payroll_model = new PayrollModel();
        $payrolls      = $payroll_model->all( $args );

        $data = [];
        foreach ( $payrolls as $payroll ) {
            $item   = $this->prepare_item_for_response( $payroll, $request );
            $data[] = $this->prepare_response_for_collection( $item );
        }

        $total     = $payroll_model->count( $args );
        $max_pages = ceil( $total / (int) $args['limit'] );

        $response = new WP_REST_Response( $data );

        $response->header( 'X-WP-Total', (string) $total );
        $response->header( 'X-WP-TotalPages', (string) $max_pages );

        return new WP_REST_Response( $response, 200 );
    }

    /**
     * Create payroll.
     *
     * @since 1.0.0
     *
     * @param WP_REST_Request<array<string>> $request Full details about the request.
     *
     * @throws \Exception
     *
     * @return WP_REST_Response|WP_Error Response object on success, or WP_Error object on failure.
     */
    public function generate_payroll( WP_REST_Request $request ) {
        $parameters = $request->get_params();

        if ( ! isset( $parameters['payroll_date'] ) ) {
            return new WP_REST_Response( [ 'error' => 'The "date" parameter is required.' ], 400 );
        }

        $date              = gmdate( 'Y-m-d', strtotime( $parameters['payroll_date'] ) );
        $month             = gmdate( 'm', strtotime( $date ) );
        $year              = gmdate( 'Y', strtotime( $date ) );
        $last_day_of_month = gmdate( 't', strtotime( $date ) );

        $parameters['payroll_date'] = gmdate( 'Y-m-d', strtotime( $year . '-' . $month . '-' . $last_day_of_month ) );

        $args     = [
            'status'   => 1,
            'limit'    => '-1',
            'order'    => 'ASC',
            'order_by' => 'priority',
        ];
        $employee = apply_filters( 'pay_check_mate_before_generate_payroll', $parameters );
        if ( is_wp_error( $employee ) ) {
            return $employee;
        }
        $salary_head_types = Helper::get_salary_head( $args );

        $department_args = [
            'table'       => DepartmentModel::get_table(),
            'local_key'   => 'department_id',
            'foreign_key' => 'id',
            'join_type'   => 'left',
            'where'       => [
                'status' => [
                    'operator' => '=',
                    'value'    => 1,
                ],
            ],
            'fields'      => [
                'name as department_name',
            ],
        ];

        $designation_args = [
            'table'       => DesignationModel::get_table(),
            'local_key'   => 'designation_id',
            'foreign_key' => 'id',
            'join_type'   => 'left',
            'where'       => [
                'status' => [
                    'operator' => '=',
                    'value'    => 1,
                ],
            ],
            'fields'      => [
                'name as designation_name',
            ],
        ];

        // || 'all' !== $parameters['department_id']
        if ( ! empty( $parameters['department_id'] ) ) {
            $department_args['where']['id'] = [
                'operator' => '=',
                'value'    => $parameters['department_id'],
            ];
        }

        //  || 'all' !== $parameters['designation_id']
        if ( ! empty( $parameters['designation_id'] ) ) {
            $designation_args['where']['id'] = [
                'operator' => '=',
                'value'    => $parameters['designation_id'],
            ];
        }

        $args = [
            'status'          => 1,
            'limit'           => - 1,
            'order'           => 'ASC',
            'order_by'        => 'employee_id',
            'mutation_fields' => [
                'full_name',
            ],
            'where'           => [
                'joining_date' => [
                    'operator' => '<=',
                    'value'    => $parameters['payroll_date'],
                    'type'     => 'AND',
                ],
            ],
            'relations'       => [
                $designation_args,
                $department_args,
                [
                    'table'       => SalaryHistoryModel::get_table(),
                    'local_key'   => 'employee_id',
                    'foreign_key' => 'employee_id',
                    'join_type'   => 'left',
                    'where'       => [
                        'status' => [
                            'operator' => '=',
                            'value'    => 1,
                            'type'     => 'AND',
                        ],
                    ],
                    'select_max'  => [
                        'active_from' => [
                            'operator' => '<=',
                            'value'    => $parameters['payroll_date'],
                            'compare'  => [
                                'key'      => 'active_from',
                                'operator' => '<=',
                                'value'    => $parameters['payroll_date'],
                            ],
                        ],
                    ],
                    'fields'      => [
                        'basic_salary',
                        'gross_salary',
                        'salary_details',
                        'active_from',
                    ],
                ],
            ],
        ];

        $employees = new EmployeeModel();
        $employees = $employees->all(
            $args, [
				'id',
				'employee_id',
				'first_name',
				'last_name',
				'designation_id',
				'department_id',
				'joining_date',
			], $salary_head_types
        );

        $employees         = apply_filters( 'pay_check_mate_generate_payroll_employee_response', $employees, $salary_head_types, $parameters );
        $salary_head_types = apply_filters( 'pay_check_mate_generate_payroll_salary_head_types', $salary_head_types, $employees, $parameters );

        return new WP_REST_Response(
            [
                'salary_head_types'       => $salary_head_types,
                'employee_salary_history' => $employees,
            ], 200
        );
    }

    /**
     * Saves the payroll.
     *
     * @since 1.0.0
     *
     * @param WP_REST_Request<array<string>> $request Full details about the request.
     *
     * @throws \Exception
     *
     * @return WP_REST_Response|WP_Error Response object on success, or WP_Error object on failure.
     */
    public function save_payroll( WP_REST_Request $request ) {
        global $wpdb;
        $parameters      = $request->get_params();
        $created_user_id = get_current_user_id();
        if ( empty( $created_user_id ) ) {
            return new WP_Error(
                400, __( 'You are not authorized to perform this action.', 'pay-check-mate' ), [
                    'status' => 400,
                    'error'  => __( 'You are not authorized to perform this action.', 'pay-check-mate' ),
                ]
            );
        }

        $parameters['created_user_id'] = $created_user_id;

        $parameters['payroll_date'] = gmdate( 'Y-m-t', strtotime( $parameters['payroll_date'] ) );

        $validated_data = new PayrollRequest( $parameters );
        if ( $validated_data->error ) {
            return new WP_Error(
                400, implode( ', ', $validated_data->error ), [
                    'status' => 400,
                    'error'  => $validated_data->error,
                ]
            );
        }

        $employee = apply_filters( 'pay_check_mate_before_save_payroll', $parameters );
        if ( is_wp_error( $employee ) ) {
            return $employee;
        }
        // Start the transaction.
        $wpdb->query( 'START TRANSACTION' );

        $payroll = new PayrollModel();
        // @phpstan-ignore-next-line
        $previous_payroll = $payroll->get_payroll_by_date( $validated_data->payroll_date, [ 'status' => [ 2, 3, 4 ] ] );
        if ( $previous_payroll ) {
            return new WP_Error(
                400, __( 'Payroll already exists for this Month.', 'pay-check-mate' ), [
                    'status' => 400,
                    'error'  => __( 'Payroll already exists for this Month.', 'pay-check-mate' ),
                ]
            );
        }
        $inserted_payroll = $payroll->create( $validated_data );
        if ( is_wp_error( $inserted_payroll ) ) {
            return new WP_Error( 400, $inserted_payroll->get_error_message(), [ 'status' => 400 ] );
        }

        $payroll_details    = new PayrollDetailsModel();
        $details_parameters = $parameters['employee_salary_history'];
        $data['_wpnonce']   = $parameters['_wpnonce'];
        $data['payroll_id'] = $inserted_payroll->id;
        foreach ( $details_parameters as $detail ) {
            $data['employee_id']  = $detail['employee_id'];
            $data['basic_salary'] = $detail['basic_salary'];
            $data['gross_salary'] = $detail['gross_salary'];
            $merged_array         = [];
            array_walk_recursive(
                $detail['salary_details'], function ( $value, $key ) use ( &$merged_array ) {
					$merged_array[ $key ] = $value;
				}
            );
            $data['salary_details'] = wp_json_encode( $merged_array );

            $validated_details_data = new PayrollDetailsRequest( $data );
            if ( $validated_details_data->error ) {
                return new WP_Error(
                    400, implode( ', ', $validated_details_data->error ), [
                        'status' => 400,
                        'error'  => $validated_details_data->error,
                    ]
                );
            }

            $payroll_details->create( $validated_details_data );
            do_action( 'pay_check_mate_after_save_payroll', $validated_details_data->data, $parameters );
        }

        // If everything is fine, then commit the data.
        $wpdb->query( 'COMMIT' );

        return new WP_REST_Response(
            [
                'message' => __( 'Payroll saved successfully.', 'pay-check-mate' ),
            ], 200
        );
    }

    /**
     * Updates the payroll and payroll details.
     *
     * @since 1.0.0
     *
     * @param \WP_REST_Request<array<string>> $request Full details about the request.
     *
     * @throws \Exception
     * @return \WP_Error|\WP_REST_Response
     */
    public function update_payroll_sheet( WP_REST_Request $request ) {
        global $wpdb;
        $parameters = $request->get_params();
        $payroll_id = $parameters['id'];
        if ( ! $payroll_id ) {
            return new WP_Error(
                400, __( 'Payroll ID is required.', 'pay-check-mate' ), [
                    'status' => 400,
                    'error'  => __( 'Payroll ID is required.', 'pay-check-mate' ),
                ]
            );
        }
        $result = apply_filters( 'pay_check_mate_before_save_payroll', $parameters );
        if ( is_wp_error( $result ) ) {
            return $result;
        }

        $parameters['approved_user_id'] = get_current_user_id();
        $validated_data                 = new PayrollRequest( $parameters );
        if ( $validated_data->error ) {
            return new WP_Error(
                400, implode( ', ', $validated_data->error ), [
                    'status' => 400,
                    'error'  => $validated_data->error,
                ]
            );
        }

        // Start the transaction.
        $wpdb->query( 'START TRANSACTION' );

        $payroll = new PayrollModel();
        // @phpstan-ignore-next-line
        $previous_payroll = $payroll->get_payroll_by_date( $validated_data->payroll_date, [ 'id' => $payroll_id ] );
        if ( $previous_payroll ) {
            return new WP_Error(
                400, __( 'Payroll already exists for this Month.', 'pay-check-mate' ), [
                    'status' => 400,
                    'error'  => __( 'Payroll already exists for this Month.', 'pay-check-mate' ),
                ]
            );
        }
        $inserted_payroll = $payroll->update( $payroll_id, $validated_data );
        if ( is_wp_error( $inserted_payroll ) ) {
            return new WP_Error( 400, $inserted_payroll->get_error_message(), [ 'status' => 400 ] );
        }

        $payroll_details    = new PayrollDetailsModel();
        $details_parameters = $parameters['employee_salary_history'];
        $data['_wpnonce']   = $parameters['_wpnonce'];
        $data['payroll_id'] = $payroll_id;
        foreach ( $details_parameters as $detail ) {
            $payroll_details_id = $detail['payroll_details_id'];
            if ( ! $payroll_details_id ) {
                return new WP_Error(
                    400, __( 'Payroll Details ID is required.', 'pay-check-mate' ), [
                        'status' => 400,
                        'error'  => __( 'Payroll Details ID is required.', 'pay-check-mate' ),
                    ]
                );
            }
            $data['employee_id']  = $detail['employee_id'];
            $data['basic_salary'] = $detail['basic_salary'];
            $data['gross_salary'] = $detail['gross_salary'];
            $merged_array         = [];
            array_walk_recursive(
                $detail['salary_details'], function ( $value, $key ) use ( &$merged_array ) {
					$merged_array[ $key ] = $value;
				}
            );
            $data['salary_details'] = wp_json_encode( $merged_array );

            $validated_details_data = new PayrollDetailsRequest( $data );
            if ( $validated_details_data->error ) {
                return new WP_Error(
                    400, implode( ', ', $validated_details_data->error ), [
                        'status' => 400,
                        'error'  => $validated_details_data->error,
                    ]
                );
            }

            $payroll_details->update( $payroll_details_id, $validated_details_data );
        }

        // If everything is fine, then commit the data.
        $wpdb->query( 'COMMIT' );

        return new WP_REST_Response(
            [
                'message' => __( 'Payroll updated successfully.', 'pay-check-mate' ),
            ], 200
        );
    }

    /**
     * Gets the payroll.
     *
     * @since 1.0.0
     *
     * @param WP_REST_Request<array<string>> $request
     *
     * @throws \Exception
     *
     * @return WP_REST_Response Response object.
     */
    public function get_payroll( WP_REST_Request $request ): WP_REST_Response {
        $payroll_id = $request->get_param( 'id' );
        $payroll    = new PayrollModel();
        $payroll    = $payroll->find( $payroll_id );

        $args              = [
            'status'   => 1,
            'limit'    => '-1',
            'order'    => 'ASC',
            'order_by' => 'priority',
        ];
        $salary_head_types = Helper::get_salary_head( $args );

        $payroll_details = new PayrollDetailsModel();
        $args            = [
            'status'    => $request->get_param( 'status' ) ? sanitize_text_field( $request->get_param( 'status' ) ) : 'all',
            'limit'     => '-1',
            'order_by'  => 'employee_id',
            'order'     => 'ASC',
            'where'     => [
                'payroll_id' => [
                    'operator' => '=',
                    'value'    => $payroll_id,
                    'type'     => 'AND',
                ],
            ],
            'relations' => [
                [
                    'table'       => EmployeeModel::get_table(),
                    'local_key'   => 'employee_id',
                    'foreign_key' => 'employee_id',
                    'join_type'   => 'left',
                    'fields'      => [
                        'first_name',
                        'last_name',
                    ],
                ],
            ],
        ];

        $payroll_details   = $payroll_details->all( $args, [ '*', 'id as payroll_details_id' ], $salary_head_types );
        $payroll_details   = apply_filters( 'pay_check_mate_get_payroll_employee_response', $payroll_details, $salary_head_types, $request->get_params() );
        $salary_head_types = apply_filters( 'pay_check_mate_get_payroll_salary_head_types', $salary_head_types, $payroll_details, $request->get_params() );

        return new WP_REST_Response(
            [
                'payroll'                 => $payroll,
                'employee_salary_history' => $payroll_details,
                'salary_head_types'       => $salary_head_types,
            ], 200
        );
    }

    /**
     * Gets the payroll report.
     *
     * @since 1.0.0
     *
     * @param \WP_REST_Request<array<string>> $request
     *
     * @throws \Exception
     * @return WP_REST_Response|WP_Error
     */
    public function get_payroll_report( WP_REST_Request $request ) {
        $parameters = $request->get_params();
        if ( ! isset( $parameters['payroll_date'] ) ) {
            return new WP_Error(
                400, __( 'Payroll Date is required.', 'pay-check-mate' ), [
                    'status' => 400,
                    'error'  => __( 'Payroll Date is required.', 'pay-check-mate' ),
                ]
            );
        }

        $where = [];
        if ( ! empty( $parameters['department_id'] ) && 'all' !== $parameters['department_id'] ) {
            $where['department_id'] = [
                'operator' => '=',
                'value'    => $parameters['department_id'],
                'type'     => 'AND',
            ];
        }

        if ( ! empty( $parameters['designation_id'] ) && 'all' !== $parameters['designation_id'] ) {
            $where['designation_id'] = [
                'operator' => '=',
                'value'    => $parameters['designation_id'],
                'type'     => 'AND',
            ];
        }

        $payroll      = new PayrollModel();
        $payroll_args = [
            'status'        => 1,
            'order'         => 'DESC',
            'order_by'      => 'id',
            'where'         => $where,
            'where_between' => [
                'payroll_date' => [
                    'start' => gmdate( 'Y-m-01', strtotime( $parameters['payroll_date'] ) ),
                    'end'   => gmdate( 'Y-m-t', strtotime( $parameters['payroll_date'] ) ),
                ],
            ],
        ];

        $payroll_data = $payroll->all( $payroll_args, [ '*', 'id as payroll_id' ] );

        if ( empty( $payroll_data ) || empty( $payroll_data[0] ) ) {
            return new WP_Error(
                400, __( 'No payroll found.', 'pay-check-mate' ), [
                    'status' => 400,
                    'error'  => __( 'No payroll found.', 'pay-check-mate' ),
                ]
            );
        }

        $payroll_data      = $payroll_data[0];
        $args              = [
            'status'   => 1,
            'limit'    => '-1',
            'order'    => 'ASC',
            'order_by' => 'priority',
        ];
        $salary_head_types = Helper::get_salary_head( $args );

        $payroll_details = new PayrollDetailsModel();
        $args            = [
            'status'    => 1,
            'limit'     => '-1',
            'order_by'  => 'employee_id',
            'order'     => 'ASC',
            'where'     => [
                'payroll_id' => [
                    'operator' => '=',
                    'value'    => $payroll_data->payroll_id ?? 0,
                    'type'     => 'AND',
                ],
            ],
            'relations' => [
                [
                    'table'       => EmployeeModel::get_table(),
                    'local_key'   => 'employee_id',
                    'foreign_key' => 'employee_id',
                    'join_type'   => 'left',
                    'fields'      => [
                        'first_name',
                        'last_name',
                    ],
                ],
            ],
        ];

        $payroll_details = $payroll_details->all( $args, [ '*', 'id as payroll_details_id' ], $salary_head_types );

        return new WP_REST_Response(
            [
                'payroll'                 => $payroll_data,
                'employee_salary_history' => $payroll_details,
                'salary_head_types'       => $salary_head_types,
            ], 200
        );
    }

    /**
     * Get the payroll ledger.
     *
     * @since 1.0.0
     *
     * @param \WP_REST_Request<array<string>> $request Full details about the request.
     *
     * @throws \Exception
     * @return WP_REST_Response
     */
    public function get_payroll_ledger( WP_REST_Request $request ): WP_REST_Response {
        $employee_id = $request->get_param( 'employee_id' );
        $start_date  = $request->get_param( 'start_date' );
        $end_date    = $request->get_param( 'end_date' );
        if ( ! empty( $start_date ) && empty( $end_date ) ) {
            // Set end date to current month's last date
            $end_date = gmdate( 'Y-m-t' );
        }
        if ( ! isset( $employee_id ) ) {
            wp_send_json_error( __( 'Employee ID is required.', 'pay-check-mate' ) );
        }

        $args              = [
            'status'   => 1,
            'limit'    => '-1',
            'order'    => 'ASC',
            'order_by' => 'priority',
        ];
        $salary_head_types = Helper::get_salary_head( $args );

        $payroll_details = new PayrollDetailsModel();
        $args            = [
            'status'    => $request->get_param( 'status' ) ? sanitize_text_field( $request->get_param( 'status' ) ) : 'all',
            'limit'     => '-1',
            'order_by'  => 'employee_id',
            'order'     => 'ASC',
            'where'     => [
                'employee_id' => [
                    'operator' => '=',
                    'value'    => $employee_id,
                    'type'     => 'AND',
                ],
            ],
            'relations' => [
                [
                    'table'       => EmployeeModel::get_table(),
                    'local_key'   => 'employee_id',
                    'foreign_key' => 'employee_id',
                    'join_type'   => 'left',
                    'fields'      => [
                        'first_name',
                        'last_name',
                    ],
                ],
                [
                    'table'       => PayrollModel::get_table(),
                    'local_key'   => 'payroll_id',
                    'foreign_key' => 'id',
                    'join_type'   => 'left',
                    'fields'      => [
                        'payroll_date',
                    ],
                    'where'       => [
                        'status' => [
                            'operator' => '=',
                            'value'    => 1,
                        ],
                    ],
                ],
            ],
        ];

        if ( ! empty( $start_date ) && ! empty( $end_date ) ) {
            $args['relations'][1]['where_between']['payroll_date'] = [
                'start' => gmdate( 'Y-m-01', strtotime( $start_date ) ),
                'end'   => gmdate( 'Y-m-t', strtotime( $end_date ) ),
            ];
        }

        $payroll_details   = $payroll_details->all( $args, [ '*', 'id as payroll_details_id' ], $salary_head_types );
        $payroll_details   = apply_filters( 'pay_check_mate_get_payroll_ledger_response', $payroll_details, $salary_head_types, $request->get_params() );
        $salary_head_types = apply_filters( 'pay_check_mate_get_payroll_salary_head_types', $salary_head_types, $payroll_details, $request->get_params() );

        if ( empty( $payroll_details ) ) {
            wp_send_json_error( __( 'No payroll found.', 'pay-check-mate' ), 400 );
        }

        return new WP_REST_Response(
            [
                'employee_salary_history' => $payroll_details,
                'salary_head_types'       => $salary_head_types,
            ], 200
        );
    }


    /**
     * Updates the payroll.
     *
     * @since 1.0.0
     *
     * @param \WP_REST_Request<array<string>> $request Full details about the request.
     *
     * @throws \Exception
     * @return \WP_REST_Response|\WP_Error
     */
    public function update_payroll( WP_REST_Request $request ) {
        $request->set_param( 'approved_user_id', get_current_user_id() );
        $validated_data = new PayrollRequest( $request->get_params() );
        if ( ! empty( $validated_data->error ) ) {
            return new WP_Error( 500, __( 'Invalid data.', 'pay-check-mate' ), [ $validated_data->error ] );
        }

        // Do not update the payroll date.
        unset( $validated_data->payroll_date );
        $payroll = new PayrollModel();
        $updated = $payroll->update( $request->get_param( 'id' ), $validated_data );
        if ( is_wp_error( $updated ) ) {
            return new WP_Error( 500, $updated->get_error_message(), [ 'status' => 500 ] );
        }

        $payroll = $payroll->find( $request->get_param( 'id' ) );

        do_action( 'pay_check_mate_after_update_payroll_status', (array) $payroll );

        $item     = $this->prepare_item_for_response( $payroll, $request );
        $data     = $this->prepare_response_for_collection( $item );
        $response = new WP_REST_Response( $data );
        $response->set_status( 201 );

        return new WP_REST_Response( $response, 201 );
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
            'title'      => 'payroll',
            'type'       => 'object',
            'properties' => [
                'id'                  => [
                    'description' => __( 'Unique identifier for the payroll.', 'pay-check-mate' ),
                    'type'        => 'integer',
                    'context'     => [ 'view', 'edit', 'embed' ],
                    'readonly'    => true,
                ],
                'payroll_date'        => [
                    'description' => __( 'The date of the payroll', 'pay-check-mate' ),
                    'type'        => 'string',
                    'format'      => 'Y-m-d',
                    'required'    => true,
                    'readonly'    => true,
                    'context'     => [ 'view', 'embed' ],
                ],
                'payroll_date_string' => [
                    'description' => __( 'The date of the payroll in string format', 'pay-check-mate' ),
                    'type'        => 'string',
                    'format'      => 'Y-m-d',
                    'required'    => true,
                    'readonly'    => true,
                    'context'     => [ 'view', 'embed' ],
                ],
                'designation_id'      => [
                    'description' => __( 'Unique identifier for the designation.', 'pay-check-mate' ),
                    'type'        => 'integer',
                    'required'    => true,
                ],
                'department_id'       => [
                    'description' => __( 'Unique identifier for the department.', 'pay-check-mate' ),
                    'type'        => 'integer',
                    'required'    => true,
                ],
                'total_salary'        => [
                    'description' => __( 'Total salary for the payroll.', 'pay-check-mate' ),
                    'type'        => 'number',
                    'context'     => [ 'view', 'edit', 'embed' ],
                ],
                'remarks'             => [
                    'description' => __( 'Remarks for the payroll.', 'pay-check-mate' ),
                    'type'        => 'string',
                ],
                'status'              => [
                    'description' => __( 'Status of the payroll.', 'pay-check-mate' ),
                    'type'        => 'integer',
                ],
                'created_user_id'     => [
                    'description' => __( 'Unique identifier for the employee who created the payroll.', 'pay-check-mate' ),
                    'type'        => 'integer',
                ],
                'created_user'        => [
                    'description' => __( 'Created user name.', 'pay-check-mate' ),
                    'type'        => 'string',
                    'readonly'    => true,
                    'context'     => [ 'view', 'embed' ],
                ],
                'approved_user_id'    => [
                    'description' => __( 'Unique identifier for the employee who approved the payroll.', 'pay-check-mate' ),
                    'type'        => [ 'integer, null' ],
                ],
                'approved_user'       => [
                    'description' => __( 'Approved user name.', 'pay-check-mate' ),
                    'type'        => 'string',
                    'readonly'    => true,
                    'context'     => [ 'view', 'embed' ],
                ],
                'created_on'          => [
                    'description' => __( 'The date the payroll was created.', 'pay-check-mate' ),
                    'type'        => 'string',
                    'format'      => 'date-time',
                    'readonly'    => true,
                ],
                'updated_at'          => [
                    'description' => __( 'The date the payroll was last updated.', 'pay-check-mate' ),
                    'type'        => 'string',
                    'format'      => 'date-time',
                    'readonly'    => true,
                ],
            ],
        ];
    }

    /**
     * Retrieves the query params for the collections.
     *
     * @since 1.0.0
     *
     * @return array<array<string, mixed>> Collection parameters.
     */
    public function get_payroll_register_collection_params(): array {
        return [
            'department_id'  => [
                'description' => __( 'Unique identifier for the department.', 'pay-check-mate' ),
                'type'        => 'string',
                'required'    => true,
            ],
            'designation_id' => [
                'description' => __( 'Unique identifier for the designation.', 'pay-check-mate' ),
                'type'        => 'string',
                'required'    => true,
            ],
            'payroll_date'   => [
                'description' => __( 'The date of the payroll', 'pay-check-mate' ),
                'type'        => 'string',
                'format'      => 'Y-m-d',
                'required'    => true,
                'readonly'    => true,
                'context'     => [ 'view', 'embed' ],
            ],
        ];
    }

    /**
     * Retrieves the query params for the collections.
     *
     * @since 1.0.0
     *
     * @return array<array<string, mixed>> Collection parameters.
     */
    public function get_payroll_ledger_collection_params(): array {
        return [
            'employee_id' => [
                'description' => __( 'Unique identifier for the employee.', 'pay-check-mate' ),
                'type'        => 'string',
                'required'    => true,
            ],
            'start_date'  => [
                'description' => __( 'The start date of the payroll', 'pay-check-mate' ),
                'type'        => 'string',
                'format'      => 'Y-m',
            ],
            'end_date'    => [
                'description' => __( 'The end date of the payroll', 'pay-check-mate' ),
                'type'        => 'string',
                'format'      => 'Y-m',
            ],
        ];
    }
}
