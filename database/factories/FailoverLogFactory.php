<?php

namespace Database\Factories;

use App\Models\FailoverLog;
use Illuminate\Database\Eloquent\Factories\Factory;

class FailoverLogFactory extends Factory
{
    protected $model = FailoverLog::class;

    public function definition(): array
    {
        $from   = $this->faker->randomElement(['jh', 'upcloud']);
        $to     = $from === 'jh' ? 'upcloud' : 'jh';
        $status = $this->faker->randomElement(['success', 'failed', 'running', 'pending']);
        $start  = now()->subMinutes(rand(1, 60));
        $end    = $status !== 'pending' ? $start->copy()->addSeconds(rand(30, 600)) : null;

        return [
            'action'            => 'full_failover',
            'from_server'       => $from,
            'to_server'         => $to,
            'status'            => $status,
            'started_at'        => $start,
            'finished_at'       => $end,
            'duration_seconds'  => $end ? $start->diffInSeconds($end) : null,
            'triggered_by'      => 1,
            'triggered_by_name' => 'Admin',
            'message'           => $status === 'success' ? 'Failover completed.' : ($status === 'failed' ? 'Failover failed.' : null),
            'payload'           => [],
            'ip_address'        => $this->faker->ipv4(),
        ];
    }

    public function success(): static
    {
        return $this->state(['status' => 'success']);
    }

    public function failed(): static
    {
        return $this->state(['status' => 'failed']);
    }
}
