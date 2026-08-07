<?php

namespace App\Http\Requests\Device;

use App\Enums\DeviceStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreDeviceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('devices.create');
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'company_id' => [
                'required',
                'exists:companies,id',
            ],

            'vehicle_id' => [
                'nullable',
                'exists:vehicles,id',
            ],

            'traccar_device_id' => [
                'required',
                'integer',
                'unique:devices,traccar_device_id',
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
                'unique:devices,unique_id',
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

            'last_sync_at' => [
                'nullable',
                'date',
            ],
        ];
    }
}
