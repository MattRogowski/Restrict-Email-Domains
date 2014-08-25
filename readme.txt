Name: Restrict Email Domains
Description: Allows you to restrict which domains users can register with.
Website: https://github.com/MattRogowski/Restrict-Email-Domains
Author: Matt Rogowski
Authorsite: http://mattrogowski.co.uk
Version: 1.1
Compatibility: 1.6.x, 1.8.x
Files: 2
Settings added: 3 (1 group)

To Install:
Upload ./inc/plugins/restrictemaildomains.php to ./inc/plugins/
Upload ./inc/languages/english/restrictemaildomains.lang.php to ./inc/languages/english/
Go to ACP > Plugins > Activate

Information:
This plugin will allow you to only accept registrations from certain email domains.

You can also choose to override the check when editing users in the ACP.

Change Log:
14/10/10 - v0.1 -> Initial 'beta' release.
23/10/10 - v0.1 -> v0.2 -> Fixed bug where it would check the email where it shouldn't do. Also shows a list of allowed domains in the error saying the given domain is invalid. To upgrade, reupload ./inc/plugins/restrictemaildomains.php and ./inc/languages/english/restrictemaildomains.lang.php
08/11/10 - v0.2 -> v1.0 -> Tweaked how it checks the validity of the email address. To upgrade, reupload ./inc/plugins/restrictemaildomains.php
18/11/10 - v1.0 -> v1.0.1 -> Email address was validated before the check for if it should even be validated. To upgrade, reupload ./inc/plugins/restrictemaildomains.php
25/08/14 - v1.0.1 -> v1.1 -> MyBB 1.8 compatible. To upgrade, reupload ./inc/plugins/restrictemaildomains.php.

Copyright 2014 Matthew Rogowski

 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at

 ** http://www.apache.org/licenses/LICENSE-2.0

 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.