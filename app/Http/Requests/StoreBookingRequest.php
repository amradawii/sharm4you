<?php

namespace App\Http\Requests;

use App\Models\Package;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Carbon;
use Illuminate\Validation\Rule;

class StoreBookingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        if (is_string($this->input('phone'))) {
            $this->merge(['phone' => preg_replace('/[\s\-]+/u', '', $this->input('phone'))]);
        }
    }

    public function rules(): array
    {
        // Existence + active + locale-currency + slot membership are all checked
        // in a single DB fetch inside withValidator()->after(), so rules() stays
        // structural only (no closure query that would duplicate the fetch).
        return [
            'name' => ['required', 'string', 'min:'.config('booking.validation.name_min'), 'max:'.config('booking.validation.name_max')],
            'phone' => ['required', 'string', 'min:'.config('booking.validation.phone_min'), 'max:'.config('booking.validation.phone_max'), 'regex:/^[0-9]+$/'],
            'country_code' => ['required', 'string', Rule::in(config('countries.public_dial_codes'))],
            'email' => ['nullable', 'string', 'email', 'max:'.config('booking.validation.email_max')],
            'package_id' => ['required', 'integer'],
            'session_date' => ['required', 'string', 'date_format:Y-m-d', 'after_or_equal:'.now(config('booking.timezone'))->format('Y-m-d')],
            'session_time' => ['required', 'string', 'size:5', 'regex:/^([01]\d|2[0-3]):[0-5]\d$/'],
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            $packageId = $this->input('package_id');
            $sessionDate = $this->input('session_date');
            $sessionTime = $this->input('session_time');

            if ($validator->errors()->has('package_id')) {
                return;
            }

            if (! $validator->errors()->has('phone') && ! $validator->errors()->has('country_code')
                && strlen(ltrim($this->input('country_code'), '+').$this->input('phone')) > 15) {
                $validator->errors()->add('phone', __('site.booking.validation.phone_invalid'));
            }

            if (! $packageId) {
                // 'required' rule already covers this; nothing to add.
                return;
            }

            $localeCurrency = config('currencies.locale_map.'.app()->getLocale(), 'USD');

            // Single fetch validating existence + active + locale currency.
            $package = Package::where('id', $packageId)
                ->where('is_active', true)
                ->where('currency', $localeCurrency)
                ->first();

            if (! $package) {
                $validator->errors()->add('package_id', __('site.booking.validation.package_required'));

                return;
            }

            if ($validator->errors()->has('session_time') || $validator->errors()->has('session_date')) {
                return;
            }

            if (! $sessionTime) {
                return;
            }

            $availableTimes = $package->available_times ?? [];

            if (! in_array($sessionTime, $availableTimes, true)) {
                $validator->errors()->add('session_time', __('site.booking.validation.time_required'));

                return;
            }

            $now = now(config('booking.timezone'));
            if ($sessionDate === $now->format('Y-m-d')) {
                [$hours, $minutes] = explode(':', $sessionTime);
                $slotDateTime = Carbon::createFromFormat('!Y-m-d', $sessionDate, config('booking.timezone'))->setTime((int) $hours, (int) $minutes, 0);

                if ($slotDateTime <= $now) {
                    $validator->errors()->add('session_time', __('site.booking.validation.time_required'));
                }
            }
        });
    }

    public function messages(): array
    {
        $messages = [];
        foreach ([
            'name.required' => 'name_required', 'name.string' => 'name_required', 'name.min' => 'name_min', 'name.max' => 'name_max',
            'phone.required' => 'phone_required', 'phone.string' => 'phone_invalid', 'phone.min' => 'phone_invalid', 'phone.max' => 'phone_invalid', 'phone.regex' => 'phone_invalid',
            'country_code.required' => 'country_code_invalid', 'country_code.string' => 'country_code_invalid', 'country_code.in' => 'country_code_invalid',
            'email.string' => 'email_invalid', 'email.email' => 'email_invalid', 'email.max' => 'email_max',
            'package_id.required' => 'package_required', 'package_id.integer' => 'package_required',
            'session_date.required' => 'date_required', 'session_date.string' => 'date_invalid', 'session_date.date_format' => 'date_invalid', 'session_date.after_or_equal' => 'date_invalid',
            'session_time.required' => 'time_required', 'session_time.string' => 'time_required', 'session_time.size' => 'time_required', 'session_time.regex' => 'time_required',
        ] as $rule => $key) {
            $messages[$rule] = __('site.booking.validation.'.$key);
        }

        return $messages;
    }
}
