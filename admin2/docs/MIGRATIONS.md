**HOW TO CREATE A MIGRATION**

./console mig:generate MigrationName


**MIGRATION TEMPLATE**

``` 
 <?php
 use Phpmig\Migration\Migration;
 use App\Extensions\Database\Schema\Blueprint; //Important to have auto-completion for methods related to sharding
 
 class MigrationName extends Migration
 {
     protected $table;
 
     protected $schema;
 
     public function init()
     {
         $this->table = 'table_name';
         $this->schema = $this->get('schema');
     }
     /**
      * Do the migration
      */
     public function up()
     {
         $this->schema->create($this->table, function (Blueprint $table) {
             $table->asGlobal(); //Table is global
             $table->asSharded(); //Table is sharded
             $table->asMaster(); // Only in master
 
             $table->bigIncrements('id');
             $table->bigInteger('user_id');
         });
     }
 
     /**
      * Undo the migration
      */
     public function down()
     {
         $this->schema->drop($this->table);
     }
 }
