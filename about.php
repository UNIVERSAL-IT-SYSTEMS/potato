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

<h1>About</h1>

<h2>What's so good about one-time-passwords?</h2>
<p>OTP's are good because they provide a second layer of security over plain passwords.
First, consider the case of the plain password.
If a plain password is intercepted by a malicious hacker, then your account is compromised.
That hacker is now able to log into your network, and has the same access to all your
resources as you do. In hacker-lingo: 0wn3d!</p>

<p>Now consider the same situation, but with OTP. It doesn't matter if a hacker intercepts 
your one-time-password since you've already used it. Even if a hacker is able to get your 
PIN code and one of your one-time-passwords, that still doesn't allow him/her access to
your data.</p>

<p>The only way your account can be compromised is if you lose your OTP-token to a 
malicious hacker who already knows your PIN-code. Without you finding out that your
token has been lost.</p>

<h2>How do one-time-passwords work?</h2>
<p>All passwords are based on the concept of shared secrets. In the case of plain passwords,
there's a single shared secret -- your password; which is known to the server
and to you. With OTP, there are two shared secrets:</p>
<ul>
    <li>One secret is known to the server and to your OTP-token (likely your mobile phone)</li>
    <li>The other secret is your PIN code. This is known to the server, and to you</li>
</ul>

<p>In order for the passwords to be continually changing, there are also these 
sources of entropy:
<ul>
    <li>In mOtp, the clock on the token needs to be
synchronized to the clock on the server.</li>
    <li>In OATH-HOTP, there's a token-internal counter which is automatically synchronized
between the token and the server</li>
</ul>

<p>The two secrets are combined with the entropy (clock or counter) on the client-end to create 
the one-time-password. Since the server is privy to the same secrets, and the same sources
of entropy it can easily generate a corresponding password to authenticate the client.
</p>

<?php
include 'footer.php';
?>
