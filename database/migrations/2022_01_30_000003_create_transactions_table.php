<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateTransactionsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('transactions', function (Blueprint $table) {
            $table->id();
            $table->string('ext_id')->nullable();
            $table->string('ext_provider')->nullable();
            $table->bigInteger('account_id')->unsigned()->nullable();
            $table->enum('type', ['debit', 'credit'])->default('debit');
            $table->string('description')->nullable();
            $table->float('amount', 0.0);
            $table->timestamps();

            $table->foreign('account_id')->references('id')->on('accounts')
                ->onUpdate('cascade')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('transactions', function (Blueprint $table) {
            $table->dropForeign(['account_id']);

            $table->dropColumn('account_id');
        });
    }
}
