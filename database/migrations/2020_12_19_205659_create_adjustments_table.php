<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $userKey = config('adjustfly.user.foreign_key', 'user_id');
        $usersTable = $this->usersTable();

        Schema::create('adjustments', function (Blueprint $table) use ($userKey, $usersTable) {
            $table->id();

            // The models you track. If they use UUID or ULID keys, swap this for
            // $table->uuidMorphs('adjustable') or $table->ulidMorphs('adjustable').
            $table->morphs('adjustable');

            // The user credited with the adjustment. Deleting a user keeps the
            // audit row and nulls the actor.
            //
            // foreignId() is an unsigned big integer. If your users table has a
            // UUID or ULID key, replace it with foreignUuid() or foreignUlid() —
            // the rest of the chain stays the same.
            $table->foreignId($userKey)
                ->nullable()
                ->constrained($usersTable)
                ->nullOnDelete();

            $table->string('event')->nullable();
            $table->json('before')->nullable();
            $table->json('after')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->timestamps();
            $table->softDeletes();

            // MySQL indexes foreign key columns automatically; PostgreSQL and
            // SQLite do not, so declare it explicitly.
            $table->index($userKey);
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('adjustments');
    }

    /**
     * The table the credited users live in, taken from the configured user
     * model rather than assumed to be "users".
     */
    private function usersTable(): string
    {
        $model = config('adjustfly.user.model')
            ?? config('auth.providers.users.model')
            ?? 'App\Models\User';

        return class_exists($model) ? (new $model)->getTable() : 'users';
    }
};
