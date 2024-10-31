<?php

namespace PayCheckMate\Requests;

class PayrollRequest extends Request {

    protected static string $nonce = 'pay_check_mate_nonce';

    protected static array $fillable = [ 'department_id', 'designation_id', 'payroll_date', 'created_user_id', 'total_salary' ];

    // Have to create a rule that will validate $request in next.
    protected static array $rules = [
        'department_id'    => 'sanitize_text_field',
        'designation_id'   => 'sanitize_text_field',
        'payroll_date'     => 'sanitize_text_field',
        'total_salary'     => 'sanitize_text_field',
        'remarks'          => 'sanitize_text_field',
        'status'           => 'absint',
        'created_user_id'  => 'absint',
        'approved_user_id' => 'absint',
    ];
}
