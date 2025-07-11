# DB Table: actions
This table is responsible for handling miscellaneous action logging.

**This table is sharded by `target`.**


|COLUMN_NAME   |COLUMN_TYPE |IS_NULLABLE|COLUMN_KEY|COLUMN_DEFAULT     |EXTRA         |
|--------------|------------|-----------|----------|-------------------|--------------|
|id            |bigint(21)  |NO         |PRI       |                   |auto_increment|
|actor         |bigint(21)  |NO         |MUL       |                   |              |
|target        |bigint(21)  |NO         |MUL       |                   |              |
|descr         |varchar(255)|NO         |MUL       |                   |              |
|tag           |varchar(50) |NO         |MUL       |                   |              |
|created_at    |timestamp   |NO         |MUL       |current_timestamp()|              |
|actor_username|varchar(25) |NO         |MUL       |                   |              |


**actor**: the users.id of the person who performed the action, it will be the same as the target id in case we're logging actions that a player performs on herself, like updating profile information. In case someone executes an action that affects a player from the BO the actor will the the id of that administrator.

**target**: the users.id of the person who was affected by the action.

**descr**: free text with info about the action, Ideally, it should not be relied on or used in business logic, but there are some instances where there was no other choice (for example, historical data for DGOJ/ICS reports). Please be careful if changing the structure of existing messages.

**tag**: a shorter descriptive text in one word that should always be the same for a particular action, bonus_fail could for instance be used when a user fails a bonus for whatever reason, that can later be used in an SQL statement to get all actions of this type.

**actor_username**: the users.username of the actor, it's a de-normalization so that we don't have to do a left join to get the username in a lot of places.