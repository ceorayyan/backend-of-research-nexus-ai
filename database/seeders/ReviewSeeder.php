<?php

namespace Database\Seeders;

use App\Models\Review;
use App\Models\Article;
use App\Models\ReviewMember;
use App\Models\User;
use Illuminate\Database\Seeder;

class ReviewSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $users = User::all();

        if ($users->count() < 2) {
            $this->command->warn('Please create at least 2 users before running this seeder.');
            return;
        }

        $creator = $users->first();
        $member = $users->skip(1)->first();

        // Create a sample review
        $review = Review::create([
            'user_id' => $creator->id,
            'title' => 'Machine Learning in Healthcare',
            'description' => 'A comprehensive review of machine learning applications in healthcare systems.',
            'status' => 'active',
        ]);

        // Add articles
        Article::create([
            'review_id' => $review->id,
            'title' => 'Deep Learning for Medical Image Analysis',
            'authors' => 'Smith, J., Johnson, M., Williams, R.',
            'abstract' => 'This paper presents a comprehensive overview of deep learning techniques applied to medical image analysis, including CT scans, MRI, and X-ray images.',
            'url' => 'https://example.com/article1',
        ]);

        Article::create([
            'review_id' => $review->id,
            'title' => 'Natural Language Processing in Clinical Documentation',
            'authors' => 'Brown, A., Davis, B., Miller, C.',
            'abstract' => 'An exploration of NLP techniques for extracting meaningful information from clinical notes and medical records.',
            'url' => 'https://example.com/article2',
        ]);

        // Add members
        ReviewMember::create([
            'review_id' => $review->id,
            'user_id' => $member->id,
            'role' => 'reviewer',
            'invited_at' => now(),
            'accepted_at' => now(),
        ]);

        $this->command->info('Review seeder completed successfully!');
    }
}
