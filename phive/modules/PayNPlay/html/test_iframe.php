<?php
require_once __DIR__ . '/../../../phive.php';



if(phive('PayNPlay')->getSetting('test_iframe', false)){
    //stage env with enabled test_iframe config
} elseif (p( 'account.pnp.login' ) && isPNP()){
    //logged in Admin user with permission to view PNP test iframe
} else {
    die();
}

$baseDomain = phive('BrandedConfig')->isProduction() ? "http://syx-auth-api.videoslots.com/api/v1/auth" : "http://localhost:8020/api/v1/auth";
//$baseDomain = phive()->getSiteUrl(); // for docker setup

$amount = $_GET['amount'] ?? $_POST['amount'] ?? 0;
$currency = $_GET['currency'] ?? $_POST['currency'] ?? 'SEK';

function requestPnp($endpoint, $payload): array
{
    $customId = phive('BrandedConfig')->getConfigValue("PHIVE.FULL_DOMAIN_WITHOUT_SCHEMA", 'local.videoslots.com');
    $userAgent = $_SERVER['HTTP_USER_AGENT'];

    $response = json_decode(phive()->post(
        $endpoint,
        $payload,
        'application/json',
        [
            // brand header deprecated to be removed after kunga instance is not use
            "brand: kungaslottet.se",
            // market header deprecated to be removed after kunga instance is not use
            "market: SE",
            "device: device",
            "User-Agent: {$userAgent}",
            "X-Consumer-Custom-ID: {$customId}"
        ],
        'paynplay-test-deposit',
        'POST',
        30,
        [],
        'UTF-8',
        false
    ), true);

    return $response;
}


function generateRandomSwedishIP()
{
    $ipRangeStart = ip2long("78.64.0.0");
    $ipRangeEnd = ip2long("78.71.255.255");

    // Generate a random IP within the range
    $randomIP = long2ip(rand($ipRangeStart, $ipRangeEnd));

    return $randomIP;
}



if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $message = $_POST['message'] ?? '';
    $transaction_id = $_POST['transaction_id'] ?? '';
    $firstname = $_POST['firstname'] ?? '';
    $lastname = $_POST['lastname'] ?? '';
    $birthdate = $_POST['birthdate'] ?? '';
    $gender = $_POST['gender'] ?? '';
    $address = $_POST['address'] ?? '';
    $city = $_POST['city'] ?? '';
    $zipcode = $_POST['zipcode'] ?? '';
    $birth_country = $_POST['birth_country'] ?? '';
    $person_id = $_POST['person_id'] ?? '';
    $amount = $_POST['amount'] ?? '';
    $currency = $_POST['currency'] ?? '';
    $endpoint = $_POST['endpoint'] ?? '';
    $endpoint_event = $_POST['endpoint_event'] ?? '';
    $referral_id = $_POST['bonus_code'] ?? '';

    // 1. Send a POST request to the syx-auth-api endpoint with the data

    // Mock the next action based on the button clicked
    switch ($action) {
        case 'deposit_success':
            $transaction_id = 'test'.$transaction_id;

            $payload = [
                'transaction_id' => $transaction_id,
                'firstname' => $firstname,
                'lastname' => $lastname,
                'birthdate' => $birthdate,
                'gender' => $gender,
                'address' => $address,
                'city' => $city,
                'zipcode' => $zipcode,
                'birth_country' => $birth_country,
                'person_id' => $person_id,
                'amount' => $amount,
                'currency' => $currency,
                'ip' => remIp(),
                'user_id' => 0,
                'bonus_code' => $referral_id
            ];

            if(cu()){
                if(privileged(cu())){
                    $_SESSION['skip_pnp_logout'] = true;
                    phive('UserHandler')->logoutAndPreserveCsrfToken('logout');

                    //Registration is possible only for SE users. Privileged users are mostly MT. We force SE ip for them
                    $ip = generateRandomSwedishIP();
                    $payload['ip'] = $ip;
                    $_SESSION['rstep1']['pnp_ip'] = $ip;
                } else {
                    $payload['user_id'] = cu()->getId();
                }
            }

            $response = requestPnp($endpoint, $payload);

            if ($response['data']['status'] == 'REJECT') {
                echo json_encode([
                    'notification_response' => $response['data']['message'],
                    'redirect_url' => phive('PayNPlay')->getFailUrl() . '?transaction_id=' . $transaction_id,
                ]);

                break;
            }

            if (!empty($amount)) {
                phive('Casino')->depositCash(cu($response['data']['userid']), $amount * 100, 'trustly', uniqid());
            }
            $jsonData = json_encode([
                'notification_response' => $response['data'],
                'redirect_url' => phive('PayNPlay')->getSuccessUrl() . '?strategy=strategy_trustly&step=1&transaction_id=' . $transaction_id,
            ]);
            echo $jsonData;
            break;
        case 'deposit_fail':
            $payload = [
                'transaction_id' => $transaction_id,
                'status' => 'REJECT',
                'message' => $message
            ];

            $response = requestPnp($endpoint_event, $payload);

            echo json_encode([
                'notification_response' => $response,
                'redirect_url' => phive('PayNPlay')->getFailUrl() . '?transaction_id=' . $transaction_id,
            ]);
            break;
        case 'kyc_failure':
            $payload = [
                'transaction_id' => $transaction_id,
                'status' => 'REJECT',
                'message' => $message
            ];

            $response = requestPnp($endpoint_event, $payload);

            echo json_encode([
                'notification_response' => $response,
                'redirect_url' => phive('PayNPlay')->getFailUrl() . '?transaction_id=' . $transaction_id,
            ]);

            break;
    }
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Payment iFrame Testing</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            font-size: 12px;
            margin: 0;
        }

        .container {
            margin: auto;
            padding: 10px;
            box-sizing: border-box;
        }

        .form-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 10px;
        }

        .form-row label {
            flex: 1;
            text-align: right;
            margin-right: 10px;
            box-sizing: border-box;
        }

        .form-row input {
            flex: 3;
            padding: 5px;
            font-size: 12px;
            box-sizing: border-box;
        }

        .form-row select {
            flex: 3;
            padding: 5px;
            font-size: 12px;
            box-sizing: border-box;
        }

        #person_id {
            font-weight: bold;
        }

        button {
            padding: 10px;
            margin: 5px;
            font-size: 12px;
        }

        .debugelement, .regelement {
            display: none;
        }

        #buttonsContainer {
           display: flex;
           flex-wrap: wrap;
        }

        .w-100-pc {
            width: 100%;
            margin: 0px;
        }

    </style>
</head>
<body>


<div id="form_container" class="container">
    <? if(!p('account.pnp.login')) { ?>
        <p>
            <label for="debugCheckbox">Debug mode</label>
            <input type="checkbox" id="debugCheckbox" onchange="toggleButtons()">
        </p>

    <? } ?>

    <p>
        <label for="actionSelect">Select Action:</label>
        <select id="actionSelect" onchange="toggleElements()">
            <option value="login">Login</option>
            <option value="registration">New registration</option>
        </select>
    </p>

    <!--    Amount hidden input >-->
    <input type="hidden" id="amount" value="<?= $amount ?>">
    <input type="hidden" id="currency" value="<?= $currency ?>">
    <input type="hidden" id="bonus_code" value="<?= $_COOKIE['referral_id'] ?>">
    <div class="form-row">
        <label for="transaction_id">Transaction ID</label>
        <input type="text" id="transaction_id" value="913234414"><br>
    </div>

    <div class="form-row regelement">
        <label for="firstname">First Name</label>
        <input type="text" id="firstname" value="Chuck"><br>
    </div>

    <div class="form-row regelement">
        <label for="lastname">Last Name</label>
        <input type="text" id="lastname" value="Norris"><br>
    </div>

    <div class="form-row regelement">
        <label for="birthdate">Birthdate</label>
        <input type="text" id="birthdate" value="1990-01-01"><br>
    </div>

    <div class="form-row regelement">
        <label for="gender">Gender</label>
        <select id="gender">
            <option value="M">Male</option>
            <option value="F">Female</option>
        </select>
    </div>

    <div class="form-row regelement">
        <label for="address">Address</label>
        <input type="text" id="address" value="Street"><br>
    </div>

    <div class="form-row regelement">
        <label for="city">City</label>
        <input type="text" id="city" value="Stokholm"><br>
    </div>

    <div class="form-row regelement">
        <label for="zipcode">Zipcode</label>
        <input type="text" id="zipcode" value="009-000"><br>
    </div>

    <div class="form-row regelement">
        <label for="birth_country">Birth Country</label>
        <input type="text" id="birth_country" value="Sweden"><br>
    </div>

    <div class="form-row">
        <label for="person_id">Person ID</label>
        <input type="text" id="person_id" value="913234468"><br>
    </div>

    <div class="form-row debugelement">
        <label for="endpoint">KYC Endpoint</label>
        <input type="text" id="endpoint"
               value="<?= $baseDomain ?>/login-kyc"><br>
    </div>

    <div class="form-row debugelement">
        <label for="endpoint-event">KYC-Event Endpoint</label>
        <input type="text" id="endpoint_event"
               value="<?= $baseDomain ?>/login-kyc-event"><br>
    </div>

    <button id="depositSuccessBtn" class="w-100-pc">Login</button>

    <div id="buttonsContainer" class="debugelement">
        <button id="depositFailBtn">Deposit Fail: Monthly Limit Reached</button>
        <button class="kycFailBtn" error-type="blocked">KYC: Blocked</button>
        <button class="kycFailBtn" error-type="self-excluded">KYC: Self-Excluded</button>
        <button class="kycFailBtn" error-type="login-limit-reached">KYC: Login Limit Reached</button>
        <button id="netDepositThreshold" error-type="casino-net-deposit-threshold-reached">Casino: Net Deposit threshold Reached</button>
        <button id="customerNetDepositReached" error-type="customer-net-deposit-reached">Customer: Net Deposit Reached</button>
    </div>
</div>

<div id="debug" class="container" style="display: none;">
    <h3>Debug</h3>
    <pre id="debugResponse"></pre>
    <button id="debugContinue">Continue</button>
</div>
<div class="container">
    <p>Note:</p>
    <p>
        The values are stored between flows<br />
        In case user exists in system you'll be logged in<br />
        In case user is new - user will be created and you'll be logged in
    </p>
</div>

<script>
    // Function to save form data to localStorage
    function saveFormDataToLocalStorage() {
        const fields = [
            'transaction_id', 'firstname', 'lastname', 'birthdate',
            'address', 'city', 'zipcode', 'birth_country', 'person_id'
        ];
        fields.forEach(field => {
            const value = document.getElementById(field).value;
            localStorage.setItem(field, value);
        });
    }

    // Function to load form data from localStorage and generate random data for certain fields
    function loadFormDataFromLocalStorage() {
        const fields = [
            'birthdate', 'city', 'zipcode', 'birth_country', 'person_id'
        ];
        fields.forEach(field => {
            const value = localStorage.getItem(field);
            if (value) {
                document.getElementById(field).value = value;
            }
        });

        // Generate random values for specific fields
        const firstNames = ["Ethan", "Mia", "Logan", "Isabella", "Lucas", "Emma", "James", "Aria", "Alexander", "Ava"];
        const lastNames = ["Anderson", "Martin", "Thompson", "Garcia", "Rodriguez", "Martinez", "Hernandez", "Lopez", "Gonzalez", "Perez"];
        const addresses = ["12 Cherry Lane", "98 Sycamore Drive", "77 Magnolia Street", "44 Walnut Avenue", "66 Cypress Road", "39 Juniper Trail", "21 Poplar Crescent", "50 Redwood Blvd", "88 Chestnut Place", "11 Aspen Court"];
        const transactionId = Math.floor(Math.random() * 1000000000);

        document.getElementById('firstname').value = firstNames[Math.floor(Math.random() * firstNames.length)];
        document.getElementById('lastname').value = lastNames[Math.floor(Math.random() * lastNames.length)];
        document.getElementById('address').value = addresses[Math.floor(Math.random() * addresses.length)];
        document.getElementById('transaction_id').value = transactionId.toString();
    }

    let redirect_url = '';
    document.addEventListener('DOMContentLoaded', function () {
        // Load form data from localStorage
        loadFormDataFromLocalStorage();

        const depositSuccessBtn = document.getElementById('depositSuccessBtn');
        const depositFailBtn = document.getElementById('depositFailBtn');
        const kycFailButtons = document.querySelectorAll('.kycFailBtn');
        const debugContinueBtn = document.getElementById('debugContinue');
        const netDepositThreshold = document.getElementById('netDepositThreshold');
        const customerNetDepositReached = document.getElementById('customerNetDepositReached');


        function makePostRequest(action, message) {
            saveFormDataToLocalStorage(); // Save form data before making the POST request

            const formData = new URLSearchParams();
            formData.append('token', '<?= $_SESSION['token'] ?>');
            formData.append('action', action);
            if (message) {
                formData.append('message', message);
            }

            const fields = [
                'transaction_id', 'firstname', 'lastname', 'birthdate', 'gender',
                'address', 'city', 'zipcode', 'birth_country', 'person_id', 'endpoint', 'endpoint_event',
                'amount', 'currency', 'bonus_code'
            ];

            fields.forEach(field => {
                formData.append(field, document.getElementById(field).value);
            });

            fetch(window.location.href, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: formData
            }).then(async response => {
                // Handle server response here, if needed
                const ret = await response.json();
                document.getElementById('debugResponse').innerHTML = JSON.stringify(ret.notification_response, null, 2);
                redirect_url = ret.redirect_url;
                // hide the form_container and show the debug container
                document.getElementById('form_container').style.display = 'none';
                document.getElementById('debug').style.display = 'block';
            });
        }

        function handleKycFailButtonClick(event) {
            const button = event.target;

            if (button.hasAttribute('error-type')) {
                const errorType = button.getAttribute('error-type');
                makePostRequest('kyc_failure', errorType);
            }
        }

        depositSuccessBtn.addEventListener('click', function () {
            makePostRequest('deposit_success', 'deposit-success');
        });

        depositFailBtn.addEventListener('click', function () {
            makePostRequest('deposit_fail', 'monthly-net-deposit-limit-reached');
        });

        netDepositThreshold.addEventListener('click', function (event) {
            const button = event.target;
            const errorType = button.hasAttribute('error-type') ? button.getAttribute('error-type') : 'casino-net-deposit-threshold-reached';
            makePostRequest('deposit_fail', errorType);
        });

        customerNetDepositReached.addEventListener('click', function (event) {
            const button = event.target;
            const errorType = button.hasAttribute('error-type') ? button.getAttribute('error-type') : 'customer-net-deposit-reached';

            makePostRequest('deposit_fail', errorType);
        });

        kycFailButtons.forEach(button => {
            button.addEventListener('click', handleKycFailButtonClick);
        });

        debugContinueBtn.addEventListener('click', function () {
            // redirect to the redirect_url
            window.location.href = redirect_url;
        });
    });

    function toggleButtons() {
        var debugElements = document.querySelectorAll(".debugelement");
        var debugCheckbox = document.getElementById("debugCheckbox");

        debugElements.forEach(function(element) {
            if (debugCheckbox.checked) {
                element.style.display = "flex";
            } else {
                element.style.display = "none";
            }
        });
    }

    function toggleElements() {
        var actionSelect = document.getElementById("actionSelect");
        var regElements = document.querySelectorAll(".regelement");

        if (actionSelect.value === "registration") {
            regElements.forEach(function(element) {
                element.style.display = "flex";
            });

            document.getElementById('person_id').value = generateRandomNumber();

        } else {
            regElements.forEach(function(element) {
                element.style.display = "none";
            });
        }
    }

    function generateRandomNumber() {
        // Generate a random date within a reasonable range
        const randomDate = getRandomDate();

        // Generate a random 4-digit number
        const randomNumber = Math.floor(Math.random() * 10000).toString().padStart(4, '0');

        // Combine the parts to create the final random number
        const formattedNumber = randomDate + randomNumber;

        return formattedNumber;
    }

    function getRandomDate() {
        const startDate = new Date('1920-01-01');
        const endDate = new Date('2000-12-31');
        const randomDate = new Date(startDate.getTime() + Math.random() * (endDate.getTime() - startDate.getTime()));

        const year = randomDate.getFullYear();
        const month = (randomDate.getMonth() + 1).toString().padStart(2, '0');
        const day = randomDate.getDate().toString().padStart(2, '0');

        return `${year}${month}${day}`;
    }
</script>

</body>
</html>


