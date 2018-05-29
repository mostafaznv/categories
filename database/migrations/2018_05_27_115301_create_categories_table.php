<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

class CreateCategoriesTable extends Migration
{
    protected $table;

    public function __construct()
    {
        $this->table = config('categories.tables.categories');
    }

    public function up()
    {
        Schema::create($this->table, function(Blueprint $table) {
            $table->increments('id');
            $table->string('slug');
            $table->{$this->jsonable()}('name');
            $table->{$this->jsonable()}('description')->nullable();
            $table->tinyInteger('type')->nullable();
            //$table->string('hex', 7)->nullable();
            $table->{$this->jsonable()}('stats')->nullable();

            $table->nestedSet();

            $table->timestamps();
            $table->softDeletes();

            // Indexes
            $table->unique('slug');
        });
    }


    public function down()
    {
        Schema::dropIfExists($this->table);
    }

    /**
     * Get jsonable column data type.
     *
     * @return string
     */
    protected function jsonable()
    {
        return DB::connection()->getPdo()->getAttribute(PDO::ATTR_DRIVER_NAME) === 'mysql' && version_compare(DB::connection()->getPdo()->getAttribute(PDO::ATTR_SERVER_VERSION), '5.7.8', 'ge') ? 'json' : 'text';
    }
}
