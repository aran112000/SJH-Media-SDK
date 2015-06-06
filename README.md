# SJH Media SDK
This is the official PHP SDK for SJH Media enabling recyclers to integrate our platform into their existing setup.

##How to use this API
In sjh_media_api.php you will need to update 2 lines of code at the top of the file as indicated to include both your API Key and Secret Key as will have been provided by the onboarding team at SJH Media.

Once in place you're ready to start making your first API requests via the SDK as follows:

##Making your first API Request
```php
<?php
$sjh_media = new sjh_media();

// Specify the function call you're trying to make
$sjh_media->setApiEndpoint('/feed');

// For single request parameters they can be added using $key, $value parameters passed to setRequestParameter():
$sjh_media->setRequestParameter('format', 'json');
// Where more than one parameter is required, you can also pass these all through in one hit using an array as follows:
$sjh_media->setRequestParameter(['format' => 'json', 'type' => 'console']);

// Once you've built your full API request, it can be executed as follows:
$api_response = $sjh_media->doApiRequest();

$array_formatted_response = json_decode($api_response);

// ... Handle the API response as you need from here
```
