# Feature Flags
Feature flags, or toggles, are code-based techniques that enable dynamic control over feature availability in application.
They allow for gradual rollouts, risk management, A/B testing, and conditional feature activation.

**Feature flags operate as follows:**
- **Setting Value:**
    - **Via Environment Variable** (useful for testing scripts via CLI and overriding values):
        - Set the specific feature flag variable (feature flag name) within the environment, either in the `.env` file or in domain-specific configuration.
            - For example, we can set the variable before executing a command from the terminal, as shown below:
                - `$ AML52_PAYOUT_DETAILS_REQUEST_EMAIL=true php console regulations:aml52:payout`
    - **Using Admin2 Config** (for management through Admin2):
        - To utilize this, you need to create a seeder to populate the feature flag-related configuration on admin2.
            - With the tag `'feature-flag'`
            - And type `'{"type":"choice","values":["on","off"]}')`.
- **Retrieving Value:**
    - To obtain the value of a specific feature flag, use the following code:
        - `phive('Config')->featureFlag('feature-flag-name')`
    - The priority for retrieving the value is as follows:
        - First, it checks the environment variables. If unavailable or false,
        - Then it checks the value from the config.


