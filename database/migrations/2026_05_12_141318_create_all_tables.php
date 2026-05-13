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
        // Cache table
        Schema::create('cache', function (Blueprint $table) {
            $table->string('key', 191)->primary();
            $table->mediumText('value');
            $table->bigInteger('expiration');
            $table->index('expiration');
        });

        // Cache locks table
        Schema::create('cache_locks', function (Blueprint $table) {
            $table->string('key', 191)->primary();
            $table->string('owner', 191);
            $table->bigInteger('expiration');
            $table->index('expiration');
        });

        // Courses table
        Schema::create('courses', function (Blueprint $table) {
            $table->id();
            $table->string('code', 255);
            $table->string('subject', 255);
            $table->bigInteger('credits');
            $table->timestamps();
        });

        // Failed jobs table
        Schema::create('failed_jobs', function (Blueprint $table) {
            $table->id();
            $table->string('uuid', 191)->unique();
            $table->text('connection');
            $table->text('queue');
            $table->longText('payload');
            $table->longText('exception');
            $table->timestamp('failed_at')->useCurrent();
        });

        // Jobs table
        Schema::create('jobs', function (Blueprint $table) {
            $table->id();
            $table->string('queue', 191);
            $table->longText('payload');
            $table->tinyInteger('attempts')->unsigned();
            $table->integer('reserved_at')->unsigned()->nullable();
            $table->integer('available_at')->unsigned();
            $table->integer('created_at')->unsigned();
            $table->index('queue');
        });

        // Job batches table
        Schema::create('job_batches', function (Blueprint $table) {
            $table->string('id', 191)->primary();
            $table->string('name', 191);
            $table->integer('total_jobs');
            $table->integer('pending_jobs');
            $table->integer('failed_jobs');
            $table->longText('failed_job_ids');
            $table->mediumText('options')->nullable();
            $table->integer('cancelled_at')->nullable();
            $table->integer('created_at');
            $table->integer('finished_at')->nullable();
        });

        // Migrations table (Laravel's default)
        Schema::create('migrations', function (Blueprint $table) {
            $table->increments('id');
            $table->string('migration', 191);
            $table->integer('batch');
        });

        // Password reset tokens table
        Schema::create('password_reset_tokens', function (Blueprint $table) {
            $table->string('email', 191)->primary();
            $table->string('token', 191);
            $table->timestamp('created_at')->nullable();
        });

        // People table
        Schema::create('people', function (Blueprint $table) {
            $table->id();
            $table->string('username', 255)->unique();
            $table->string('fullname', 255);
            $table->string('password', 255);
            $table->string('dob', 15)->nullable();
            $table->string('sex', 10)->nullable();
            $table->integer('majorid');
            $table->integer('campusid')->nullable();
            $table->integer('typeid')->nullable();
            $table->string('cell', 255)->nullable();
            $table->string('address', 255)->nullable();
            $table->string('email', 255)->nullable();
            $table->string('nationalityid', 255)->nullable();
            $table->string('religionid', 155)->nullable();
            $table->year('startyear')->nullable();
            $table->string('sponsor', 255)->nullable();
            $table->string('role', 128)->nullable();
            $table->string('image_path', 100)->nullable();
            $table->string('marital', 10)->nullable();
            $table->string('spcell', 15)->nullable();
            $table->string('spemail', 100)->nullable();
            $table->timestamps();
        });

        // Registration table
        Schema::create('registration', function (Blueprint $table) {
            $table->id();
            $table->string('studentid', 30);
            $table->integer('semesterid');
            $table->integer('courseid');
            $table->string('campusid', 100)->nullable();
            $table->string('grade', 11)->nullable();
            $table->string('assign1', 9)->nullable();
            $table->integer('midterm')->nullable();
            $table->float('points')->nullable();
            $table->string('res', 100)->nullable();
            $table->integer('adviserapp')->nullable();
            $table->integer('fnceapp')->nullable();
            $table->integer('credits')->nullable();
            $table->timestamps();
        });

        // Sessions table
        Schema::create('sessions', function (Blueprint $table) {
            $table->string('id', 191)->primary();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->longText('payload');
            $table->integer('last_activity');
            $table->index('user_id');
            $table->index('last_activity');
        });

        // Users table (Laravel's default)
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('name', 191);
            $table->string('email', 191)->unique();
            $table->timestamp('email_verified_at')->nullable();
            $table->string('password', 191);
            $table->string('remember_token', 100)->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('users');
        Schema::dropIfExists('sessions');
        Schema::dropIfExists('registration');
        Schema::dropIfExists('people');
        Schema::dropIfExists('password_reset_tokens');
        Schema::dropIfExists('migrations');
        Schema::dropIfExists('job_batches');
        Schema::dropIfExists('jobs');
        Schema::dropIfExists('failed_jobs');
        Schema::dropIfExists('courses');
        Schema::dropIfExists('cache_locks');
        Schema::dropIfExists('cache');
    }
};