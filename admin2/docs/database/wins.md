# DB Table: wins

This is the table that is logging all wins, as opposed to deposits and withdrawals no transactions are generated in cash_transactions so this is not an auxiliary table, it's a bona fide log in its own right.

All transactions logged here are affecting the player balance (users.cash_balance).

**This table is sharded.**

|COLUMN_NAME|COLUMN_TYPE |IS_NULLABLE|COLUMN_KEY|COLUMN_DEFAULT     |EXTRA         |
|-----------|------------|-----------|----------|-------------------|--------------|
|id         |bigint(21)  |NO         |PRI       |                   |auto_increment|
|trans_id   |bigint(21)  |NO         |          |                   |              |
|game_ref   |varchar(60) |NO         |MUL       |                   |              |
|user_id    |bigint(21)  |NO         |MUL       |                   |              |
|amount     |bigint(12)  |NO         |MUL       |0                  |              |
|created_at |timestamp   |NO         |MUL       |current_timestamp()|              |
|mg_id      |varchar(100)|NO         |UNI       |                   |              |
|balance    |bigint(21)  |NO         |          |                   |              |
|award_type |int(5)      |NO         |MUL       |                   |              |
|bonus_bet  |tinyint(1)  |NO         |MUL       |0                  |              |
|op_fee     |float       |NO         |          |0                  |              |
|currency   |varchar(3)  |NO         |MUL       |EUR                |              |
|device_type|tinyint(1)  |NO         |MUL       |0                  |              |

**award_type**: Type 4 is used when a jackpot is won. When doing MicroGaming / QuickFire rollbacks we need to offset a rolled back bet by way of creating a win, and a normal win has type 2 and a rollback type 7. 3 for freespin.

**trans_id**: AKA "round id", typically an increasing number that increases with 1 on every win in each game session, is being sent by the GP.

**amount**: the win amount in cents.

**game_ref**: this is micro_games.ext_game_name, note that this column is prefixed in our database to achieve uniqueness since several GPs might have the same id for the same game.

**mg_id**: the unique id of the win in the GP database, this is used to achieve idempotency on our side.

**balance**: the player balance (users.cash_balance) before the win. The reason we store the balance before the win is that we do not insert the win and update the balance in a single MySQL transaction, therefore in order to achieve idempotency even in severe situations (extreme database pressure etc) we insert first before we credit the player balance. And since we insert the win before we credit we do not have access to the updated balance at the time of insert.

**bonus_bet**: 0 / 1 / 3 - it is 1 if the bet was made with bonus money (not really used for anything atm), and it's 3 if the winning is made from a FREESPIN offer.

**op_fee**: the GP license fee on the win, this is a negative number as GP fees are on the GGR.

**currency**: iso3 currency code.

**device_type**: this is micro_games.device_type_num