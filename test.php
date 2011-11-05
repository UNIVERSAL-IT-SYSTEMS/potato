<?php
$timeStart = gmdate("U");
for ($i=0; $i< 100000; $i++) {
    $rand = md5(rand());
    echo $rand . "\n";
}
$timeStop = gmdate("U");

$timeDiff = $timeStop - $timeStart;

echo "Diff: " . $timeDiff . "\n";

?>
