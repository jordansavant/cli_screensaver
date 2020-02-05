<?php

include "perlin.php";

function getSize() {
    return array_map("trim", explode(" ", shell_exec("stty size")));
}

function clearScreen() {
	echo chr(27).chr(91).'H'.chr(27).chr(91).'J';
}

function setCursor($line, $col) {
    echo "\033[${line};${col}H";
}

function getMinMax($map) {
    $min = $max = null;
    foreach ($map as $k => $row) {
        foreach($row as $val) {
            if ($min === null || $min > $val) {
                $min = $val;
            }
            if ($max === null || $max < $val) {
                $max = $val;
            }
        }
    }
    return [$min, $max];
}

$asciiTopo = [];
for ($i=33; $i<126; $i++) {
    $asciiTopo[] = chr($i);
}
shuffle($asciiTopo);

function getAsciiFromFloat($r) {
    #global $asciiTopo;
    #$range = count($asciiTopo) - 1;
    #$index = (int)($range * $r);
    #$a = $asciiTopo[$index];
    #return $a;

    // full ascii
    $range = 126 - 33;
    $ascii = 33 + (int)round($range * $r);
    return chr($ascii);
}

$fgColors = [
    30, // black
    90,
    34, // blue
    94,
    36, // cyan
    #96,
    37, // white
    #97
    #32, // green
    #92,
    #33, // yellow
    #93,
    #31, // red
    #91,
    #35, // magenta
    #95,
    #37, // white
    #97

    //40,
    //44,
    //46,
    //45,
    //41,
    //43,
    //42,
    //47
];
function getCliColorFromFloat($r, $ascii) {
    global $fgColors;
    $range = count($fgColors) - 1;
    $index = (int)($range * $r);
    $c = $fgColors[$index];
    return "\033[${c}m${ascii}\033[0m";
}

function getRatio($val, $min, $max) {
    // .7, .5, 1 = .4
    // .7 - .5 = .2
    // 1 - .5 = .5
    // .2 / .5 = .4
    return ($val - $min) / ($max - $min);
}

class destructDetect {
    public function __destruct() {
        #clearScreen();
    }
}
$d = new destructDetect();

$generator = new MapGenerator\PerlinNoiseGenerator();


clearScreen();
$l=0;
while (1) {
    $size = getSize();
    $h = (int)$size[0];
    $w = (int)$size[1];

    $generator->setPersistence(.5); //map roughness
    $generator->setSize(max($w, $h)); //heightmap size: 100x100
    $generator->setMapSeed(microtime(true));
    $map = $generator->generate();
    list($min, $max) = getMinMax($map);

    // write random characters for size
	$scr = '';
    for ($i=0; $i<$h; $i++) {
        $line = '';
        for ($j=0; $j<$w; $j++) {
            $m = $map[$i][$j];
            $r = getRatio($m, $min, $max); // float
            $ascii = getAsciiFromFloat($r);
            $colored = getCliColorFromFloat($r, $ascii);
            $scr .= $colored;
            $line .= $colored;
            echo "$colored";
            usleep(100);
        }
        $scr .= "\n";
        #echo "$line" . ($i < $h-1 ? "\n" : "");
        echo  ($i < $h-1 ? "\n" : "");
        #usleep(50 * 1000);
    }
    sleep(3);

    #echo trim($scr);
    setCursor(0, 0);
    $l++;
}
