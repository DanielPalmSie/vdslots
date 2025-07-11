# Videoslots Tests

[Codeception](https://codeception.com/) and [PhpUnit](https://phpunit.de/) tests.

See the wiki [Unit Tests](https://wiki.videoslots.com/index.php?title=Unit_Tests) for an introduction.

### Installation

- Clone the repository into `videoslots` at the same level as `diamondbet` and `phive`.

``` bash
# In local
cd videoslots
git clone git@gitlab.videoslots.com:app-tests/app-tests.git
```

### Configuration

- Copy `env.example.yml` to `env.yml` and set your personal configuration in `env.yml`

Do not commit `env.yml`!!!

### Git branch

- Example: you created a new branch on Phive `ch25391-tom-horn-integration` and are implementing this feature branch.
- Create a corresponding branch in `app-tests` and implement the tests for your Phive feature branch.

``` bash
$ cd app-tests
$ git switch main
$ git pull
$ git switch -C ch25391-tom-horn-integration
$ git commit -am "Tom Horn tests [ch25391]"
```
- Implement your tests, execute them and fix your feature branch if necessary.
- When your tests are ready, commit, push and create a merge request for your app-tests branch.
- In most cases `phive`, `diamondbet` and `app-tests` need to be on matching branches for your tests to work.
- `app-tests` branches should only be merged to `main` after the corresponding feature branch in `phive` has been merged to `main`.

### Videoslots configuration

- Some tests require complicated configuration in order to succeed and should therefore be skipped by default.
- For example `/app-tests/tests/api/Phive/Modules/Micro/Thunderkick/FreeSpinBetAndWinCest.php` tests free spins and requires that a free spin reward exists for the player/game, is activated, not expired and has remaining free spins.
- Typically you need to create a trophy reward for the user in `admin2` then activate it from the user's profile page before running the test.
- All test files which require complicated configuration must include clear instructions at the top of the file.
- All test files which require complicated configuration must be included in `app-tests/tests/api.suite.yml::groups::complicated` (or in `unit.suite.yml` if it is a unit test, etc).
- To execute all tests except the complicated ones, do: `$ php vendor/bin/codecept run --skip-group=complicated api`
- To execute only the complicated tests, do: `$ php vendor/bin/codecept run --group=complicated api`
- See [Group files](https://codeception.com/docs/07-AdvancedUsage#Group-Files) for more information.

### Execute Tests

``` bash
# In LXC container
$ cd app-tests

# Execute all api tests
$ php vendor/bin/codecept run --skip-group=complicated api

# Execute all unit tests
$ php vendor/bin/codecept run --skip-group=complicated unit

# Execute all tests under a folder
$ php vendor/bin/codecept run --skip-group=complicated api Phive/Modules/Micro/Thunderkick

# Execute a single test file
$ php vendor/bin/codecept run api Phive/Modules/Micro/Thunderkick/BetCest

# Execute a single test file method
$ php vendor/bin/codecept run api Phive/Modules/Micro/Thunderkick/BetCest:validBet
```
