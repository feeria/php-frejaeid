# feeria/php-frejaeid

Changelog for feeria/php-frejaeid.

The format is based on [Keep a Changelog][keep-a-changelog]
<!-- and this project adheres to [Semantic Versioning][semantic-versioning]. -->

## [1.0] (2021-08-14)

### Changed
- Initial release
- Code styling and commenting
- Small fixes
- Added 4th parameter to initAuthentication and initSignatureRequest, where you can request specific user FrejaeID data in array format, for example: ['EMAIL_ADDRESS','BASIC_USER_INFO','DATE_OF_BIRTH','SSN']. Full list could be found here: https://frejaeid.com/rest-api/Authentication%20Service.html
