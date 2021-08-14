# phpFreja

Simple PHP wrapper to get information from [Freja eID](https://frejaeid.com/en/developers-section/) [REST API](https://frejaeid.com/rest-api/Freja%20eID%20Relying%20Party%20Developers'%20Documentation.html) for use both in test and production enviroment.

- Supports validation of the JWS but requires external library for that part (thanks to [php-jws](https://github.com/Gamegos/php-jws)).
- Supports both directed and inferred authentication, for use with qr-code and app.
- Supports authentication and signature api but not the assertion service.
- Supports specific data fetching within array [Available data](https://frejaeid.com/rest-api/Authentication%20Service.html)
- Well behaved functions that do not throw (atleast not by design) but always return objects for simpler handling.

To setup your test enviroment and then basic agreement read this information on [Freja eID](https://org.frejaeid.com/en/developers-section/) website.

## Installation
To run installation type following in your project command line:
`composer require feeria/php-frejaeid`

## Quickstart
```PHP
use Feeria\PhpFrejaeid\PhpFrejaeid;

// Create FrejaeID API Instance
$frejaApi = new PhpFrejaeid(
  'path_to_your_pfx_certificate.pfx',
  'your_password',
  false, // production mode
);

// Initialize the authorization
$authResponse = $frejaApi->initAuthentication(
  'EMAIL',
  'FREJAEID_USER_EMAIL',
  'BASIC',  // auth level, can be BASIC, EXTENDED, PLUS
  [
    'EMAIL_ADDRESS',
    // and any another from official docs
  ]
);

// Return user data
if ($authResponse->success)
    $authStatus = $frejaApi->checkAuthentication($authResponse->authRef);

    return $authStatus; // all user data from FrejaeID

```

### Create URL for QR-Code
```PHP
$qrInfo = $frejaAPI->createAuthQRCode();

if ($qrInfo->success)
    $imageUrl = $qrInfo->url;
```

### Init, monitor and cancel authentication request
```PHP
$authResponse = $frejaAPI->initAuthentication('EMAIL','youremail@yourserver.com');

if ($authResponse->success)
    $authStatus = $frejaAPI->checkAuthRequest($authResponse->authRef);

$frejaAPI->cancelAuthentication($authResponse->authRef);
```

### Init, monitor and cancel signature request
```PHP
$signResponse = $frejaAPI->initSignatureRequest('EMAIL','youremail@yourserver.com','Testsign','This is the agreement text');

if ($signResponse->success)
    $signStatus = $frejaAPI->checkSignatureRequest($signResponse->signRef);

$frejaAPI->cancelSignatureRequest($authResponse->signRef);
```
