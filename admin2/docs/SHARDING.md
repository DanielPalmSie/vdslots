

//TODO complete this doc

**SHARDING TODO LIST**

- Add support to shards on environment config and into Capsule Service Provider. [DONE]
- Queries using Eloquent Query Builder by key. [DONE]
- Queries using Query Builder by key. [DONE]
- Raw queries by key. [DONE]
- Query logging support for all the database connections. [DONE]
- Only use a shard connection when the table has been configured as sharded. [DONE]
- Add simple support to cross node queries on sharded/non global tables merging results. [DONE]
- Add use an advance method to run queries and merge results using mysqli_poll. [DONE]
- Cross-shard aggregated functions (count) [DONE]
- Cross-shard sorting and nested sorting [DONE]
- Cross-shard limit and offset [DONE]
- Add support to cross-shard Inserts / Updates. [DONE]
- Add support to cross-shard Deletes. [DONE]
- Add support to cross-shard Migrations. [DONE]
- When inserting in a sharded table primary key cannot collide between nodes [DONE]

**HOW TO USE SHARDING QUERY BUILDER**

_Initial configuration_: copy config/local.php.example as config/local.php

- **Raw queries or query builder (not eloquent)**: 

    `use App\Extensions\Database\FManager as DB;`
    
    Available classes:
   - `DB::shSelect($key, $query, $bindings)`
   - `DB::shTable($key, $table_name)`

- **Eloquent Builder classes**:
    
    Each model need to extend FModel class: `use App\Extensions\Database\FModel;`
    
    Available classes:
    `MyModel::sh($key)`

- **How to debug queries**:
    to be able to know if a sharded node has been used by your query, you just need
    to put APP_DEBUG to true and you will be able to see the query logging result
    at the bottom of the page with the connection name preceding the query inside square brackets.
    In case is a cross-shard query you will see that preceding the name of the connection.

**SHARDING CLASSES**

@see App\Providers\DatabaseServiceProvider

@see App\Extensions\Database

**SHARDED TABLES EXAMPLES**

**Using raw queries:**

`DB::shSelect($user_id, 'trophy_award_ownership', "SELECT * FROM trophy_award_ownership WHERE user_id = :user_id AND status > 0 AND created_at > :start_date", ['user_id' => $user_id, 'start_date' => $date_range['start_date']]);`

**Using query builder:**

`DB::shTable($user_id, 'trophy_award_ownership')
            ->where('trophy_award_ownership.user_id', $user_id)
            ->where('trophy_award_ownership.status', '>', 0)
            ->where('trophy_award_ownership.created_at', '>', $date_range['start_date'])
            ->get());`
            
**Using Eloquent Builders with the model:**

`TrophyAwardOwnership::sh($user_id)
            ->with('trophy_award')
            ->where('trophy_award_ownership.user_id', $user->getKey())
            ->where('trophy_award_ownership.status', '>', 0)
            ->where('trophy_award_ownership.created_at', '>', $date_range['start_date']);`