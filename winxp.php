<?php
/**
 * Potato
 * One-time-password self-service and administration
 * Version 1.0
 * 
 * Written by Markus Berg
 *   email: markus@kelvin.nu
 *   http://kelvin.nu/software/potato/
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

include 'config.php';
include 'session.php';
include 'header.php';
?>

<h1>Wifi instructions for Windows XP</h1>

<p>Windows XP doesn't play nice with non-Microsoft servers which causes lockouts when using PEAP/MSCHAPv2 authentication.
To prevent this, you must install a PEAP/GTC wifi-plugin from Aruba networks.</p>

<p>Follow these step-by-step instructions to configure
your wireless network:</p>

<ol>
  <li>Begin by downloading and installing the <a href="EAP-GTC-x86.msi">PEAP/GTC plugin</a>. 
Reboot after installation, even if you're not prompted to do so.</li>
  <li>Enter the Control Panel =&gt; network connections</li>

  <li>Right click on Wireless Network Connection and choose “Properties”<br />
    <a href="winxp/image1.png"><img src="winxp/thumbs/image1.jpg" alt="Wireless network configuration" /></a></li>

  <li>Select the “Wireless Networks” tab<br />
    <a href="winxp/image2.png"><img src="winxp/thumbs/image2.jpg" alt="Wireless network configuration" /></a></li>

  <li>Click <b>“Add…”</b>, and in the dialog box
    <ul>
      <li>Enter <b>“<?php echo $wifiSSID ?>”</b> as the SSID</li>
      <li>Choose WPA2 as the "Network Authentication"</li>
      <li>Choose AES as the Data encryption.</li>
    </ul>
    <a href="winxp/image3.png"><img src="winxp/thumbs/image3.jpg" /></a></li>

  <li>Select the Authentication tab, and select EAP type Protected EAP(PEAP). Make sure the two check boxes are unticked.<br />
    <a href="winxp/image4.png"><img src="winxp/thumbs/image4.jpg" /></a></li>

  <li>Click "Properties" on "Protected EAP (PEAP)"
    <ul>
      <li>Untick <b>“Validate server certificate”</b></li>
      <li>Select <b>"EAP-Token"</b> as the authentication method</li>
      <li>Untick <b>"Enable fast reconnect"</b></li>
    </ul>
    <a href="winxp/image5.png"><img src="winxp/thumbs/image5.jpg" /></a></li>

  <li>Click <b>“OK”</b> on this dialog box, and select the "Connection" tab of the “Wireless network properties” dialog.
    Untick <b>“Connect when the network is in range”</b>.<br />
    <a href="winxp/image6.png"><img src="winxp/thumbs/image6.jpg" /></a></li>

  <li>Click <b>“OK”</b></li>
  <li>Close the “Wireless Network Connection Properties” dialog box by clicking <b>“OK”</b></li>

  <li>Double-click the Wireless connection status icon in the System Tray.
    The “Wireless Network Connection”-dialog appears listing all wifi networks in range.<br />
    <a href="winxp/image7.png"><img src="winxp/thumbs/image7.jpg" /></a></li>

  <li>Double-click the <b>“<?php echo $wifiSSID ?>”</b> network<br />
    <a href="winxp/image8.png"><img src="winxp/thumbs/image8.jpg" /></a></li>

  <li>Wait until the networks requests additional credentials. <br />
    <img src="winxp/image021.jpg" /></li>

  <li>Click the bubble, and enter your username and a One-Time-Password in the dialog box. Leave "Logon domain" empty. Click <b>“OK”</b>.<br />
    <a href="winxp/image9.png"><img src="winxp/thumbs/image9.jpg" /></a></li>

  <li>Wait until network is connected.<br />
    <a href="winxp/imageA.png"><img src="winxp/thumbs/imageA.jpg" /></a><br />
    You are now authenticated for the next 24 hours.
  </li>

</ol>



<?php
include 'footer.php';
?>
