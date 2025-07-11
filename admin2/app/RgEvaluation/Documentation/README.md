# RG evaluation flow:

## Entrypoint

Currently, we have two-steps evaluation process. But fore some flags it can be only one step.
We have next steps and their handlers:

- `started` - handled by `RgEvaluationFirstIterationCommand`
- `self-assessment` - handled by `RgEvaluationSecondIterationCommand`
- `manual-review` - we don't have any automation handlers for this step since this step handled manually.

```php
php ./console rg:evaluation-first-iteration {interaction_started}
```
Fetches all NEW processes from users_rg_evaluation table with step 'started' and 'processed' = 0 that were added 3 days ago from `now` or
`interaction_started` if is set.

```php
php ./console rg:evaluation-second-iteration {interaction_started}
```
Fetches all NEW processes from `users_rg_evaluation` table with step 'self-assessment' and 'processed' = 0 that were added 6 days ago from `now`
or `interaction_started` if is set.

These commands are no longer responsible for the logical part. Part of their responsibility is to initiate evaluation processes
when they are due. After fetching an entry it creates a trigger instance and initiates evaluation process.

```php
$triggerInstance = $this->getTriggerInstance($userRgEvaluation);
$triggerInstance->evaluate();
```

## Evaluation Logic

### In general, the schema of evaluation logic looks like this
[FLOW](rg-evaluation-flow.png)

### UML diagram
[UML](rg-evaluation-uml-compact.png)
or you can follow the [WEB version](https://viewer.diagrams.net/?tags=%7B%7D&lightbox=1&highlight=0000ff&edit=_blank&layers=1&nav=1&title=rg-evaluation-flow.drawio#R%3Cmxfile%3E%3Cdiagram%20id%3D%22C5RBs43oDa-KdzZeNtuy%22%20name%3D%22Page-1%22%3E7V1bc6O4Ev41qdrz4BRCXB%2BTTJLdPZPdOcnMzs4jsWWbDUYewLnsrz8SCGwkOSa2hWxM1VQGhCyEur9W96dGnMGr2ettEsynd3iEojPTGL2ewU9npglNYJP%2FaMlbUQIMyytKJkk4YmXLgofwX1RWZKWLcITSWsUM4ygL5%2FXCIY5jNMxqZUGS4Jd6tTGO6nedBxMkFDwMg0gs%2FR6OsmlR6pnusvxXFE6m5Z2B4xdXZkFZmT1JOg1G%2BGWlCF6fwasE46w4mr1eoYiOXjku3397%2Bx59fnJuf%2F9f%2BjP4dvnfr3%2F8NSgau%2FnIT6pHSFCc7bdps2j6OYgWbLzYs2Zv5QAmeBGPEG3EOIOX02wWkUNADv9BWfbGBB4sMkyKcJJN8QTHQfQZ4zmrN8ZxxqoBeo7i0QUVLDl%2FjPDwqSi6CaOI3YOcsfoeOUuzBD9VsqMNVIKglaPgEUWXwfBpknf0Ckc4IZdiHCPa1IgoA3uWZeeul6XkZlny9jdt7NwuT39UHSEnn15rZ2%2FsrKFMmOxSvEiG6J16kEEjSCaItXf94P31fQ6McDyf%2Fkue5J%2F05guTsUGfa0W%2FmcRvEZ4h0klSIUFRkIXPdRAEDEuTql710y84JM9hGgz4ldKXsPeNehNFR9mvllpHDla6sSzKdfEDegkFvUT0mDwSjulv4zALg4xopWk8ksdwIjJml48JOZrQI2JMUkzES83KbBbEo%2FQdtaYq9TINM%2FQwD3IZvRBTWFf1VRUmQ3Y5iYI0ZYqwQT8%2FpifPKMnQ67uSLa9CuyYhmwnoZWnkgMvKpisGzjHW60JNiu%2BITKqZQBjizeCr2xap9ZBYmRXBSNGwHSzfg9tGWJqtoND0lKHwvadfQeFVggjsSNn97d85DtMsiIe0YJzgmQjE%2B8n1Cm5vvibhZIISgsYb0sD5fDonFYMZRVv8mM4rgW6L0zUgaqoQ68EG6mADjoi2yuNpBW2wi2gzG6LNUoI2j0ObbbaLNvuUReooEenAMuoytdx2ZSr615%2FQOIxRLufCko4j4isQNyZI6Rk1k3WLmNf7mgRxGlIrekdsHpFPgufEyr0dh7k03Ybmkp%2Ff9uec%2BDrAtVtsoQ6UVkNQAm9HVO4kM8vqZbaFzFytMhPsXYImiyhIaNdf8CDN0DxdGr0xphd8Ak6bVqi8w0M0ahawakYNehKjBtv0Aasn6xFSuRBNEOL7OiHiCBD5Foc%2FF8Xkj3KE8AC5v3UOExNkhqj7VzJQVHVWQWGrA4WW0OhQQQHchqjYmUjczT9zBVQwIBB3%2BOeCUuyXJbuQzoO4Jt%2BywrDgfi9oryePvwCDoIZ0xwAuYAfA%2Fk%2FekEEJvcE4mIXRW%2FGDyutmbf2OssskCGM6Wd3hGPPXiwZn5EpaoK5qNc1VhLbpn3vzoi7x7kuCesD1ks4YtCnI%2BgitoosrT50zKSQMSHLCsxgEMsrFOBRXN46NvAMFjxpJbygOvHBPDmbpNJjTQxKcDBHlRzeZpWWn%2FlxkEQ2L1Jor0%2BM5U%2FPcFu2VZUvsla0sMnF7e8WHHI383F1ZoN3E5vUGa4PBQtF4EKQpsQQzqhu94drecDmee86ZLiCJPsxWow8gIKAc73mCGum75zE1943ywNGj7xId2hd0%2Baavpmj49C0lce7t%2FcNDQQK%2BCw1SnA%2FoGuVNpnj2uGigtMpmVYdXTsdqGAUAZYSfKWFeMaLKsiBDT7sRPFMlvUOjcDGjsRct%2FJV0jUo0TJ%2FO4I04L0dROE%2BpLpUWYxjhxUjf0ENuycKTrUv5soGHygZed%2FhleVVB7tCcG17l4axxavKzLygJyRCgpCVPp8qG2hiZmTodHVOk9EQBLzN6hjQzIhzWpVMXL3oNs0JUHjv7UTtbComevK2c8CI6VCe2HKKNooXGjqKVL3u5ft0sAAA4uBePICx7iS1ZfEstL4qatqB%2Bf%2FwptTCfaSIYVTZEJvjgMb%2FEZayRf0EUTmKqp0TSVI8uqVEOh0F0wS7MwtEoQpWSsLRE1t5Z5WJtsOXvQEkbkMWIZW9AhuDkkawmD88z9oVki8%2Foax3JvqB%2FP64fjhXKWldGS%2BdtZSgfCHTpQC6GeQRLnxYXYbcR5FeGeDaPUJ5xQIrDPEKr5VSWwa8QX9Qwr2vxjZ%2FRDEn8C2SeLp%2FwsT8hSMzn6fJ2sKk3C6ESQwl9LkPP5A2lYvMGRTqEYXIchBEaSSCZFUk99AjTP1OKyRi9ZmdVdtAhYlGM9wkaJTR6u2gU%2FUTN8LTqUSjYAFAtMShs%2BtoB1Oq6QvF1gJ5qbEo1XgyJgEKis52gGjnDA2RMoy9Zv1PHNEKRIPkU5uZ%2BkWbkHpRXTAlI0nFI%2BUc%2BQ76w%2Bgn6uSA%2BGZse8DifDsLcj6MyPA4yEnA%2BGpSlfbbLRkIx20YcyJ69kir05jR8NTGvzXNONv%2FyWdOY1%2BFjXsdx2nUKxbSWo2Wv4K4p%2BrsBuWevVCJZDQ9dZc1U%2BNuWvTJ5m1AtMbaF5C6xV1BvXv%2FJsVdmuYhWzWja2StLd3h8UIbSaspeWWrYK4tnr1w%2BbUexebNOhr2yPN671I%2FFnt7Yit64wQTVDygaX1RZhl0gOaDJe02SvR1MKCM5fEeRirJs215HTz7bz%2FWFbD%2FZ1iPtZvsBV6T%2FO5juV6X3sZH3ZUnA7RJsxFPR7EgeS75fZUIbvNpg7%2Bhl7ihSkbUSZdyTpnIL1OClejW06ZJurxZHyhSFj7ItgM8rBsDimlIcjwBX5PuOljllgNKHZ5G72hueO8udfgTQakgBAPlQdXtAG0IeYduA9kTW73gJVAYpbYj21tI1XaVQgWFzGmxIVpfb5W2A15Oooh1sZDF3dXDlZs53%2BSRA3mAqt3IilddRHtXlJ6dDwGOTd5X6JEA5GhvhVmsOQNXRnoQ88TxA3xBSkE3pMk6rmYAMHn0qoOnxzpoF9TOVXk9rbTE3NN9VTVEUDHguygHbRsEmHwU7nteyf9glWsvTS2t5Pa2lFtCKXmmF%2FPL2tqwWv2E3cJyWwz2%2FU6SWp5fU8k%2BO1HJ9k3djbdkGmu1G0b7uoPmwDKbfmNXy1XyXABg8reV6bdu5k6G1SFTN5wceACL75KuteA%2F2uZm7IF4E0T16DtFLR9kP15TE1%2B2mCJraydYDmzf8hvMGi0r1%2BV1iHNVvZdrvvbz3reK59CTbkMyrjmReVbeDqSGGb%2F28eopJzcJ3DGxPsqFJyzuYGmI83MGc5kH1zZRyD1OJXWh7D1NDtytzLEnNlQlt4OXsSifuKFLZS1Z7You7uvpTmaAG0t11LWAN98HnNBv8hxg%2BsPjDteRwLane%2F9AQo%2FijXfthcNKH5ibbffVrP9vDedelgDUgFJLGtoXzQFjLbR%2FPYrbH8S7%2BMETpA7SYnNHxxZ8B%2FzKfpXsPRdPwNLu9B2YuGzN4QM1S%2BcDkXwUHvL1UbuREcrCjKz8DaBwcIIGMoNIbmNbD0oNMaGZwbARcU%2BusB0SKpycgTzGhWSAggeFL6PF285nNMtHy5POZB8IL0VD%2F1guk6QazQ09pyXW6wdygJgYeCBtQwY%2FGwMtMaO7uqr1B0CUKC%2BilsEBPYamFrxpGegD5PcjtrTks4QX%2FatmxNUB3isMCejksyUfMu85hWTwLa0m%2BQNl20NyzWKIb0%2Bg7k2p2gR4AnsVyjbbN3OmwWAAeHiJNGY3VNZpj3ZMcSPcON736SBga4fMm1dclVORck9ME09Fe2kHyqNM7PKJO2PX%2FAQ%3D%3D%3C%2Fdiagram%3E%3C%2Fmxfile%3E)

### Terminology
**Step** - reflects all possible values from column 'step' in `users_rg_evaluation` table
- `started` - initial step of RG process
- `self-assessment` - second step of RG evaluation process
- `manual-review` - end of RG evaluation process

**State** - You can consider states as short individual procedures that goes in the defined sequence within a particular evaluation `step`.
There are fourth 'Basic general states' of entire RG evaluation process.
Sequence combination depends on specific flags and steps.

**Basic general states:**
- `CheckUsersGRSState` - Mostly used in the beginning of each step of RG evaluation process.
   If user's RG GRS between 'Social Gambler' and 'Low Risk' we quit the RG evaluation process and add a comment to user's profile.
   If user's RG GRS between 'Medium Risk' and 'High Risk' we continue the RG evaluation process.
- `CheckActivityState` - One of the middle states of RG evaluation processes. Checks user's activities based on specific flag conditions
   Adds a user profile comment about successful or failure result of check. Completes the RG evaluation if the user has reduced negative activity
- `ForceSelfAssessmentState` - Transition state between `'started'` step and `'self-assessment'` step.
   Means we have completed first step of RG evaluation (started) and move an uses to the next evaluation step.
   We do nothing in this state except fact of transition to the next level of RG evaluation (self-assessment)
- `TriggerManualReviewState` - Mostly used in the end of RG evaluation process.
   Next action are applied on this state:
    - end RG evaluation process (by adding final step 'manual-review')
    - trigger RG69 flag 'Manual Review'
    - log action with tag 'intervention' about new flag triggering
 
**And two dynamic action sub-states (manual adjustment by an admin). See DB config `RGX-evaluation-last-step-action-state`**
- `NoActionState` - This is predefined blank state for an Admin for cases when no actions required for an abstract ActionState.
   An abstract ActionState means any action state for any step. Example:
   dynamic FinalActionState can trigger TriggerManualReviewState OR NoActionState.
   The exact state is defined in DB config `RGX-evaluation-last-step-action-state`
- `FinalActionState` - The final state in each evaluation step.
  It's a dynamic action that can be changed from Admin panel through `RGX-evaluation-last-step-action-state` config.
  Default action is `TriggerManualReviewState`. Also, can be set `NoActionState` in case of no action needed.

### How to add new trigger?

1. Create new trigger class `app/RgEvaluation/Triggers/RGX.php`
2. Override the default value of `$stateTransitionMap` in case if you gonna use one-step evaluation flow.
   Actually you can override this variable in case if you would like to provide your own state sequence per each step.
   This is default value of $stateTransitionMap that applicable for most common cases automatically until you override it:
   ```php
   protected array $stateTransitionMap = [
           'started' => [
               'CheckUsersGRSState' => 'CheckActivityState',
               'CheckActivityState' => 'ForceSelfAssessmentState',
           ],
           'self-assessment' => [
               'CheckUsersGRSState' => 'CheckActivityState',
               'CheckActivityState' => 'FinalActionState',
           ],
       ];
   ```
   It means we have next state transitions for two-step evaluation process.

   Step "started" has: `CheckUsersGRSState` -> `CheckActivityState` -> `ForceSelfAssessmentState`

   Step "self-assessment" has: `CheckUsersGRSState` -> `CheckActivityState` -> `FinalActionState` --> `TriggerManualReviewState` || `NoActionState`

   *Of course this example describes a full transition flow. But it may be shorter if we catch positive result in states CheckUsersGRSState and CheckActivityState.

3. Create new class for checking activities in `app/RgEvaluation/ActivityChecks/{NameOfCheck}.php` and implement the logic in method `evaluate()`.
   Note1: you can have access to RgEvaluate instance and User instance from this class.
   Note2: before creating new class please check existing implementations, we may have already implemented it, and you can reuse it.
4. Implement checking logic in `NameOfCheck::evaluate()`. This method must return only `EvaluationResult` instance. Before returning the instance make sure you set:
    ```php
    $evaluationResult = $this->getEvaluationResult();
    $evaluationResult->setResult(true|false);
    $evaluationResult->setEvaluationVariables([]); //those variables will be used in comment messages
    ```
    Example: you set
    ```php
    $evaluationResult->setEvaluationVariables(['variable_one' => 1, 'variable_two' => 2]);
    ```
    And RGX has comments that use those variables name like template for context replacement. Example: "User has {{variable_one}} now, but had {{variable_two}} 3 days ago"
5. Once you have the class you should add it into `\App\RgEvaluation\Triggers\RGX::getActivityCheck()` as returned value. Example:
    ```php
    public function getActivityCheck(): ActivityCheckInterface
        {
            return new SelfLockAgainCheck($this->getRgEvaluation()->user, $this);
        }
    ```
6. add onSuccess and onFailure comments into `config/rg-evaluation.php` for new 'RGX' trigger. Pay attention that comments must be added for each 'step' separately.
7. We have two configs to enable/disable "RG Evaluation" feature globally and per flag per jurisdiction:
   - to enable/disable rg evaluation globally use: set RG config `rg-evaluation-state` "on" in admin2. Default value "off"
   - to enable/disable rg evaluation per flag per jurisdiction change config `{trigger_name}-evaluation-in-jurisdictions`
     By default no value is set. To enable this flag for particular jurisdiction you just need to add the jurisdiction to the list.  
8. Done. Your flag is ready to use.

### Full roadmap to add trigger into rg evaluation process looks like this:
- add trigger into `phive/config-new/licenses/{lic_name}.php`  config name `rg-trigger-popups`. This configuration stores the triggers for which we show a popup.
- 'add'/'switch on' db config `{trigger_name}-evaluation-in-jurisdictions` this config is enable/disable rg evaluation process
- add localized string for popup `{trigger_name}.rg.info.description.html` (popup context)
- add localized string for system comment `{trigger_name}.user.comment`
- implement generation list of dynamic variables for popup context (if needed) because some popups may have extra information such as 'deposit amount' etc.
  See examples here:`phive/modules/RgEvaluation/Factory/RG72DynamicVariablesSupplier.php`
  `phive/modules/RgEvaluation/Factory/RG72DataSupplier.php`
  Basically you need to implement the factory `RG72DynamicVariablesSupplier` and data supplier `RG72DataSupplier`
  and register new factory in `\RgEvaluation\Factory\DynamicVariablesSupplierResolver::__construct()`
- created new class in Admin2 repo `app/RgEvaluation/Triggers/{TriggerName}.php`
- implement check activity in Admin2 repo `app/RgEvaluation/ActivityChecks/{NameOfCheck}Check.php` // check existing implementations. It may be already implemented.

### Full roadmap just to show the RG popup (without rg evaluation):
- add trigger into `phive/config-new/licenses/{lic_name}.php`  config name `rg-trigger-popups`. This configuration stores the triggers for which we show a popup.
- add localized string for popup `{trigger_name}.rg.info.description.html`  (popup context)
- add localized string for system comment `{trigger_name}.user.comment`
- implement generation list of dynamic variables for popup context (if needed) because some popups may have extra information such as 'deposit amount' etc.
  See examples here:`phive/modules/RgEvaluation/Factory/RG72DynamicVariablesSupplier.php`
  `phive/modules/RgEvaluation/Factory/RG72DataSupplier.php`
  Basically you need to implement the factory `RG72DynamicVariablesSupplier` and data supplier `RG72DataSupplier`
  and register new factory in `\RgEvaluation\Factory\DynamicVariablesSupplierResolver::__construct()`

#### Notes:
- popup will not be shown if trigger has not been added into `rg-trigger-popups` lic config
- rg evaluation will not start if `{trigger_name}-evaluation-in-jurisdictions` db config does not exist or doesn't have a jurisdiction.
- we shouldn't add a config `{trigger_name}-evaluation-in-jurisdictions` db config until we implemented item 6 and 7 on admin2, otherwise it may cause errors upon evaluating process since important classes not exist on Admin2.
- db config `RGX-evaluation-last-step-action-state` may impact on the evaluation behavior by dynamic changing an action that should be applied in the end of first||second evaluation step