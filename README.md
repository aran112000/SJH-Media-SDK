# SJH Media SDK
This is the official PHP SDK for SJH Media enabling recyclers to integrate our platform into their existing setup.

##How to use this API
Your API Key and API Secret will need to be passed through into the sjh_media construct as per the example below, as soon as you've done so, you're ready to start making your first API requests via the SDK as follows:

##Making your first API Request
```php
<?php
$sjh_media = new sjh_media($api_key, $api_secret);

// Specify the function call you're trying to make
$sjh_media->setApiEndpoint('feed');

// For single request parameters they can be added using $key, $value parameters passed to setRequestParameter():
$sjh_media->setRequestParameter('id', '123');
// Where more than one parameter is required, you can also pass these all through in one hit using an array as follows:
$sjh_media->setRequestParameter(['id' => '132', 'type' => 'console']);

// Once you've built your full API request, it can be executed as follows:
$api_response = $sjh_media->doApiRequest();

// ... Handle the API response as you need from here
```
