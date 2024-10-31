<?php

namespace PayCheckMate\Models;

class EmployeeModel extends Model {

    protected static string $find_key = 'employee_id';

    /**
     * @var array|string[] $search_by
     */
    protected static array $search_by = [ 'employee_id', 'first_name', 'last_name', 'email' ];

    protected static string $table = 'employees';

    /**
     * @var array|string[] $columns
     */
    protected static array $columns = [
        'employee_id'         => '%s',
        'user_id'             => '%d',
        'department_id'       => '%d',
        'designation_id'      => '%d',
        'first_name'          => '%s',
        'last_name'           => '%s',
        'email'               => '%s',
        'phone'               => '%s',
        'bank_name'           => '%s',
        'bank_account_number' => '%s',
        'tax_number'          => '%s',
        'address'             => '%s',
        'joining_date'        => '%s',
        'resign_date'         => '%s',
        'status'              => '%d',
        'created_on'          => '%s',
        'updated_at'          => '%s',
    ];

    /**
     * Find employee by id.
     *
     * @since 1.0.0
     *
     * @param int $employee_id
     *
     * @throws \Exception
     * @return $this
     */
    public function find_employee( int $employee_id ): EmployeeModel {
        $this->find( $employee_id, [] );

        return $this;
    }

    /**
     * Get employee data.
     *
     * @since 1.0.0
     *
     * @return array<string, mixed>
     */
    public function get_data(): array {
        return (array) $this->data;
    }

    public function set_employee( object $data ): void {
        if ( ! isset( $data->employee_id ) ) {
            $this->data = [];
        }
        $this->data = $data;
    }

    /**
     * Make crated on mutation
     *
     * @since 1.0.0
     *
     * @return string
     */
    public function set_created_on(): string {
        return current_time( 'mysql', true );
    }

    /**
     * Make updated at mutation
     *
     * @since 1.0.0
     *
     * @return string
     */
    public function set_updated_at(): string {
        return current_time( 'mysql', true );
    }

    /**
     * Get created at mutated date.
     *
     * @since 1.0.0
     *
     * @param string $date
     *
     * @return string
     */
    public function get_created_on( string $date ): string {
        return get_date_from_gmt( $date, 'd M Y' );
    }

    /**
     * Get employee full name
     *
     * @since 1.0.0
     *
     * @return string
     */
    public function get_full_name(): string {
        // @phpstan-ignore-next-line
        return $this->first_name . ' ' . $this->last_name;
    }

    /**
     * Get employee joining date
     *
     * @since 1.0.0
     *
     * @param string $date
     *
     * @return array<string, string>
     */
    public function get_joining_date( string $date ): array {
        return [
            'joining_date'        => $date,
            'joining_date_string' => get_date_from_gmt( $date, 'd M, Y' ),
        ];
    }

    /**
     * Get employee regine date
     *
     * @since 1.0.0
     *
     * @param string|null $date
     *
     * @return string
     */
    public function get_resign_date( ?string $date ): string {
        if ( ! $date ) {
            return 'N/A';
        }

        return get_date_from_gmt( $date, 'd M Y' );
    }


    /**
     * Get employee salary heads
     *
     * @since 1.0.0
     *
     * @param string               $salary_details
     * @param array<array<string>> $salary_head_types
     *
     * @return array<array<string, mixed>>
     */
    public function get_salary_details( string $salary_details, array $salary_head_types ): array {
        $salary_details = json_decode( $salary_details, true );
        if ( empty( $salary_head_types ) ) {
            return [
                'salary_details' => $salary_details,
            ];
        }

        $salary = [
            'salary_details' => [
                'earnings'    => [],
                'deductions'  => [],
                'non_taxable' => [],
            ],
        ];

        foreach ( $salary_details as $key => $amount ) {
            foreach ( array_keys( $salary_head_types ) as $type ) {
                if ( array_key_exists( $key, $salary_head_types[$type] ) ) {
                    $salary['salary_details'][$type][$key] = $amount;
                }
            }
        }

        return $salary;
    }

    /**
     * Get employee Id.
     *
     * @since 1.0.0
     *
     * @return string
     */
    public function get_employee_id(): string {
        // @phpstan-ignore-next-line
        return $this->employee_id;
    }

    public function get_user_id(): string {
        // @phpstan-ignore-next-line
        return $this->user_id;
    }

    /**
     * Get employee by user id.
     *
     * @since 1.0.0
     *
     * @param int $user_id
     *
     * @throws \Exception
     * @return \PayCheckMate\Models\EmployeeModel
     */
    public function get_employee_by_user_id( int $user_id ): EmployeeModel {
        $new_find_key   = self::$find_key;
        self::$find_key = 'user_id';
        $data           = $this->find( $user_id );
        self::$find_key = $new_find_key;

        $this->data = $data;

        return $this;
    }
}
