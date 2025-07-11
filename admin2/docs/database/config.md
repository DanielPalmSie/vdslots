# DB Table: config
This table is used in various places where non-developers need to be able to configure fundamental aspects of the site, such as for instance deposit limits or which PSPs should be subject to the auto withdrawal logic / cron job.

/var/www/videoslots/phive/modules/Config/Config.php is a wrapper for this table in Phive.

**This table is global.**

|COLUMN_NAME |COLUMN_TYPE |IS_NULLABLE|COLUMN_KEY|COLUMN_DEFAULT|EXTRA         |
|------------|------------|-----------|----------|--------------|--------------|
|id          |bigint(21)  |NO         |PRI       |              |auto_increment|
|config_name |varchar(255)|NO         |MUL       |              |              |
|config_tag  |varchar(255)|NO         |MUL       |              |              |
|config_value|text        |NO         |          |              |              |
|config_type |varchar(255)|NO         |          |              |              |

**config_name**: the name of the config value, this would correspond to the key in a PHP array.

**config_tag**: the tag of the config value, this would correspond to the variable name of the PHP array.

**config_value**: the actual value.

**config_type**: type hinting that controls the BO interface that is responsible for updating this table, it also validates submitted data.