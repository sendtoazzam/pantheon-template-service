<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('user_profiles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            
            // Personal Information
            $table->string('first_name')->nullable();
            $table->string('last_name')->nullable();
            $table->string('middle_name')->nullable();
            $table->string('display_name')->nullable();
            $table->date('date_of_birth')->nullable();
            $table->enum('gender', ['male', 'female', 'other', 'prefer_not_to_say'])->nullable();
            $table->string('nationality')->nullable();
            $table->string('ethnicity')->nullable();
            $table->string('religion')->nullable();
            $table->string('marital_status')->nullable();
            
            // Contact Information
            $table->string('primary_phone')->nullable();
            $table->string('secondary_phone')->nullable();
            $table->string('emergency_contact_name')->nullable();
            $table->string('emergency_contact_phone')->nullable();
            $table->string('emergency_contact_relationship')->nullable();
            
            // Address Information
            $table->text('home_address')->nullable();
            $table->string('home_city')->nullable();
            $table->string('home_state')->nullable();
            $table->string('home_country')->nullable();
            $table->string('home_postal_code')->nullable();
            $table->decimal('home_latitude', 10, 8)->nullable();
            $table->decimal('home_longitude', 11, 8)->nullable();
            
            $table->text('work_address')->nullable();
            $table->string('work_city')->nullable();
            $table->string('work_state')->nullable();
            $table->string('work_country')->nullable();
            $table->string('work_postal_code')->nullable();
            $table->decimal('work_latitude', 10, 8)->nullable();
            $table->decimal('work_longitude', 11, 8)->nullable();
            
            // Professional Information
            $table->string('occupation')->nullable();
            $table->string('job_title')->nullable();
            $table->string('company_name')->nullable();
            $table->string('industry')->nullable();
            $table->text('work_experience')->nullable();
            $table->json('skills')->nullable(); // Array of skills
            $table->json('languages')->nullable(); // Array of languages with proficiency
            $table->text('bio')->nullable();
            $table->text('interests')->nullable();
            $table->json('hobbies')->nullable();
            
            // Education
            $table->string('highest_education')->nullable();
            $table->string('university')->nullable();
            $table->string('degree')->nullable();
            $table->string('field_of_study')->nullable();
            $table->year('graduation_year')->nullable();
            $table->json('certifications')->nullable(); // Array of certifications
            
            // Social Media and Online Presence
            $table->string('website')->nullable();
            $table->string('linkedin_url')->nullable();
            $table->string('twitter_handle')->nullable();
            $table->string('facebook_url')->nullable();
            $table->string('instagram_handle')->nullable();
            $table->string('youtube_channel')->nullable();
            $table->json('social_media_links')->nullable(); // Additional social links
            
            // Health and Medical
            $table->string('blood_type')->nullable();
            $table->text('medical_conditions')->nullable();
            $table->text('allergies')->nullable();
            $table->text('medications')->nullable();
            $table->string('emergency_medical_contact')->nullable();
            $table->string('insurance_provider')->nullable();
            $table->string('insurance_policy_number')->nullable();
            
            // Preferences and Settings
            $table->string('preferred_language')->default('en');
            $table->string('timezone')->default('UTC');
            $table->string('currency')->default('USD');
            $table->boolean('dark_mode_enabled')->default(false);
            $table->json('notification_preferences')->nullable();
            $table->json('privacy_settings')->nullable();
            $table->json('communication_preferences')->nullable();
            
            // Lifestyle and Interests
            $table->json('dietary_preferences')->nullable(); // vegetarian, vegan, etc.
            $table->json('dietary_restrictions')->nullable(); // allergies, intolerances
            $table->string('smoking_status')->nullable();
            $table->string('drinking_status')->nullable();
            $table->json('fitness_level')->nullable();
            $table->json('sports_interests')->nullable();
            $table->json('entertainment_preferences')->nullable();
            $table->json('travel_preferences')->nullable();
            
            // Financial Information
            $table->string('annual_income_range')->nullable();
            $table->string('employment_status')->nullable();
            $table->string('credit_score_range')->nullable();
            $table->json('financial_goals')->nullable();
            $table->json('investment_interests')->nullable();
            
            // Family Information
            $table->integer('number_of_children')->nullable();
            $table->json('children_ages')->nullable();
            $table->string('spouse_name')->nullable();
            $table->string('spouse_occupation')->nullable();
            $table->json('family_members')->nullable(); // Extended family info
            
            // Custom Fields
            $table->json('custom_fields')->nullable(); // User-defined custom fields
            $table->json('tags')->nullable(); // User tags for categorization
            $table->text('notes')->nullable(); // Internal notes about the user
            
            // Verification and Trust
            $table->boolean('identity_verified')->default(false);
            $table->timestamp('identity_verified_at')->nullable();
            $table->string('verification_document_type')->nullable();
            $table->string('verification_document_number')->nullable();
            $table->integer('trust_score')->nullable(); // 0-100 trust rating
            $table->json('verification_status')->nullable();
            
            // Activity and Engagement
            $table->integer('profile_views')->default(0);
            $table->integer('profile_completeness')->default(0); // Percentage
            $table->timestamp('last_profile_update')->nullable();
            $table->timestamp('last_activity')->nullable();
            $table->json('activity_summary')->nullable();
            
            // Timestamps
            $table->timestamps();
            
            // Indexes
            $table->index(['user_id']);
            $table->index(['first_name', 'last_name']);
            $table->index(['date_of_birth']);
            $table->index(['occupation', 'industry']);
            $table->index(['home_city', 'home_country']);
            $table->index(['identity_verified', 'trust_score']);
            $table->index(['profile_completeness']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_profiles');
    }
};