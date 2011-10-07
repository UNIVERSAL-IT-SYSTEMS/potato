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

function NtPasswordHash($pwPlain) {
    $uni = iconv('UTF-8', 'UTF-16LE', $pwPlain);
    return hash ("md4", $uni, true);
}

function NtPasswordHashHash($pwPlain) {
    $ntHash = NtPasswordHash($pwPlain);
    return hash ("md4", $ntHash, true);
}

function ChallengeResponse($challenge, $ntHash) {
    while (strlen($ntHash) < 21) {
        $ntHash .= "\0";
    }
    $resp1 = desEncrypt( substr($ntHash, 0, 7), $challenge);
    $resp2 = desEncrypt( substr($ntHash, 7, 7), $challenge);
    $resp3 = desEncrypt( substr($ntHash, 14, 7), $challenge);

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

function ChallengeHash($challenge, $peerChallenge, $userName) {
    $hash =  hash ("sha1", $peerChallenge . $challenge . $userName, true);
    return substr($hash, 0, 8);
}

function GenerateNTResponse($challenge, $peerChallenge, $userName, $pwPlain) {
    $challengeHash = ChallengeHash($challenge, $peerChallenge, $userName);
    $pwHash = NtPasswordHash($pwPlain);
    return ChallengeResponse($challengeHash, $pwHash);
}



/*

http://tools.ietf.org/html/draft-ietf-pppext-mschap-v2-01

A.6 GenerateAuthenticatorResponse()

   GenerateAuthenticatorResponse(
   IN  0-to-256-unicode-char Password,
   IN  24-octet              NT-Response,
   IN  16-octet              PeerChallenge,
   IN  16-octet              AuthenticatorChallenge,
   IN  0-to-256-char         UserName,
   OUT 42-octet              AuthenticatorResponse )
   {
      16-octet              PasswordHash
      16-octet              PasswordHashHash
      8-octet               Challenge

      /*
       * "Magic" constants used in response generation
       *

      Magic1[39] =
         {0x4D, 0x61, 0x67, 0x69, 0x63, 0x20, 0x73, 0x65, 0x72, 0x76,
          0x65, 0x72, 0x20, 0x74, 0x6F, 0x20, 0x63, 0x6C, 0x69, 0x65,
          0x6E, 0x74, 0x20, 0x73, 0x69, 0x67, 0x6E, 0x69, 0x6E, 0x67,
          0x20, 0x63, 0x6F, 0x6E, 0x73, 0x74, 0x61, 0x6E, 0x74};

      Magic2[41] =
         {0x50, 0x61, 0x64, 0x20, 0x74, 0x6F, 0x20, 0x6D, 0x61, 0x6B,
          0x65, 0x20, 0x69, 0x74, 0x20, 0x64, 0x6F, 0x20, 0x6D, 0x6F,
          0x72, 0x65, 0x20, 0x74, 0x68, 0x61, 0x6E, 0x20, 0x6F, 0x6E,
          0x65, 0x20, 0x69, 0x74, 0x65, 0x72, 0x61, 0x74, 0x69, 0x6F,
          0x6E};

      /*
       * Hash the password with MD4
       *

      NtPasswordHash( Password, giving PasswordHash )

      /*
       * Now hash the hash
       *


      HashNtPasswordHash( PasswordHash, giving PasswordHashHash)

      SHAInit(Context)
      SHAUpdate(Context, PasswordHashHash, 16)
      SHAUpdate(Context, NTResponse, 24)
      SHAUpdate(Context, Magic1, 39)
      SHAFinal(Context, Digest)

      ChallengeHash( PeerChallenge, AuthenticatorChallenge, UserName,
                     giving Challenge)

      SHAInit(Context)
      SHAUpdate(Context, Digest, 20)
      SHAUpdate(Context, Challenge, 8)
      SHAUpdate(Context, Magic2, 41)
      SHAFinal(Context, Digest)

      /*
       * Encode the value of 'Digest' as "S=" followed by
       * 40 ASCII hexadecimal digits and return it in
       * AuthenticatorResponse.
       * For example,
       *   "S=0123456789ABCDEF0123456789ABCDEF01234567"
       *

   }
*/

function GenerateAuthenticatorResponse($pwPlain, $ntResponse, $peerChallenge, $authChallenge, $userName) {

    $magic1 = "4D616769632073657276"
            . "657220746F20636C6965"
            . "6E74207369676E696E67"
            . "20636F6E7374616E74";
    $magic1bin = pack ('H*', $magic1);

    $magic2 = "50616420746F206D616B"
            . "6520697420646F206D6F"
            . "7265207468616E206F6E"
            . "6520697465726174696F"
            . "6E";
    $magic2bin = pack ('H*', $magic2);

    // Hash the password with MD4. Twice.
    $pwHashHash = NtPasswordHashHash($pwPlain);

    $digest = hash("sha1", $pwHashHash . $ntResponse . $magic1bin, true);

    $challenge = ChallengeHash($peerChallenge, $authChallenge, $userName);

    $authResponse = hash( "sha1", $digest . $challenge . $magic2bin );

    return "S=" . strtoupper($authResponse);
}


?>
