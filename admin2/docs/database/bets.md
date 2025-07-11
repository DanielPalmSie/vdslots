# DB Table: bets
This is the table that is logging all bets, as opposed to deposits and withdrawals no transactions are generated in cash_transactions so this is not an auxiliary table, it's a bona fide log in its own right.

All transactions logged here are affecting the player balance (users.cash_balance).

**This table is sharded.**

|COLUMN_NAME|COLUMN_TYPE |IS_NULLABLE|COLUMN_KEY|COLUMN_DEFAULT     |EXTRA         |
|-----------|------------|-----------|----------|-------------------|--------------|
|id         |bigint(21)  |NO         |PRI       |                   |auto_increment|
|trans_id   |bigint(21)  |NO         |          |                   |              |
|amount     |bigint(21)  |NO         |          |                   |              |
|game_ref   |varchar(60) |NO         |MUL       |                   |              |
|user_id    |bigint(21)  |NO         |MUL       |                   |              |
|created_at |timestamp   |NO         |MUL       |current_timestamp()|              |
|mg_id      |varchar(100)|NO         |UNI       |                   |              |
|balance    |bigint(21)  |NO         |          |                   |              |
|bonus_bet  |tinyint(1)  |NO         |MUL       |0                  |              |
|op_fee     |float       |NO         |          |0                  |              |
|jp_contrib |float       |NO         |          |0                  |              |
|currency   |varchar(3)  |NO         |MUL       |EUR                |              |
|device_type|tinyint(1)  |NO         |MUL       |0                  |              |
|loyalty    |float       |NO         |          |0                  |              |


**trans_id**: AKA "round id", typically an increasing number that increases with 1 on every wager in each game session, is being sent by the GP.

**amount**: the wager amount in cents.

**game_ref**: this is micro_games.ext_game_name, note that this column is prefixed in our database to achieve uniqueness since several GPs might have the same id for the same game.

**mg_id**: the unique id of the wager / bet in the GP database, this is used to achieve idempotency on our side.

**balance**: the player balance (users.cash_balance) after the bet.

**bonus_bet**: 0 Normal bet; 1 if the bet was made with bonus money; 3 if the bet is made with FRB in e.g. MicroGames and QSpin.

**op_fee**: the GP license fee on the bet.

**jp_contrib**: the GP jackpot contribution "fee" on the bet.

**currency**: iso3 currency code.

**device_type**: this is micro_games.device_type_num

**loyalty**: the cashback / loyalty the player will get back on the bet.