<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('addr_obj', function (Blueprint $table) {
            $table->id("OBJECTID")->index();
            $table->unsignedBigInteger("PARENTOBJID")->nullable();
            $table->integer("REGION");
            $table->string("NAME");
            $table->string("TYPENAME");
            $table->string("FULL_TYPENAME")->nullable();
            $table->string("LEVEL");
            $table->string("FULL_LEVEL")->nullable();
            $table->uuid("OBJECTGUID");
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('addr_obj');
    }
};
