# Swagger\Client\DefaultApi

All URIs are relative to *https://localhost*

Method | HTTP request | Description
------------- | ------------- | -------------
[**rootPost**](DefaultApi.md#rootPost) | **POST** / | Search for person


# **rootPost**
> rootPost($first_name, $last_name, $date_of_birth, $email, $postcode, $x_trace_id)

Search for person

### Example
```php
<?php
require_once(__DIR__ . '/vendor/autoload.php');

// Configure API key authorization: X-API-Key
Swagger\Client\Configuration::getDefaultConfiguration()->setApiKey('X-API-Key', 'YOUR_API_KEY');
// Uncomment below to setup prefix (e.g. Bearer) for API key, if needed
// Swagger\Client\Configuration::getDefaultConfiguration()->setApiKeyPrefix('X-API-Key', 'Bearer');

$api_instance = new Swagger\Client\Api\DefaultApi();
$first_name = "first_name_example"; // string | First name of person, only 20 characters are significant
$last_name = "last_name_example"; // string | Last name of person, only 20 characters are significant
$date_of_birth = "date_of_birth_example"; // string | Date of birth in ISO format (yyyy-mm-dd)
$email = "email_example"; // string | Email address
$postcode = "postcode_example"; // string | Postcode - spaces not significant
$x_trace_id = "x_trace_id_example"; // string | A freeform field that is put into the audit log that can be used by the caller to identify a request.  This might be something to indicate the person being checked (in some psuedononymous fashion), a unique request ID, GUID, or a trace ID from a system such as zipkin

try {
    $api_instance->rootPost($first_name, $last_name, $date_of_birth, $email, $postcode, $x_trace_id);
} catch (Exception $e) {
    echo 'Exception when calling DefaultApi->rootPost: ', $e->getMessage(), PHP_EOL;
}
?>
```

### Parameters

Name | Type | Description  | Notes
------------- | ------------- | ------------- | -------------
 **first_name** | **string**| First name of person, only 20 characters are significant |
 **last_name** | **string**| Last name of person, only 20 characters are significant |
 **date_of_birth** | **string**| Date of birth in ISO format (yyyy-mm-dd) |
 **email** | **string**| Email address |
 **postcode** | **string**| Postcode - spaces not significant |
 **x_trace_id** | **string**| A freeform field that is put into the audit log that can be used by the caller to identify a request.  This might be something to indicate the person being checked (in some psuedononymous fashion), a unique request ID, GUID, or a trace ID from a system such as zipkin | [optional]

### Return type

void (empty response body)

### Authorization

[X-API-Key](../../README.md#X-API-Key)

### HTTP request headers

 - **Content-Type**: application/x-www-form-urlencoded
 - **Accept**: application/x-www-form-urlencoded

[[Back to top]](#) [[Back to API list]](../../README.md#documentation-for-api-endpoints) [[Back to Model list]](../../README.md#documentation-for-models) [[Back to README]](../../README.md)

