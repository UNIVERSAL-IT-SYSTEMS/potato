<?php
/*
9.2.  Hash Example

   Intermediate values for user name "User" and password "clientPass".
   All numeric values are hexadecimal.

0-to-256-char UserName:
55 73 65 72

0-to-256-unicode-char Password:
63 00 6C 00 69 00 65 00 6E 00 74 00 50 00 61 00 73 00 73 00

16-octet AuthenticatorChallenge:
5B 5D 7C 7D 7B 3F 2F 3E 3C 2C 60 21 32 26 26 28

16-octet PeerChallenge:
21 40 23 24 25 5E 26 2A 28 29 5F 2B 3A 33 7C 7E

8-octet Challenge:
D0 2E 43 86 BC E9 12 26

16-octet PasswordHash:
44 EB BA 8D 53 12 B8 D6 11 47 44 11 F5 69 89 AE

24 octet NT-Response:
82 30 9E CD 8D 70 8B 5E A0 8F AA 39 81 CD 83 54 42 33 11 4A 3D 85 D6 DF

16-octet PasswordHashHash:
41 C0 0C 58 4B D2 D9 1C 40 17 A2 A1 2F A5 9F 3F

42-octet AuthenticatorResponse:
"S=407A5589115FD0D6209F510FE9C04566932CDA56"

*/

include "mschap.php";

$userName = pack ( 'H*', "55736572" );
$pwPlain = pack ( 'H*', "63006C00690065006E0074005000610073007300" );

$authChallenge = pack ( 'H*', "5B5D7C7D7B3F2F3E3C2C602132262628" );
$peerChallenge = pack ( 'H*', "21402324255E262A28295F2B3A337C7E" );

$challenge = "D02E4386BCE91226";

$pwHash = "44EBBA8D5312B8D611474411F56989AE";
$ntResponse = pack( 'H*', "82309ECD8D708B5EA08FAA3981CD83544233114A3D85D6DF" );
$pwHashHash = "41C00C584BD2D91C4017A2A12FA59F3F";

$authResponse = "S=407A5589115FD0D6209F510FE9C04566932CDA56";
echo "<pre>\n";
echo "     username: ${userName} \n";
echo "     password: ${pwPlain} \n";

$challengeCalc = strtoupper( bin2hex( ChallengeHash( $peerChallenge, $authChallenge, $userName ) ) );
echo "challengehash: ${challengeCalc}\n";
echo "    expecting: ${challenge}\n";

$pwHashCalc = strtoupper( bin2hex( NtPasswordHash($pwPlain) ) );
echo "    NT pwHash: ${pwHashCalc}\n";
echo "    expecting: ${pwHash}\n";

$ntResponseCalc = strtoupper( bin2hex( GenerateNtResponse($peerChallenge, $authChallenge, $userName, $pwPlain) ) );
echo "  NT response: ${ntResponseCalc}\n";
echo "    expecting: " . strtoupper( bin2hex($ntResponse)) . "\n";

$pwHashHashCalc = strtoupper( bin2hex( NtPasswordHashHash($pwPlain) ) );
echo "NT PwHashHash: ${pwHashHashCalc}\n";
echo "    expecting: ${pwHashHash}\n";

$authResponseCalc = GenerateAuthenticatorResponse($pwPlain, $ntResponse, $peerChallenge, $authChallenge, $userName);
echo " authResponse: ${authResponseCalc}\n";
echo "    expecting: ${authResponse}\n";

?>
