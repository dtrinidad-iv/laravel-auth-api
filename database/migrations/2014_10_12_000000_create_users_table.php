<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

use App\Models\User;

class CreateUsersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('user_name');
            $table->string('avatar')->nullable();
            $table->enum('user_role', ['admin', 'user']);
            $table->string('email')->unique();
            $table->timestamp('email_verified_at')->nullable();
            $table->timestamp('registered_at')->useCurrent();
            $table->string('password');
            $table->boolean('is_active')->default(false);
            $table->rememberToken();
            $table->timestamps();
        });


        $superUser = User::create([
            'name' => 'Super Admin',
            'user_name' => 'superadmin',
            'email' => 'superadmin@test.com',
            'password' => '$2y$10$eFKl46HVycRD3w4jPIIUouNPcbFldYKixmx6yxnyCn4lUWdlB71HG', // p@ssw0rd
            'role' => 'admin',
            'is_active' => true
        ]);

    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('users');
    }
}
