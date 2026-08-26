<?php

namespace App\Http\Requests;

use App\Models\Package;
use Illuminate\Foundation\Http\FormRequest;

class StoreBookingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        // Existence + active + locale-currency + slot membership are all checked
        // in a single DB fetch inside withValidator()->after(), so rules() stays
        // structural only (no closure query that would duplicate the fetch).
        return [
            'name' => ['required', 'string', 'max:255'],
            'phone' => ['required', 'string', 'max:20', 'regex:/^[0-9\s\-]+$/'],
            'country_code' => ['required', 'string', 'max:5', 'regex:/^\+\d{1,4}$/'],
            'email' => ['nullable', 'email', 'max:255'],
            'package_id' => ['required', 'integer'],
            'session_date' => ['required', 'date', 'after_or_equal:today'],
            'session_time' => ['required', 'string', 'max:5', 'regex:/^\d{2}:\d{2}$/'],
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            $packageId = $this->input('package_id');
            $sessionDate = $this->input('session_date');
            $sessionTime = $this->input('session_time');

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
                $validator->errors()->add('package_id', 'Please select a valid package.');

                return;
            }

            if (! $sessionTime) {
                return;
            }

            $availableTimes = $package->available_times ?? [];

            if (! in_array($sessionTime, $availableTimes, true)) {
                $validator->errors()->add('session_time', 'Please select an available time for this package.');

                return;
            }

            if ($sessionDate === now()->format('Y-m-d')) {
                $now = now();
                [$hours, $minutes] = explode(':', $sessionTime);
                $slotDateTime = $now->copy()->setTime((int) $hours, (int) $minutes, 0);

                if ($slotDateTime <= $now) {
                    $validator->errors()->add('session_time', 'Please select a time that has not already passed.');
                }
            }
        });
    }

    public function messages(): array
    {
        return [
            'package_id.required' => 'Please select a package.',
            'package_id.integer' => 'Please select a valid package.',
            'phone.regex' => 'Please enter a valid phone number.',
            'country_code.regex' => 'Please select a valid country code.',
            'session_date.after_or_equal' => 'Please select today or a future date.',
            'session_time.regex' => 'Please select a valid time slot.',
        ];
    }
}
