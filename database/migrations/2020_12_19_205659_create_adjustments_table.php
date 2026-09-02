<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create($this->table(), function (Blueprint $table) {
            $table->id();

            match (config('adjustfly.morph_key_type', 'id')) {
                'uuid' => $table->uuidMorphs('adjustable'),
                'ulid' => $table->ulidMorphs('adjustable'),
                default => $table->morphs('adjustable'),
            };

            $userKey = config('adjustfly.user.foreign_key', 'user_id');

            match (config('adjustfly.user.key_type', 'id')) {
                'uuid' => $table->uuid($userKey)->nullable(),
                'ulid' => $table->ulid($userKey)->nullable(),
                'string' => $table->string($userKey)->nullable(),
                default => $table->unsignedBigInteger($userKey)->nullable(),
            };

            $table->string('event')->nullable();
            $table->json('before')->nullable();
            $table->json('after')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index($userKey);
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists($this->table());
    }

    private function table(): string
    {
        return config('adjustfly.table', 'adjustments');
    }
};
