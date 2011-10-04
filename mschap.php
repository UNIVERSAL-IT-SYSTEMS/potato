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

function NtPasswordHash($plain) {
    $uni = iconv('UTF-8', 'UTF-16LE', $plain);
    $hash = hash ("md4", $uni);
    return ( pack( 'H*', $hash ) );
}

function ChallengeResponse($challenge, $nthash) {
    while (strlen($nthash) < 21) {
        $nthash .= "\0";
    }
    $resp1 = desEncrypt( substr($nthash, 0, 7), $challenge);
    $resp2 = desEncrypt( substr($nthash, 7, 7), $challenge);
    $resp3 = desEncrypt( substr($nthash, 14, 7), $challenge);

    return $resp1 . $resp2 . $resp3;
}

function desEncrypt($key, $str) {
    $key = insertParity($key);
    $crypto =  mcrypt_encrypt(MCRYPT_DES, $key, $str, MCRYPT_MODE_ECB);
    return $crypto;
}

// Insert dummy parity bits in 56-bit key to make valid 64-bit DES key
function insertParity($key) {
    $bits = "";
    for ($i = 0; $i < strlen($key); $i++) {
        $bits .= sprintf('%08s', decbin(ord($key{$i})));
    }

    // Add a zero after every seven bits
    $newkey = chunk_split($bits, 7, '0');
    $aNewkey = str_split( $newkey, 8);
    $finalkey = "";
    foreach($aNewkey as $j) {
        $finalkey .= sprintf('%02s', dechex(bindec($j)));
    }

    return pack('H*', $finalkey);
}

function ChallengeHash($challenge, $peerChallenge, $username) {
    $hash =  hash ("sha1", $peerChallenge . $challenge . $username);
    return substr(pack ( 'H*', $hash ), 0, 8);
}

function GenerateNTResponse($challenge, $peerChallenge, $username, $pwPlain) {
    $challengeHash = ChallengeHash($challenge, $peerChallenge, $username);
    $pwHash = NtPasswordHash($pwPlain);
    return ChallengeResponse($challengeHash, $pwHash);
}

?>
