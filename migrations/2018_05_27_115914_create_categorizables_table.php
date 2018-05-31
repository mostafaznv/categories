<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateCategorizablesTable extends Migration
{
    protected $table;
    protected $tableCategory;

    public function __construct()
    {
        $this->table = config('categories.tables.categorizables');
        $this->tableCategory = config('categories.tables.categories');
    }

    public function up()
    {
        Schema::create($this->table, function (Blueprint $table) {
            $table->integer('category_id')->unsigned();
            $table->morphs('categorizable');
            $table->timestamps();

            $table->unique(['category_id', 'categorizable_id', 'categorizable_type'], 'categorizables_ids_type_unique');
            $table->foreign('category_id')->references('id')->on($this->tableCategory)->onDelete('cascade')->onUpdate('cascade');
        });
    }

    public function down()
    {
        Schema::dropIfExists($this->table);
    }
}
