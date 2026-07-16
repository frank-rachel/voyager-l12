<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class CreateUserRolesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        // Laravel 11 removed its doctrine/dbal integration, taking
        // Connection::getDoctrineColumn() with it. Schema::getColumnType() is the
        // native replacement, but it reports the driver's own type name — Postgres
        // says "int8" where MySQL says "bigint" — so match on all the spellings.
        $type = strtolower(Schema::getColumnType(DB::getTablePrefix().'users', 'id'));
        $isBigInt = in_array($type, ['bigint', 'int8', 'bigserial'], true);

        Schema::create('user_roles', function (Blueprint $table) use ($isBigInt) {
            if ($isBigInt) {
                $table->bigInteger('user_id')->unsigned()->index();
            } else {
                $table->integer('user_id')->unsigned()->index();
            }

            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->bigInteger('role_id')->unsigned()->index();
            $table->foreign('role_id')->references('id')->on('roles')->onDelete('cascade');
            $table->primary(['user_id', 'role_id']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('user_roles');
    }
}
