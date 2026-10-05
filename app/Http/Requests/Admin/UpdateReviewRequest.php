<?php

namespace App\Http\Requests\Admin;

class UpdateReviewRequest extends StoreReviewRequest
{
    public function rules(): array
    {
        $rules = parent::rules();
        unset($rules['status']);

        return $rules;
    }
}
