<?php
namespace PayCheckMate\Classes;

class Databases {

    /**
     * Charset collate of DB.
     *
     * @var string
     */
    protected string $charset_collate;

    /**
     * Database prefix.
     *
     * @var string
     */
    protected string $prefix;
    protected string $table_prefix;

    /**
     * Constructor.
     */
    public function __construct() {
        global $wpdb;

        $this->charset_collate = $wpdb->get_charset_collate();
        $this->prefix          = $wpdb->prefix;
        $this->table_prefix    = $wpdb->prefix . 'pay_check_mate_';

        $this->create_tables();
    }

    /**
     * Create tables.
     *
     * @since 1.0.0
     * @return void
     */
    public function create_tables() {
        $this->create_table_departments();
        $this->create_table_designation();
        $this->create_table_salary_head();
        $this->create_table_employees();
        $this->create_table_employee_salary_history();
        $this->create_table_employee_payroll();
        $this->create_table_employee_payroll_details();

        // Update option that all tables are created for this version.
        update_option( 'pay_check_mate_tables_created', PAY_CHECK_MATE_PLUGIN_VERSION );
    }

    /**
     * Create table departments.
     *
     * @since 1.0.0
     *
     * @return void
     */
    public function create_table_departments() {
        $this->include_db_delta();

        $sql = "CREATE TABLE IF NOT EXISTS `{$this->table_prefix}departments` (
			    `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			    `name` varchar(255) NOT NULL,
			    `status` tinyint(1) NOT NULL DEFAULT '1',
			    `created_on` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
			    `updated_at` DATETIME NULL ON UPDATE CURRENT_TIMESTAMP,
			    PRIMARY KEY (`id`)
			) {$this->charset_collate};";

        dbDelta( $sql );
    }

    /**
     * Create table designation.
     *
     * @since 1.0.0
     *
     * @return void
     */
    public function create_table_designation() {
        $this->include_db_delta();

        $sql = "CREATE TABLE IF NOT EXISTS `{$this->table_prefix}designations` (
                `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
                `name` varchar(255) NOT NULL,
                `status` tinyint(1) NOT NULL DEFAULT '1',
                `created_on` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                `updated_at` DATETIME NULL ON UPDATE CURRENT_TIMESTAMP,
                PRIMARY KEY (`id`)
            ) {$this->charset_collate};";

        dbDelta( $sql );
    }

    /**
     * Create table salary head.
     *
     * @since 1.0.0
     *
     * @return void
     */
    public function create_table_salary_head() {
        $this->include_db_delta();

        // In the future, we will use (is_personal_savings) for PF.
        $sql = "CREATE TABLE IF NOT EXISTS `{$this->table_prefix}salary_heads` (
                `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
                `head_name` varchar(255) NOT NULL,
                `head_type` tinyint(1) NOT NULL DEFAULT '1' Comment '1 = Earning, 2 = Deduction',
                `head_amount` decimal(10,2) NOT NULL,
                `is_percentage` tinyint(1) NOT NULL DEFAULT '1' Comment '0 = No, 1 = Yes',
                `is_variable` tinyint(1) NOT NULL DEFAULT '1' Comment '0 = No, 1 = Yes',
                `is_taxable` tinyint(1) NOT NULL DEFAULT '1' Comment '0 = No, 1 = Yes',
                `is_personal_savings` tinyint(1) NOT NULL DEFAULT '0' Comment '0 = No, 1 = Yes',
                `priority` int(11) NOT NULL DEFAULT '0',
                `status` tinyint(1) NOT NULL DEFAULT '1',
                `created_on` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                `updated_at` DATETIME NULL ON UPDATE CURRENT_TIMESTAMP,
                PRIMARY KEY (`id`)
            ) {$this->charset_collate};";

        dbDelta( $sql );
    }

    /**
     * Create table employees.
     *
     * @since 1.0.0
     *
     * @return void
     */
    public function create_table_employees() {
        $this->include_db_delta();

        $sql = "CREATE TABLE IF NOT EXISTS `{$this->table_prefix}employees` (
                `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
                `employee_id` varchar(255) NOT NULL UNIQUE,
                `user_id` bigint(20) unsigned NOT NULL,
                `department_id` bigint(20) unsigned NOT NULL,
                `designation_id` bigint(20) unsigned NOT NULL,
                `first_name` varchar(255) NOT NULL,
                `last_name` varchar(255) NOT NULL,
                `email` varchar(255) NOT NULL,
                `phone` varchar(255) NOT NULL,
                `bank_name` varchar(255) NULL,
                `bank_account_number` varchar(255) NULL,
                `tax_number` varchar(255) NULL,
                `address` varchar(255) NOT NULL,
                `joining_date` DATE NOT NULL,
                `resign_date` DATE NULL,
                `status` tinyint(1) NOT NULL DEFAULT '1',
                `created_on` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                `updated_at` DATETIME NULL ON UPDATE CURRENT_TIMESTAMP,
                PRIMARY KEY (`id`)
            ) {$this->charset_collate};";

        dbDelta( $sql );
    }

    /**
     * Create table employee salary.
     *
     * @since 1.0.0
     *
     * @return void
     */
	public function create_table_employee_salary_history() {
        $this->include_db_delta();

        $sql = "CREATE TABLE IF NOT EXISTS `{$this->table_prefix}employee_salary_history` (
                `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
                `employee_id` bigint(20) unsigned NOT NULL,
                `basic_salary` decimal(10,2) NOT NULL,
                `gross_salary` decimal(10,2) NOT NULL,
                `salary_details` text NOT NULL, /*JSON Format. key value pair, key = salary head id, value = amount*/
                `status` tinyint(1) NOT NULL DEFAULT '1',
                `active_from` DATE NOT NULL,
                `remarks` text NULL,
                `salary_purpose` tinyint(1) NOT NULL DEFAULT '1', /*'1 = Initial, 2 = Increment, 3 = Promotion' */
                `created_on` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                `updated_at` DATETIME NULL ON UPDATE CURRENT_TIMESTAMP,
                PRIMARY KEY (`id`)
            ) {$this->charset_collate};";

        dbDelta( $sql );
	}

    /**
     * Create table employee payroll.
     *
     * @since 1.0.0
     *
     * @return void
     */
    public function create_table_employee_payroll() {
        $this->include_db_delta();

        $sql = "CREATE TABLE IF NOT EXISTS `{$this->table_prefix}payroll` (
                `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
                `department_id` bigint(20) unsigned NOT NULL,
                `designation_id` bigint(20) unsigned NOT NULL,
                `payroll_date` DATE NOT NULL,
                `total_salary` decimal(10,2) NOT NULL,
                `remarks` text NULL,
                `status` tinyint(1) NOT NULL DEFAULT 0,
                `created_user_id` bigint(20) unsigned NOT NULL,
                `approved_user_id` bigint(20) unsigned NULL,
                `created_on` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                `updated_at` DATETIME NULL ON UPDATE CURRENT_TIMESTAMP,
                PRIMARY KEY (`id`)
            ) {$this->charset_collate};";

        dbDelta( $sql );
    }

    /**
     * Create table employee payroll details.
     *
     * @since 1.0.0
     *
     * @return void
     */
    public function create_table_employee_payroll_details() {
        $this->include_db_delta();

        $sql = "CREATE TABLE IF NOT EXISTS `{$this->table_prefix}payroll_details` (
                `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
                `payroll_id` bigint(20) unsigned NOT NULL,
                `employee_id` bigint(20) unsigned NOT NULL,
                `basic_salary` decimal(10,2) NOT NULL,
                `salary_details` text NOT NULL, /*JSON Format. key value pair, key = salary head id, value = amount*/
                `status` tinyint(1) NOT NULL DEFAULT 1, /*1 = Current, 2 = Arear*/
                `created_on` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                `updated_at` DATETIME NULL ON UPDATE CURRENT_TIMESTAMP,
                PRIMARY KEY (`id`)
            ) {$this->charset_collate};";

        dbDelta( $sql );
    }


    /**
     * Include the db delta file
     *
     * @return void
     */
    protected function include_db_delta(): void {
        if ( ! function_exists( 'dbDelta' ) ) {
            require ABSPATH . 'wp-admin/includes/upgrade.php';
        }
    }
}
