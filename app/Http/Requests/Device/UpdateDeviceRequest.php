<?php

namespace App\Http\Requests\Device;

use App\Enums\DeviceStatus;
use App\Models\Device;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateDeviceRequest extends FormRequest
{
    public function authorize(): bool
    {
        /** @var Device $device */
        $device = $this->route('device');

        return $this->user()->can('update', $device);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        /** @var Device $device */
        $device = $this->route('device');

        return [
            'company_id' => [
                'nullable',
                'integer',
                'exists:companies,id',
            ],

            'vehicle_id' => [
                'nullable',
                'exists:vehicles,id',
            ],

            'name' => [
                'required',
                'string',
                'max:255',
            ],

            'unique_id' => [
                'required',
                'string',
                'max:255',
                Rule::unique('devices', 'unique_id')->ignore($device),
            ],

            'model' => [
                'nullable',
                'string',
                'max:255',
            ],

            'phone' => [
                'nullable',
                'string',
                'max:255',
            ],

            'status' => [
                'required',
                Rule::enum(DeviceStatus::class),
            ],
        ];
    }
}