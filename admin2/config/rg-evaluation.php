<?php

return [
    'rg-evaluation-comments' => [
        /**
         * Reflects all possible values from column 'steps' in users_rg_evaluation table
         * - started - initial step of RG process
         * - self-assessment - second step of RG evaluation process
         * - manual-review - end of RG evaluation process
         */
        'steps' => [
            'started' => [
                'states' => [
                    /**
                     * There are fourth possible states of entire RG evaluation process.
                     * Sequence combination depends on specific flags and steps
                     * Possible states:
                     * - CheckUsersGRSState
                     * - CheckActivityState
                     * - ForceSelfAssessmentState
                     * - TriggerManualReviewState
                     */
                    'CheckUsersGRSState' => [
                        'default' => [
                            'onSuccess' => "The interaction was successful.
                                        The automated evaluation process has been completed;
                                        the customer is currently at risk level {{rating_tag}} and has not raised their risk level.",
                            'onFailure' => "",
                        ],
                        'triggers' => [
                            // You can override here the default comment with specific requirements per trigger
//                            'RGx' => [
//                                'onSuccess' => "RGX evaluation: The interaction was successful.",
//                                'onFailure' => "RGX evaluation: The interaction was failure.",
//                            ],
                        ],
                    ],
                    'CheckActivityState' => [
                        // By default, this state doesn't have common comments. Each trigger has unique comment
                        'default' => [
                            'onSuccess' => "",
                            'onFailure' => "",
                        ],
                        'triggers' => [
                            'RG5' => [
                                'onSuccess' => "The popup was successful. Within a {{evaluation_interval}}-day time period,
                                                the customer did not lock their account again. No further actions are required.",
                                'onFailure' => "The interaction did not prevent the customer from locking their account again within a {{evaluation_interval}}-day time period.
                                                The system has signaled that the account needs to be manually reviewed by an RG analyst.
                                                The automated process has been completed.",
                            ],
                            'RG6' => [
                                'onSuccess' => "The popup was successful. Within a {{evaluation_interval}}-day time period,
                                                the customer did not lock their account again. No further actions are required.",
                                'onFailure' => "The interaction did not prevent the customer from
                                                locking their account again within a {{evaluation_interval}}-day time period.
                                                The system has signaled that the account needs to be manually reviewed by an RG analyst.
                                                The automated process has been completed.",
                            ],
                            'RG8' => [
                                'onSuccess' => "The popup was successful. The customer has spent less
                                                time playing in the last {{evaluation_interval}} days than the average of {{hours_threshold}} hours per day.
                                                No further actions are required.",
                                'onFailure' => "The last {{evaluation_interval}} days customer has spent more time playing than average {{hours_threshold}} hours per day.
                                                Action taken, forced self-assessment test. New evaluation will be completed in {{nex_evaluation_in_days}} days time.",
                            ],
                            'RG10' => [
                                'onSuccess' => "The popup was as success. The average deposit amount per transaction per day the last {{evaluation_interval}} days has decreased or stabilized from the day the flag triggered.
                                                The flag triggered on a deposit amount per transaction of {{avg_deposit_amount_per_transaction_before}} and the average last {{evaluation_interval}} days is {{avg_deposit_amount_per_transaction_after}}, the change is {{percentage_diff}}%. No further actions required.",
                                'onFailure' => "The average deposit amount per transaction per day have not stabilized since the flag triggered.
                                                The flag triggered on a deposit amount per transaction of {{avg_deposit_amount_per_transaction_before}} and the average last {{evaluation_interval}} days is {{avg_deposit_amount_per_transaction_after}}, the change is {{percentage_diff}}%.
                                                Action taken, forced self-assessment test. New evaluation will be completed in {{nex_evaluation_in_days}} days time.",
                            ],
                            'RG11' => [
                                'onSuccess' => "The popup was as success. The average transactions amount per day the last {{evaluation_interval}} days has decreased or stabilized from the day the flag triggered.
                                                The flag triggered on a deposit transactions of {{number_of_deposit_transactions_before_evaluation}} and the average last {{evaluation_interval}} days is {{number_of_deposit_transactions_after_evaluation}}, the change is {{percentage_diff}}%.
                                                No further actions required.",
                                'onFailure' => "The average deposit transactions per day have not stabilized since the flag triggered.
                                                The flag triggered on a deposit transactions of {{number_of_deposit_transactions_before_evaluation}} and the average last {{evaluation_interval}} days is {{number_of_deposit_transactions_after_evaluation}}, the change is {{percentage_diff}}%.
                                                Action taken, forced self-assessment test. New evaluation will be completed in {{nex_evaluation_in_days}} days time.",
                            ],
                            'RG12' => [
                                'onSuccess' => "The popup was as success. The average  total deposit amount per day the last {{evaluation_interval}} days has decreased or stabilized from the day the flag triggered.
                                                The flag triggered on a total deposit amount of '{{X}}' and the average last {{evaluation_interval}} days is '{{Y}}', the change is {{percentage_diff}}%.
                                                No further actions required.",
                                'onFailure' => "The average total deposit amount per day have not stabilized since the flag triggered.
                                                The flag triggered on a total deposit amount of '{{X}}' and the average last {{evaluation_interval}} days is '{{Y}}', the change is {{percentage_diff}}%.
                                                Action taken, forced self-assessment test. New evaluation will be completed in {{nex_evaluation_in_days}} days time.",
                            ],
                            'RG13' => [
                                'onSuccess' => "The popup was as success. The average deposit amount per transaction per day the last {{evaluation_interval}} days has decreased or stabilized from the day the flag triggered.
                                                The flag triggered on a deposit amount per transaction of {{avg_deposit_amount_per_transaction_before}} and the average last {{evaluation_interval}} days is {{avg_deposit_amount_per_transaction_after}}, the change is {{percentage_diff}}%. No further actions required.",
                                'onFailure' => "The average deposit amount per transaction per day have not stabilized since the flag triggered.
                                                The flag triggered on a deposit amount per transaction of {{avg_deposit_amount_per_transaction_before}} and the average last {{evaluation_interval}} days is {{avg_deposit_amount_per_transaction_after}}, the change is {{percentage_diff}}%.
                                                Action taken, forced self-assessment test. New evaluation will be completed in {{nex_evaluation_in_days}} days time.",
                            ],
                            'RG14' => [
                                'onSuccess' => "The popup was as success. Game play has not increased from the day the flag triggered.
                                                Average bets last {{evaluation_interval}} days per day was {{current_average_bets}} and on the day of win it was {{initial_average_bets}}, change is {{percentage_diff}}%.
                                                No further actions required.",
                                'onFailure' => "Game play have increased the last {{evaluation_interval}} days compared to the day the player won big.
                                                Current average bet per day the last {{evaluation_interval}} days {{current_average_bets}} and on the win day it was {{initial_average_bets}}, the change is {{percentage_diff}}%.
                                                Action taken, forced self-assessment test. New evaluation will be completed in {{nex_evaluation_in_days}} days time.",
                            ],
                            'RG16' => [
                                'onSuccess' => "The popup was successful. Within a {{evaluation_interval}}-day time period, the customer did not lock their account again. No further actions are required.",
                                'onFailure' => "The interaction did not prevent the customer from
                                                locking their account again within a {{evaluation_interval}}-day time period.
                                                The system has signaled that the account needs to be manually reviewed by an RG analyst.
                                                The automated process has been completed.",
                            ],
                            'RG19' => [
                                'onSuccess' => "The popup was as success. The average loss per day the last {{evaluation_interval}} days has decreased or stabilized from the day the flag triggered.
                                                The day the flag triggered the loss was {{initial_average_loss}} and the average last {{evaluation_interval}} days is {{current_average_loss}}, the change is {{percentage_diff}}%.
                                                No further actions required.",
                                'onFailure' => "The average loss per day have not stabilized since the flag triggered.
                                                The day the flag triggered the loss was {{initial_average_loss}} and the average last {{evaluation_interval}} days is {{current_average_loss}}, the change is {{percentage_diff}}%.
                                                Action taken, forced self-assessment test. New evaluation will be completed in {{nex_evaluation_in_days}} days time.",
                            ],
                            'RG20' => [
                                'onSuccess' => "The popup was as success. The average loss per day the last {{evaluation_interval}} days has decreased or stabilized from the day the flag triggered.
                                                The day the flag triggered the loss was {{initial_average_loss}} and the average last {{evaluation_interval}} days is {{current_average_loss}}, the change is {{percentage_diff}}%.
                                                No further actions required.",
                                'onFailure' => "The average loss per day have not stabilized since the flag triggered.
                                                The day the flag triggered the loss was {{initial_average_loss}} and the average last {{evaluation_interval}} days is {{current_average_loss}}, the change is {{percentage_diff}}%.
                                                Action taken, forced self-assessment test. New evaluation will be completed in {{nex_evaluation_in_days}} days time.",
                            ],
                            'RG21' => [
                                'onSuccess' => "{{evaluation_interval}} days after RG21, customer have added a limit again, so e-mail was successful. No further actions required.",
                                'onFailure' => "{{evaluation_interval}} days after RG21, customer don’t have any limit in place. Action taken, forced self-assessment test.
                                                New evaluation will be completed in {{nex_evaluation_in_days}} days time.",
                            ],
                            'RG28' => [
                                'onSuccess' => "The popup was as success. Game play has not increased from the day the flag triggered.
                                                Average total bets last {{evaluation_interval}} days per day was {{current_average_bets}} and on the day the flag triggered it was {{initial_average_bets}}, change is {{percentage_diff}}%.
                                                No further actions required.",
                                'onFailure' => "Game play have increased the last {{evaluation_interval}} days compared to the day the flag triggered.
                                                Current average total bets per day the last {{evaluation_interval}} days is {{current_average_bets}} and the day the flag triggered it was {{initial_average_bets}}, the change is {{percentage_diff}}%.
                                                Action taken, forced self-assessment test. New evaluation will be completed in {{nex_evaluation_in_days}} days time.",
                            ],
                            'RG29' => [
                                'onSuccess' => "The popup was as success. The average total unique bet transactions per day the last {{evaluation_interval}} days has decreased or stabilized from the day the flag triggered.
                                                The day the flag triggered the total unique bet transaction was '{{X}}' and the average last {{evaluation_interval}} days is '{{Y}}', the change is {{percentage_diff}}%.
                                                No further actions required.",
                                'onFailure' => "The average total unique bet transactions per day have not stabilized since the flag triggered.
                                                The day the flag triggered the total unique bet transactions was '{{X}}' and the average last {{evaluation_interval}} days is '{{Y}}', the change is {{percentage_diff}}%.
                                                Action taken, forced self-assessment test. New evaluation will be completed in {{nex_evaluation_in_days}} days time.",
                            ],
                            'RG30' => [
                                'onSuccess' => "The popup was as success. Game play has not increased from the day the flag triggered.
                                                Average total bets last {{evaluation_interval}} days per day was {{current_average_bets}} and on the day the flag triggered it was {{initial_average_bets}}, change is {{percentage_diff}}%.
                                                No further actions required.",
                                'onFailure' => "Game play have increased the last {{evaluation_interval}} days compared to the day the flag triggered.
                                                Current average total bets per day the last {{evaluation_interval}} days is {{current_average_bets}} and the day the flag triggered it was {{initial_average_bets}}, the change is {{percentage_diff}}%.
                                                Action taken, forced self-assessment test. New evaluation will be completed in {{nex_evaluation_in_days}} days time.",
                            ],
                            'RG31' => [
                                'onSuccess' => "The popup was as success. Game play has not increased from the day the flag triggered.
                                                Average total bets last {{evaluation_interval}} days per day was {{current_average_bets}} and on the day the flag triggered it was {{initial_average_bets}}, change is {{percentage_diff}}%.
                                                No further actions required.",
                                'onFailure' => "Game play have increased the last {{evaluation_interval}} days compared to the day the flag triggered.
                                                Current average total bets per day the last {{evaluation_interval}} days is {{current_average_bets}} and the day the flag triggered it was {{initial_average_bets}}, the change is {{percentage_diff}}%.
                                                Action taken, forced self-assessment test. New evaluation will be completed in {{nex_evaluation_in_days}} days time.",
                            ],
                            'RG32' => [
                                'onSuccess' => "The popup was as success. The average session time per day the last {{evaluation_interval}} days has decreased or stabilized from the day the flag triggered.
                                                The day the flag triggered the average session time was '{{X}}' and the average session time the last {{evaluation_interval}} days is '{{Y}}', the change is {{percentage_diff}}%.
                                                No further actions required.",
                                'onFailure' => "The average session time per day have not stabilized since the flag triggered.
                                                The day the flag triggered the average session time was '{{X}}' and the average last {{evaluation_interval}} days is '{{Y}}', the change is {{percentage_diff}}%.
                                                Action taken, forced self-assessment test. New evaluation will be completed in {{nex_evaluation_in_days}} days time.",
                            ],
                            'RG33' => [
                                'onSuccess' => "The popup was as success. The average time spent in a unique game session per day the last {{evaluation_interval}} days has decreased or stabilized from the day the flag triggered.
                                                The day the flag triggered the average time spent in a unique game session was '{{X}}' and the average time spent in a unique game session the last {{evaluation_interval}} days is '{{Y}}', the change is {{percentage_diff}}%.
                                                No further actions required.",
                                'onFailure' => "The average time spent in a unique game session per day have not stabilized since the flag triggered.
                                                The day the flag triggered the average time spent in a unique game session per day was '{{X}}' and the average last {{evaluation_interval}} days is '{{Y}}',
                                                the change is {{percentage_diff}}%. Action taken, forced self-assessment test. New evaluation will be completed in {{nex_evaluation_in_days}} days time.",
                            ],
                            'RG34' => [
                                'onSuccess' => "The popup was as success. The average unique game session per day the last {{evaluation_interval}} days has decreased or stabilized from the day the flag triggered.
                                                The day the flag triggered the amount of unique game session was '{{X}}' and the average amount of unique game sessions the last {{evaluation_interval}} days is '{{Y}}', the change is {{percentage_diff}}%.
                                                No further actions required.",
                                'onFailure' => "The average time spent in a unique game session per day have not stabilized since the flag triggered.
                                                The day the flag triggered the average time spent in a unique game session per day was '{{X}}' and the average last {{evaluation_interval}} days is '{{Y}}',
                                                the change is {{percentage_diff}}%. Action taken, forced self-assessment test. New evaluation will be completed in {{nex_evaluation_in_days}} days time.",
                            ],
                            'RG35' => [
                                'onSuccess' => "The popup was as success. The average bet in a unique game session per day the last {{evaluation_interval}} days has decreased or stabilized from the day the flag triggered.
                                                The day the flag triggered the average bet in a unique game session was '{{X}}' and the average bet in a unique game session the last {{evaluation_interval}} days is '{{Y}}',
                                                the change is {{percentage_diff}}%. No further actions required.",
                                'onFailure' => "The average time spent in a unique game session per day have not stabilized since the flag triggered.
                                                The day the flag triggered the average time spent in a unique game session per day was '{{X}}' and the average last {{evaluation_interval}} days is '{{Y}}',
                                                the change is {{percentage_diff}}%. Action taken, forced self-assessment test. New evaluation will be completed in {{nex_evaluation_in_days}} days time.",
                            ],
                            'RG38' => [
                                'onSuccess' => "The popup was as success. The average loss per day the last {{evaluation_interval}} days has decreased or stabilized from the day the flag triggered.
                                                The day the flag triggered the loss was {{initial_average_loss}} and the average last {{evaluation_interval}} days is {{current_average_loss}}, the change is {{percentage_diff}}%. No further actions required.",
                                'onFailure' => "The average loss per day have not stabilized since the flag triggered.
                                                The day the flag triggered the loss was {{initial_average_loss}} and the average last {{evaluation_interval}} days is {{current_average_loss}}, the change is {{percentage_diff}}%.
                                                Action taken, forced self-assessment test. New evaluation will be completed in {{nex_evaluation_in_days}} days time.",
                            ],
                            'RG39' => [
                                'onSuccess' => "The popup was as success. The average loss per day the last {{evaluation_interval}} days has decreased or stabilized from the day the flag triggered.
                                                The day the flag triggered the loss was {{initial_average_loss}} and the average last {{evaluation_interval}} days is {{current_average_loss}}, the change is {{percentage_diff}}%. No further actions required.",
                                'onFailure' => "The average loss per day have not stabilized since the flag triggered.
                                                The day the flag triggered the loss was {{initial_average_loss}} and the average last {{evaluation_interval}} days is {{current_average_loss}}, the change is {{percentage_diff}}%.
                                                Action taken, forced self-assessment test. New evaluation will be completed in {{nex_evaluation_in_days}} days time.",
                            ],
                            'RG59' => [
                                'onSuccess' => "The popup was as success. The total bets during night time has decreased or stabilized from the day the flag triggered.
                                                The bets during night time when flag triggered was '{{X}}' and the average the last {{evaluation_interval}} days was '{{Y}}', the change is {{percentage_diff}}%.
                                                No further actions required.",
                                'onFailure' => "Game play have not decreased within the desired level, its currently at (+ or -) {{X}}%. Action taken, forced self-assessment test.
                                                New evaluation will be completed in {{nex_evaluation_in_days}} days time.",
                            ],
                            'RG68' => [
                                'onSuccess' => "The popup was as success. The average loss per day the last {{evaluation_interval}} days has decreased or stabilized from the day the flag triggered.
                                                The day the flag triggered the loss was {{initial_average_loss}} and the average last {{evaluation_interval}} days is {{current_average_loss}}, the change is {{percentage_diff}}%.
                                                No further actions required.",
                                'onFailure' => "The average loss per day have not stabilized since the flag triggered.
                                                The day the flag triggered the loss was {{initial_average_loss}} and the average last {{evaluation_interval}} days is {{current_average_loss}}, the change is {{percentage_diff}}%.
                                                Action taken, forced self-assessment test. New evaluation will be completed in {{nex_evaluation_in_days}} days time.",
                            ],
                            'RG78' => [
                                'onSuccess' => "The popup was as success. The average loss per day the last {{evaluation_interval}} days has decreased or stabilized from the day the flag triggered.
                                                The day the flag triggered the loss was {{initial_average_loss}} and the average last {{evaluation_interval}} days is {{current_average_loss}}, the change is {{percentage_diff}}%.
                                                No further actions required.",
                                'onFailure' => "The average loss per day have not stabilized since the flag triggered.
                                                The day the flag triggered the loss was {{initial_average_loss}} and the average last {{evaluation_interval}} days is {{current_average_loss}}, the change is {{percentage_diff}}%.
                                                Action taken, forced self-assessment test. New evaluation will be completed in {{nex_evaluation_in_days}} days time.",
                            ],
                            'RG80' => [
                                'onSuccess' => "The popup was as success. The average loss per day the last {{evaluation_interval}} days has decreased or stabilized from the day the flag triggered.
                                                The day the flag triggered the loss was {{initial_average_loss}} and the average last {{evaluation_interval}} days is {{current_average_loss}}, the change is {{percentage_diff}}%.
                                                No further actions required.",
                                'onFailure' => "The average loss per day have not stabilized since the flag triggered.
                                                The day the flag triggered the loss was {{initial_average_loss}} and the average last {{evaluation_interval}} days is {{current_average_loss}}, the change is {{percentage_diff}}%.
                                                Action taken, forced self-assessment test. New evaluation will be completed in {{nex_evaluation_in_days}} days time.",
                            ],
                        ],
                    ],
                ],
            ],
            'self-assessment' => [
                'states' => [
                    'CheckUsersGRSState' => [
                        'default' => [
                            'onSuccess' => "The interaction was successful.
                                        The automated evaluation process has been completed and the customer's risk profile
                                        has been reduced to {{rating_tag}}.",
                            'onFailure' => "",
                        ],
                        'triggers' => [],
                    ],
                    'CheckActivityState' => [
                        'default' => [
                            'onSuccess' => "",
                            'onFailure' => "",
                        ],
                        'triggers' => [
                            'RG8' => [
                                'onSuccess' => "The self-assessment test was as success.
                                                The last {{evaluation_interval}} days customer has spent less time playing than average {{hours_threshold}} hours per day.
                                                No further actions required.",
                                'onFailure' => "Game play have not decreased within the desired level,
                                                the last {{evaluation_interval}} days customer has spent more time playing than average {{hours_threshold}} hours per day.
                                                Action taken, flagged for manual review by analyst. Automated process has been completed.",
                            ],
                            'RG10' => [
                                'onSuccess' => "The self-assessment test was as success.
                                                The average deposit amount per transaction per day last {{evaluation_interval}} days has decreased or stabilized from the day the flag triggered.
                                                The flag triggered on a deposit amount per transaction  of {{avg_deposit_amount_per_transaction_before}} and the average last {{evaluation_interval}} days is {{avg_deposit_amount_per_transaction_after}}, the change is {{percentage_diff}}%.
                                                No further actions required.",
                                'onFailure' => "The average deposit amount per transaction per day have not stabilized since the flag triggered.
                                                The flag triggered on a deposit amount per transaction of {{avg_deposit_amount_per_transaction_before}} and the average last {{evaluation_interval}} days is {{avg_deposit_amount_per_transaction_after}}, the change is {{percentage_diff}}%.
                                                Action taken, flagged for manual review by analyst. Automated process has been completed.",
                            ],
                            'RG11' => [
                                'onSuccess' => "The self-assessment test was as success.
                                                The average deposit transactions per day last {{evaluation_interval}} days has decreased or stabilized from the day the flag triggered.
                                                The flag triggered on a deposit transactions of {{number_of_deposit_transactions_before_evaluation}} and the average last {{evaluation_interval}} days is {{number_of_deposit_transactions_after_evaluation}}, the change is {{percentage_diff}}%.
                                                No further actions required.",
                                'onFailure' => "The average deposit transactions per day have not stabilized since the flag triggered.
                                                The flag triggered on a deposit transactions of {{number_of_deposit_transactions_before_evaluation}} and the average last {{evaluation_interval}} days is {{number_of_deposit_transactions_after_evaluation}}, the change is {{percentage_diff}}%.
                                                Action taken, flagged for manual review by analyst. Automated process has been completed.",
                            ],
                            'RG12' => [
                                'onSuccess' => "The self-assessment test was as success.
                                                The average total deposit amount per day last {{evaluation_interval}} days has decreased or stabilized from the day the flag triggered.
                                                The flag triggered on a total deposit amount of '{{X}}' and the average last {{evaluation_interval}} days is '{{Y}}', the change is {{percentage_diff}}%.
                                                No further actions required.",
                                'onFailure' => "The average total deposit amount per day have not stabilized since the flag triggered.
                                                The flag triggered on a total deposit amount of '{{X}}' and the average last {{evaluation_interval}} days is '{{Y}}', the change is {{percentage_diff}}%.
                                                Action taken, flagged for manual review by analyst. Automated process has been completed.",
                            ],
                            'RG13' => [
                                'onSuccess' => "The self-assessment test was as success.
                                                The average deposit amount per transaction per day last {{evaluation_interval}} days has decreased or stabilized from the day the flag triggered.
                                                The flag triggered on a deposit amount per transaction  of {{avg_deposit_amount_per_transaction_before}} and the average last {{evaluation_interval}} days is {{avg_deposit_amount_per_transaction_after}}, the change is {{percentage_diff}}%.
                                                No further actions required.",
                                'onFailure' => "The average deposit amount per transaction per day have not stabilized since the flag triggered.
                                                The flag triggered on a deposit amount per transaction of {{avg_deposit_amount_per_transaction_before}} and the average last {{evaluation_interval}} days is {{avg_deposit_amount_per_transaction_after}}, the change is {{percentage_diff}}%.
                                                Action taken, flagged for manual review by analyst. Automated process has been completed.",
                            ],
                            'RG14' => [
                                'onSuccess' => "The self-assessment test was as success. Game play has not increased from the day flag triggered.
                                                Average bets last {{evaluation_interval}} days per day was {{current_average_bets}} and on the day of win it was {{initial_average_bets}}, change is {{percentage_diff}}%. No further actions required.",
                                'onFailure' => "Game play have increased the last {{evaluation_interval}} days compared to the day the player won big.
                                                Current average bet per day the last {{evaluation_interval}} days {{current_average_bets}} and on the win day it was {{initial_average_bets}}, the change is {{percentage_diff}}%.
                                                Action taken, flagged for manual review by analyst. Automated process has been completed.",
                            ],
                            'RG19' => [
                                'onSuccess' => "The self-assessment test was as success. The average loss per day the last {{evaluation_interval}} days has decreased or stabilized from the day the flag triggered.
                                                The day the flag triggered the loss was {{initial_average_loss}} and the average last {{evaluation_interval}} days is {{current_average_loss}}, the change is {{percentage_diff}}%. No further actions required.",
                                'onFailure' => "The average loss per day have not stabilized since the flag triggered.
                                                The day the flag triggered the loss was {{initial_average_loss}} and the average last {{evaluation_interval}} days is {{current_average_loss}}, the change is {{percentage_diff}}%.
                                                Action taken, flagged for manual review by analyst. Automated process has been completed.",
                            ],
                            'RG20' => [
                                'onSuccess' => "The self-assessment test was as success. The average loss per day the last {{evaluation_interval}} days has decreased or stabilized from the day the flag triggered.
                                                The day the flag triggered the loss was {{initial_average_loss}} and the average last {{evaluation_interval}} days is {{current_average_loss}}, the change is {{percentage_diff}}%. No further actions required.",
                                'onFailure' => "The average loss per day have not stabilized since the flag triggered.
                                                The day the flag triggered the loss was {{initial_average_loss}} and the average last {{evaluation_interval}} days is {{current_average_loss}}, the change is {{percentage_diff}}%.
                                                Action taken, flagged for manual review by analyst. Automated process has been completed.",
                            ],
                            'RG21' => [
                                'onSuccess' => "{{evaluation_interval}} days after last check, customer have added a limit again, so e-mail was successful. No further actions required.",
                                'onFailure' => "{{evaluation_interval}} days after last check, customer don’t have any limit in place. Action taken, flag account for manual review.",
                            ],
                            'RG28' => [
                                'onSuccess' => "The self-assessment test was as success. Game play has not increased from the day the flag triggered.
                                                Average total bets last {{evaluation_interval}} days per day was {{current_average_bets}} and on the day the flag triggered it was {{initial_average_bets}}, change is {{percentage_diff}}%.
                                                No further actions required.",
                                'onFailure' => "Game play have increased the last {{evaluation_interval}} days compared to the day the flag triggered.
                                                Current average total bets per day the last {{evaluation_interval}} days is {{current_average_bets}} and the day the flag triggered it was {{initial_average_bets}}, the change is {{percentage_diff}}%.
                                                Action taken, flagged for manual review by analyst. Automated process has been completed.",
                            ],
                            'RG29' => [
                                'onSuccess' => "The self-assessment test was as success.
                                                The average total unique bet transactions per day the last {{evaluation_interval}} days has decreased or stabilized from the day the flag triggered.
                                                The day the flag triggered the total unique bet transaction was '{{X}}' and the average last {{evaluation_interval}} days is '{{Y}}', the change is {{percentage_diff}}%.
                                                No further actions required.",
                                'onFailure' => "The average total unique bet transactions per day have not stabilized since the flag triggered.
                                                The day the flag triggered the total unique bet transactions was '{{X}}' and the average last {{evaluation_interval}} days is '{{Y}}', the change is {{percentage_diff}}%.
                                                Action taken, flagged for manual review by analyst. Automated process has been completed.",
                            ],
                            'RG30' => [
                                'onSuccess' => "The self-assessment test was as success. Game play has not increased from the day the flag triggered.
                                                Average total bets last {{evaluation_interval}} days per day was {{current_average_bets}} and on the day the flag triggered it was {{initial_average_bets}}, change is {{percentage_diff}}%.
                                                No further actions required.",
                                'onFailure' => "Game play have increased the last {{evaluation_interval}} days compared to the day the flag triggered.
                                                Current average total bets per day the last {{evaluation_interval}} days is {{current_average_bets}} and the day the flag triggered it was {{initial_average_bets}}, the change is {{percentage_diff}}%.
                                                Action taken, flagged for manual review by analyst. Automated process has been completed.",
                            ],
                            'RG31' => [
                                'onSuccess' => "The self-assessment test was as success. Game play has not increased from the day the flag triggered.
                                                Average total bets last {{evaluation_interval}} days per day was {{current_average_bets}} and on the day the flag triggered it was {{initial_average_bets}}, change is {{percentage_diff}}%.
                                                No further actions required.",
                                'onFailure' => "Game play have increased the last {{evaluation_interval}} days compared to the day the flag triggered.
                                                Current average total bets per day the last {{evaluation_interval}} days is {{current_average_bets}} and the day the flag triggered it was {{initial_average_bets}}, the change is {{percentage_diff}}%.
                                                Action taken, flagged for manual review by analyst. Automated process has been completed.",
                            ],
                            'RG32' => [
                                'onSuccess' => "The self-assessment test was as success.
                                                The average session time per day the last {{evaluation_interval}} days has decreased or stabilized from the day the flag triggered.
                                                The day the flag triggered the average session time was '{{X}}' and the average session time the last {{evaluation_interval}} days is '{{Y}}', the change is {{percentage_diff}}%.
                                                No further actions required.",
                                'onFailure' => "The average session time per day have not stabilized since the flag triggered.
                                                The day the flag triggered the average session time was '{{X}}' and the average last {{evaluation_interval}} days is '{{Y}}', the change is {{percentage_diff}}%.
                                                Action taken, flagged for manual review by analyst. Automated process has been completed.",
                            ],
                            'RG33' => [
                                'onSuccess' => "The self-assessment test was as success. The average time spent in a unique game session per day the last {{evaluation_interval}} days
                                                has decreased or stabilized from the day the flag triggered.
                                                The day the flag triggered the average time spent in a unique game session was '{{X}}' and the average time spent
                                                in a unique game session the last {{evaluation_interval}} days is '{{Y}}', the change is {{percentage_diff}}%. No further actions required.",
                                'onFailure' => "The average time spent in a unique game session per day have not stabilized since the flag triggered.
                                                The day the flag triggered the average time spent in a unique game session per day was '{{X}}' and the average last {{evaluation_interval}} days is '{{Y}}',
                                                the change is {{percentage_diff}}%. Action taken, flagged for manual review by analyst. Automated process has been completed.",
                            ],
                            'RG34' => [
                                'onSuccess' => "The self-assessment test was as success. The average unique game session per day the last {{evaluation_interval}} days has decreased or stabilized from the day the flag triggered.
                                                The day the flag triggered the amount of unique game session was '{{X}}' and the average amount of unique game sessions the last {{evaluation_interval}} days is '{{Y}}',
                                                the change is {{percentage_diff}}%. No further actions required.",
                                'onFailure' => "The average time spent in a unique game session per day have not stabilized since the flag triggered.
                                                The day the flag triggered the average time spent in a unique game session per day was '{{X}}' and the average last {{evaluation_interval}} days is '{{Y}}',
                                                the change is {{percentage_diff}}%. Action taken, flagged for manual review by analyst. Automated process has been completed.",
                            ],
                            'RG35' => [
                                'onSuccess' => "The self-assessment test was as success. The average bet in a unique game session per day the last {{evaluation_interval}} days has decreased
                                                or stabilized from the day the flag triggered. The day the flag triggered the average bet in a unique game session was '{{X}}'
                                                and the average bet in a unique game session the last {{evaluation_interval}} days is '{{Y}}', the change is {{percentage_diff}}%. No further actions required.",
                                'onFailure' => "The average bet in a unique game session per day have not stabilized since the flag triggered.
                                                The day the flag triggered the average bet in a unique game session was '{{X}}' and the average bet the last {{evaluation_interval}} days is '{{Y}}',
                                                the change is {{percentage_diff}}%. Action taken, flagged for manual review by analyst. Automated process has been completed.",
                            ],
                            'RG38' => [
                                'onSuccess' => "The self-assessment test was as success. The average loss per day the last {{evaluation_interval}} days has decreased or stabilized from the day the flag triggered.
                                                The day the flag triggered the loss was {{initial_average_loss}} and the average last {{evaluation_interval}} days is {{current_average_loss}}, the change is {{percentage_diff}}%. No further actions required.",
                                'onFailure' => "The average loss per day have not stabilized since the flag triggered. The day the flag triggered the loss was {{initial_average_loss}}
                                                and the average last {{evaluation_interval}} days is {{current_average_loss}}, the change is {{percentage_diff}}%. Action taken, flagged for manual review by analyst.
                                                Automated process has been completed.",
                            ],
                            'RG39' => [
                                'onSuccess' => "The self-assessment test was as success. The average loss per day the last {{evaluation_interval}} days has decreased or stabilized from the day the flag triggered.
                                                The day the flag triggered the loss was {{initial_average_loss}} and the average last {{evaluation_interval}} days is {{current_average_loss}}, the change is {{percentage_diff}}%. No further actions required.",
                                'onFailure' => "The average loss per day have not stabilized since the flag triggered. The day the flag triggered the loss was {{initial_average_loss}} and the average last {{evaluation_interval}} days is {{current_average_loss}},
                                                the change is {{percentage_diff}}%. Action taken, flagged for manual review by analyst. Automated process has been completed.",
                            ],
                            'RG59' => [
                                'onSuccess' => "The popup was as success. Game play as decreased with {{X}}%. No further actions required.",
                                'onFailure' => "Game play have not decreased within the desired level, its currently at (+ or -) {{X}}%. Action taken,
                                                flagged for manual review by analyst. Automated process has been completed.",
                            ],
                            'RG68' => [
                                'onSuccess' => "The self-assessment test was as success. The average loss per day the last {{evaluation_interval}} days has decreased or stabilized from the day the flag triggered.
                                                The day the flag triggered the loss was {{initial_average_loss}} and the average last {{evaluation_interval}} days is {{current_average_loss}}, the change is {{percentage_diff}}%. No further actions required.",
                                'onFailure' => "The average loss per day have not stabilized since the flag triggered.
                                                The day the flag triggered the loss was {{initial_average_loss}} and the average last {{evaluation_interval}} days is {{current_average_loss}}, the change is {{percentage_diff}}%.
                                                Action taken, flagged for manual review by analyst. Automated process has been completed.",
                            ],
                            'RG78' => [
                                'onSuccess' => "The self-assessment test was successful. Over the past three days, the average daily loss has either decreased or stabilized compared to the day the flag was triggered.
                                                On that day, the total loss was {{initial_average_loss}}, while the three-day average is now {{current_average_loss}}, reflecting a {{percentage_diff}}% change. No further action is required.",
                                'onFailure' => "The average daily loss has not stabilized since the flag was triggered.
                                                On that day, the total loss was {{initial_average_loss}}, while the three-day average is now {{current_average_loss}}, reflecting a {{percentage_diff}}% change.
                                                As a result, the case has been flagged for manual review by an analyst. The automated process has been completed.",
                            ],
                            'RG80' => [
                                'onSuccess' => "The self-assessment test was successful. The average daily loss over the last three days has either decreased or stabilized compared to the day the flag was triggered.
                                                On that day, the total loss was {{initial_average_loss}}, while the three-day average is now {{current_average_loss}}, reflecting a {{percentage_diff}}% change. No further action is required.",
                                'onFailure' => "The average daily loss has not stabilized since the flag was triggered.
                                                On that day, the total loss was {{initial_average_loss}}, while the three-day average is now {{current_average_loss}}, reflecting a {{percentage_diff}}% change.
                                                As a result, the case has been flagged for manual review by an analyst. The automated process has been completed.",
                            ],
                        ],
                    ],
                ],
            ],
        ],
    ],
];
