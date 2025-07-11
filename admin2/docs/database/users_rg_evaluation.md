# DB Table: users_rg_evaluation

This table is to store user's RG flag evaluation steps.

**This table is sharded.**

|COLUMN_NAME|COLUMN_TYPE |IS_NULLABLE|COLUMN_KEY|COLUMN_DEFAULT     |EXTRA         |
|-----------|------------|-----------|----------|-------------------|--------------|
|id         |bigint(21)  |NO         |PRI       |                   |auto_increment|
|user_id    |bigint(21)  |NO         |       |                   |              |
|trigger_name    |varchar(6)|NO         |       |                   |              |
|step      |enum('started', 'self-assessment', 'manual-review')        |NO         |MUL      |                   |              |
|processed    |tinyint(1)|NO         |       |0                   |              |
|result    |tinyint(1)|NO         |       |0                   |              |
|created_at |timestamp   |NO         |       |current_timestamp()|              |

**trigger_name**: The RG flag name. For example, RG7

**step**: Based on the evaluation process, the value for this field could be started, self-assessment or manual-review

**processed**: This column indicates whether the entry has been processed or not. If processed, this field will be set to 1

**result**: This column indicates whether further evaluation is needed or not. If further action is required, this field will be set to 1
