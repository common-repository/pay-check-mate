<?php

namespace PayCheckMate\Models;

class SalaryHeadModel extends Model {

    /**
     * The table associated with the model.
     *
     * @since  1.0.0
     *
     * @var string
     *
     * @access protected
     */
    protected static string $table = 'salary_heads';

    /**
     * @var array|string[] $search_by
     */
    protected static array $search_by = [ 'head_name' ];

    /**
     * @var array|string[] $columns
     */
    protected static array $columns = [
        'head_name'                  => '%s',
        'head_type'                  => '%s',
        'head_amount'                => '%f',
        'is_percentage'              => '%d',
        'is_variable'                => '%d',
        'is_taxable'                 => '%d',
        'is_personal_savings'        => '%d',
        'priority'                   => '%d',
        'status'                     => '%d',
        'created_on'                 => '%s',
        'updated_at'                 => '%s',
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
     * Get head type string
     *
     * @since 1.0.0
     *
     * @param string $head_type
     *
     * @return array<string, string>
     */
    public function get_head_type( string $head_type ): array {
        return [
            'head_type'      => $head_type,
            'head_type_text' => $head_type === '1' ? __( 'Earning', 'pay-check-mate' ) : __( 'Deduction', 'pay-check-mate' ),
        ];
    }

}
