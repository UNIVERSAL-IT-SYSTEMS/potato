<?php
/**
 * Mobile OTP self-service station and administration console
 * Version 1.0
 * 
 * PHP Version 5 with PDO, MySQL, and PAM support
 * 
 * Written by Markus Berg
 *   email: markus@kelvin.nu
 *   http://kelvin.nu/mossad/
 * 
 * Copyright 2011 Markus Berg
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *     http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 * 
 */

include "config.php";
include "session.php";
include 'header.php';
?>


<h1>Yubikey administration</h1>
<p>For users that do not have a mobile phone for use as a token, standard HOTP tokens are also supported; for instance the 
<a href="http://www.yubico.com/">Yubikey</a>. Here are step-by-step instructions for initializing your Yubikey
for first time use.</p>

<ol>
    <li>Download and install the <a href="http://www.yubico.com/personalization-tool">Yubikey personalization tool from the Yubico site</a></li>
    <li>Start the tool and select the OATH-HOTP mode</li>
    <li>Select the "Quick" programming mode<br />
<a href="images/yubikey.png"><img src="images/yubikey_thumb.jpg"></a>
</li>
    <li>Select "Configuration Slot 1"</li>
    <li>Deselect the "OATH Token Identifier"</li>
    <li>Set "HOTP Length" to "6 Digits"</li>
    <li>Deselect "Hide secret"</li>
    <li>Click "Regenerate"</li>
    <li>Click "Write Configuration"</li>
    <li>Copy-paste the "Secret Key (20 bytes Hex)" as the user's secret</li>
</ol>

<h2>Important</h2>
<p>As the HOTP tokens don't have PIN-codes, the user needs to enter their PIN in the passphrase field when logging in. 
For example: if a user has PIN-code <strong>"1234"</strong>, the user would enter "1234" and then press the Yubikey button for a total
password length of 10 characters.
</p>
<?php
include 'footer.php';
?>
