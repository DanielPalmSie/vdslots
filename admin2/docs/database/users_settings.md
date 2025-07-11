# DB Table: users_settings

This is a basic key / value store of settings pertaining to players.

**This table is sharded.**

|COLUMN_NAME|COLUMN_TYPE |IS_NULLABLE|COLUMN_KEY|COLUMN_DEFAULT     |EXTRA         |
|-----------|------------|-----------|----------|-------------------|--------------|
|id         |bigint(21)  |NO         |PRI       |                   |auto_increment|
|user_id    |bigint(21)  |NO         |MUL       |                   |              |
|setting    |varchar(100)|NO         |MUL       |                   |              |
|value      |text        |NO         |          |                   |              |
|created_at |timestamp   |NO         |MUL       |current_timestamp()|              |

**setting**: this column in combination with user_id forms a unique key.

**value**: the setting.