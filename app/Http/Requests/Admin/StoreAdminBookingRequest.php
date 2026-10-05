<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreAdminBookingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'min:2', 'max:255'],
            // Ad-hoc bookings are recorded after the session happened.
            'session_date' => ['required', 'string', 'date_format:Y-m-d', 'before_or_equal:'.now(config('booking.timezone'))->format('Y-m-d')],
            'session_time' => ['required', 'string', 'size:5', 'regex:/^([01]\d|2[0-3]):[0-5]\d$/'],
            'country_code' => ['required', 'string', 'max:5', Rule::in(array_keys(config('countries.dial_codes', [])))],
            'phone' => ['required', 'string', 'max:'.config('booking.validation.phone_max'), 'regex:/^[0-9]+$/', 'min:'.config('booking.validation.phone_min')],
            'currency' => ['required', Rule::in(array_keys(config('currencies.booking', [])))],
            'amount' => ['required', 'numeric', 'min:0.01', 'max:99999999.99', 'decimal:0,2'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ];
    }

    protected function prepareForValidation(): void
    {
        if (is_string($this->input('country_code'))) {
            $countryCode = trim($this->input('country_code'));
            $this->merge(['country_code' => $countryCode !== '' && ! str_starts_with($countryCode, '+') ? '+'.$countryCode : $countryCode]);
        }
        if (is_string($this->input('phone'))) {
            $this->merge(['phone' => preg_replace('/[\s\-]+/u', '', $this->input('phone'))]);
        }
        if (is_string($this->input('notes'))) {
            $notes = trim($this->input('notes'));
            $this->merge(['notes' => $notes === '' ? null : $notes]);
        }
    }

    public function messages(): array
    {
        return [
            'name.required' => 'Customer name is required.',
            'name.min' => 'Customer name must be at least 2 characters.',
            'session_date.required' => 'Session date is required.',
            'session_date.before_or_equal' => 'Session date cannot be in the future.',
            'session_time.required' => 'Session time is required.',
            'session_time.regex' => 'Please enter a valid time.',
            'session_time.size' => 'Please enter a valid time.',
            'country_code.required' => 'Please select a country code.',
            'country_code.in' => 'Please select a valid country code.',
            'phone.required' => 'Phone number is required.',
            'phone.min' => 'Please enter a valid phone number.',
            'phone.regex' => 'Please enter a valid phone number.',
            'currency.required' => 'Please select a currency.',
            'currency.in' => 'Please select a valid currency.',
            'amount.required' => 'Amount is required.',
            'amount.numeric' => 'Amount must be a number.',
            'amount.min' => 'Amount must be greater than zero.',
            'amount.decimal' => 'Amount can have at most 2 decimal places.',
            'notes.max' => 'Notes cannot exceed 2000 characters.',
        ];
    }
}
