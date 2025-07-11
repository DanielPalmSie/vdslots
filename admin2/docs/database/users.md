# DB Table: users
The main table storing player information.

In addition to being sharded, this table also exists (but with stale information) on the master, when people sign up a line is generated in the master table in order to generate a unique id that is then used to insert into the correct shard. This is a one time event, after the user has been inserted into the shard the shard database is queried for user information from then on.

**This table is sharded.**

|COLUMN_NAME   |COLUMN_TYPE |IS_NULLABLE|COLUMN_KEY|COLUMN_DEFAULT     |EXTRA         |
|--------------|------------|-----------|----------|-------------------|--------------|
|id            |bigint(21)  |NO         |PRI       |                   |auto_increment|
|email         |varchar(250)|NO         |MUL       |                   |              |
|mobile        |varchar(100)|NO         |MUL       |                   |              |
|country       |varchar(5)  |NO         |MUL       |                   |              |
|last_login    |timestamp   |NO         |MUL       |current_timestamp()|              |
|newsletter    |tinyint(1)  |NO         |MUL       |1                  |              |
|sex           |varchar(10) |NO         |MUL       |                   |              |
|lastname      |varchar(255)|NO         |MUL       |                   |              |
|firstname     |varchar(255)|NO         |MUL       |                   |              |
|address       |varchar(255)|NO         |MUL       |                   |              |
|city          |varchar(255)|NO         |MUL       |                   |              |
|zipcode       |varchar(20) |NO         |MUL       |                   |              |
|dob           |date        |NO         |          |                   |              |
|preferred_lang|varchar(5)  |NO         |MUL       |                   |              |
|username      |varchar(255)|NO         |UNI       |                   |              |
|password      |varchar(255)|NO         |          |                   |              |
|bonus_code    |varchar(100)|NO         |MUL       |                   |              |
|register_date |date        |NO         |MUL       |                   |              |
|cash_balance  |bigint(21)  |NO         |          |0                  |              |
|bust_treshold |bigint(21)  |NO         |          |0                  |              |
|reg_ip        |varchar(55) |NO         |MUL       |                   |              |
|active        |tinyint(1)  |YES        |MUL       |1                  |              |
|verified_phone|tinyint(1)  |NO         |MUL       |0                  |              |
|friend        |varchar(50) |NO         |          |                   |              |
|alias         |varchar(255)|NO         |MUL       |                   |              |
|last_logout   |timestamp   |NO         |          |0000-00-00 00:00:00|              |
|cur_ip        |varchar(55) |NO         |MUL       |                   |              |
|logged_in     |tinyint(12) |NO         |          |0                  |              |
|currency      |varchar(3)  |NO         |MUL       |EUR                |              |
|affe_id       |bigint(21)  |NO         |MUL       |0                  |              |
|nid           |varchar(55) |NO         |MUL       |                   |              |

**email**: email address of player.

**mobile**: mobile phone number of player without leading zeroes in country codes or leading zeroes in area codes, Swedish example: 4670123456.

**country**: ISO2 country code, example: SE

**last_login**: gets updated everytime a player logs in with the current stamp.

**newsletter**: 0 / 1, if 0 we can't send any newsletters / CRM campaigns to the player.

**sex**: Male / Female

**lastname**: surname of player.

**firstname**: name of player.

**address**: address of player.

**city**: city of player.

**zipcode**: zip code of player.

**dob**: date of birth of player in Y-m-d format.

**preferred_lang**: ISO2 code of the language, example: sv

**username**: unique identifying string for the player.

**password**: hashed password of player.

**bonus_code**: the bonus / campaign code the player came via, this column is used in order to connect the player to an affiliate.

**register_date**: Y-m-d of the registration date.

**cash_balance**: the player's cash balance, must always be updated via cash_balance = cash_balance + X, never saved directly. Stored in cents, all money numbers everywhere in the database are in cents unless explicitly stated otherwise.

**bust_treshold**: legacy, not used.

**reg_ip**: player IP used during registration.

**active**: 0 / 1, if 0 it means that the player is blocked and can not log in, we can't send any newsletters / CRM campaigns to the player.

**verified_phone**: 0 / 1, will be 1 if mobile phone was verified via an SMS, people need to verify either email or mobile phone during registration.

**friend**: legacy, not used.

**alias**: used in BoS to display a unique handle.

**last_logout**: date of last logout.

**cur_ip**: currently used IP by player.

**logged_in**: 0 / 1, is 1 if player is currently logged in.

**currency**: ISO3 currency code, should not change during the lifetime of an account, example: SEK

**affe_id**: legacy, not used.

**nid**: National Identity number of the user, if applicable.