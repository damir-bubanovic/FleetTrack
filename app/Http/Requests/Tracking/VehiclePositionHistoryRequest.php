<?php

namespace App\Http\Requests\Tracking;

use Carbon\CarbonImmutable;
use Closure;
use Illuminate\Foundation\Http\FormRequest;
use Throwable;

class VehiclePositionHistoryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'from' => [
                'required',
                'date',
            ],
            'to' => [
                'required',
                'date',
                'after:from',
                function (string $attribute, mixed $value, Closure $fail): void {
                    $from = $this->input('from');

                    if (! is_string($from)) {
                        return;
                    }

                    try {
                        $fromDate = CarbonImmutable::parse($from);
                        $toDate = CarbonImmutable::parse((string) $value);
                    } catch (Throwable) {
                        return;
                    }

                    if ($fromDate->diffInHours($toDate) > 168) {
                        $fail('The selected date range may not exceed 7 days.');
                    }
                },
            ],
        ];
    }
}
