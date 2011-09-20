<?php

$dadm = posix_getgrnam("dadm");
echo "<pre>";
print_r( $dadm );
echo "</pre>";

if ( in_array( "markus", $dadm[members] ) ) {
    echo "markus is in \"" . $dadm[name] . "\"";
} else {
    echo "nope";
}

?>
