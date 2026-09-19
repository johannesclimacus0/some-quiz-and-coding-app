<?php

namespace Database\Seeders;

use App\Enums\UserRole;
use App\Models\Answer;
use App\Models\Question;
use App\Models\Quiz;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Sequence;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AdminQuizSeeder extends Seeder
{
    public function run(): void
    {
        $admin = User::query()->firstOrNew([
            'email' => 'testadmin@example.org',
        ]);

        $admin->forceFill([
            'name' => 'Test Admin',
            'role' => UserRole::Admin,
            'email_verified_at' => now(),
            'password' => Hash::make('password'),
        ])->save();

        Quiz::factory()
            ->count(12)
            ->create()
            ->each(function (Quiz $quiz): void {
                Question::factory()
                    ->count(fake()->numberBetween(5, 7))
                    ->for($quiz)
                    ->sequence(fn (Sequence $sequence): array => [
                        'position' => $sequence->index,
                    ])
                    ->create()
                    ->each(function (Question $question): void {
                        Answer::factory()
                            ->count(4)
                            ->for($question)
                            ->sequence(fn (Sequence $sequence): array => [
                                'position' => $sequence->index,
                                'is_correct' => $sequence->index === 0,
                            ])
                            ->create();
                    });
            });
    }
}
