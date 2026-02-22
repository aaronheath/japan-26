<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('address', function (Blueprint $table) {
            $table->string('addressable_type')->nullable()->change();
            $table->unsignedBigInteger('addressable_id')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('address', function (Blueprint $table) {
            $table->string('addressable_type')->nullable(false)->change();
            $table->unsignedBigInteger('addressable_id')->nullable(false)->change();
        });
    }
};
