<?php

namespace Database\Factories;

use App\Models\Manifest;
use App\Models\ManifestAudit;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class ManifestAuditFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = ManifestAudit::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        $actions = ['closed', 'unlocked', 'auto_complete'];
        $action = $this->faker->randomElement($actions);
        
        $reasons = [
            'closed' => 'Manual closure by admin',
            'unlocked' => 'Need to update package information',
            'auto_complete' => 'All packages delivered automatically'
        ];

        return [
            'manifest_id' => Manifest::factory(),
            'user_id' => User::factory(),
            'action' => $action,
            'reason' => $reasons[$action] ?? $this->faker->sentence(),
            'performed_at' => $this->faker->dateTimeBetween('-30 days', 'now'),
        ];
    }

    /**
     * Indicate that the audit is for a closed action
     */
    public function closed(): Factory
    {
        return $this->state(function (array $attributes) {
            return [
                'action' => 'closed',
                'reason' => 'Manifest closed manually by administrator',
            ];
        });
    }

    /**
     * Indicate that the audit is for an unlocked action
     */
    public function unlocked(): Factory
    {
        return $this->state(function (array $attributes) {
            return [
                'action' => 'unlocked',
                'reason' => 'Unlocked to make corrections to package information',
            ];
        });
    }

    /**
     * Indicate that the audit is for an auto-complete action
     */
    public function autoComplete(): Factory
    {
        return $this->state(function (array $attributes) {
            return [
                'action' => 'auto_complete',
                'reason' => 'All packages in manifest marked as delivered',
            ];
        });
    }

    /**
     * Set a specific manifest for the audit
     */
    public function forManifest(Manifest $manifest): Factory
    {
        return $this->state(function (array $attributes) use ($manifest) {
            return [
                'manifest_id' => $manifest->id,
            ];
        });
    }

    /**
     * Set a specific user for the audit
     */
    public function byUser(User $user): Factory
    {
        return $this->state(function (array $attributes) use ($user) {
            return [
                'user_id' => $user->id,
            ];
        });
    }
}
