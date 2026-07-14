<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('course_connections', function (Blueprint $table) {
            $table->id();
            $table->string('canonical_course_code'); // The main course code used in transcripts
            $table->string('alias_course_code');     // The alternative course code
            $table->integer('canonical_course_id');  // The ID from course_mapping
            $table->integer('alias_course_id');      // The ID from course_mapping
            $table->text('notes')->nullable();       // For documentation
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            
            // Indexes for performance
            $table->index('canonical_course_code');
            $table->index('alias_course_code');
            $table->unique(['canonical_course_code', 'alias_course_code']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('course_connections');
    }
};