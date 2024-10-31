<?php

namespace PayCheckMate\Models;

use PayCheckMate\Requests\SalaryHistoryRequest;

class SalaryHistoryModel extends Model {

    protected static string $table = 'employee_salary_history';

    /**
     * @var array|string[] $search_by
     */
    protected static array $search_by = [ 'employee_id', 'active_from' ];

    /**
     * @var array|string[] $columns
     */
    protected static array $columns = [
        'employee_id'    => '%d',
        'basic_salary'   => '%d',
        'gross_salary'   => '%d',
        'salary_details' => '%s',
        'status'         => '%d',
        'active_from'    => '%s',
        'remarks'        => '%s',
        'salary_purpose' => '%s',
        'created_on'     => '%s',
        'updated_at'     => '%s',
    ];

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
     * Get basic salary
     */
    public function get_basic_salary(): float {
        // @phpstan-ignore-next-line
        return doubleval( $this->basic_salary );
    }

    /**
     * Get salary details.
     *
     * @since 1.0.0
     *
     * @return array<string, mixed>
     */
    public function get_salary_details(): array {
        return [
            // @phpstan-ignore-next-line
            'salary_details' => json_decode( $this->salary_details, true ),
        ];
    }

    /**
     * Employee salary increment.
     *
     * @since 1.0.0
     *
     * @param SalaryHistoryRequest $request
     *
     * @throws \Exception
     *
     * @return mixed|object|\WP_Error
     */
    public function employee_salary_increment(SalaryHistoryRequest $request){
        return $this->create( $request );
    }

}
