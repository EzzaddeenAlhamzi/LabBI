<?php

namespace Database\Factories;

use App\Models\Patient;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Patient>
 */
class PatientFactory extends Factory
{
    protected $model = Patient::class;

    public function definition(): array
    {
        return [
            'user_id' => null,
            'medical_file_number' => 'MF-FCT-'.fake()->unique()->numerify('######'),
            'full_name' => 'مريض مصنع '.fake()->numerify('###'),
            'date_of_birth' => fake()->dateTimeBetween('-70 years', '-18 years')->format('Y-m-d'),
            'sex' => fake()->randomElement(['male', 'female']),
            'phone' => fake()->numerify('05########'),
            'national_id' => null,
            'primary_doctor_id' => null,
        ];
    }
}
