<?php

namespace Database\Factories;

use App\Models\RegistrationPayment;
use App\Models\SpmbRegistration;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<RegistrationPayment>
 */
class RegistrationPaymentFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'spmb_registration_id' => SpmbRegistration::factory(),
            'amount' => fake()->numberBetween(1, 5) * 50_000,
            'unique_code' => fake()->numberBetween(1, 999),
            'method' => RegistrationPayment::METHOD_MANUAL_TRANSFER,
            'status' => RegistrationPayment::STATUS_UNPAID,
            'expires_at' => now()->addHours(48),
        ];
    }

    public function waitingVerification(): static
    {
        return $this->state([
            'status' => RegistrationPayment::STATUS_WAITING,
            'bank_account' => 'BSI 7123456789 a.n. Yayasan',
            'sender_name' => fake()->name(),
            'transferred_on' => now()->toDateString(),
            'proof_path' => 'ppdb-bukti/1/bukti.jpg',
            'submitted_at' => now(),
        ]);
    }

    public function paid(): static
    {
        return $this->waitingVerification()->state([
            'status' => RegistrationPayment::STATUS_PAID,
            'verified_at' => now(),
        ]);
    }

    public function expired(): static
    {
        return $this->state([
            'status' => RegistrationPayment::STATUS_UNPAID,
            'expires_at' => now()->subDay(),
        ]);
    }
}
