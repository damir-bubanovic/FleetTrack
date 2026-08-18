<?php

namespace App\Data\Traccar;

final readonly class DeviceData
{
    public function __construct(
        public int $id,
        public string $name,
        public string $uniqueId,
        public ?string $model = null,
        public ?string $phone = null,
        public ?string $status = null,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            id: (int) $data['id'],
            name: (string) $data['name'],
            uniqueId: (string) $data['uniqueId'],
            model: isset($data['model']) ? (string) $data['model'] : null,
            phone: isset($data['phone']) ? (string) $data['phone'] : null,
            status: isset($data['status']) ? (string) $data['status'] : null,
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'uniqueId' => $this->uniqueId,
            'model' => $this->model,
            'phone' => $this->phone,
            'status' => $this->status,
        ];
    }
}
