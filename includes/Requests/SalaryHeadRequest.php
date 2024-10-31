<?php

namespace PayCheckMate\Requests;

class SalaryHeadRequest extends Request {

    protected static string $nonce = 'pay_check_mate_nonce';

    protected static array $fillable = [ 'head_name', 'head_type', 'head_amount', 'is_percentage', 'is_variable', 'is_taxable', 'is_personal_savings', 'priority' ];

    // Have to create a rule that will validate $request in next.
    protected static array $rules
        = [
            'head_name'            => 'sanitize_text_field',
            'head_type'            => 'absint',
            'head_amount'          => 'floatval',
            'is_percentage'        => 'absint',
            'is_variable'          => 'absint',
            'is_taxable'           => 'absint',
            'is_personal_savings'  => 'absint',
            'priority'             => 'absint',
            'status'               => 'absint',
            'created_on'           => 'sanitize_text_field',
            'updated_at'           => 'sanitize_text_field',
        ];
}
