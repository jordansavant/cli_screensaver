<?php

include "perlinMine.php";
include "perlinTheirs.php";

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

$asciiTopo = [
    ' ', '.', '!', '/', ';', '\\',
    ':', ',', '=',
    'a', 'b', 'c', 'd', 'e', 'f',
    'g', 'h', 'i', 'j', 'k', 'l',
    'm', 'n', 'o', 'p', 'q', 'r',
    's', 't', 'u', 'v', 'w', 'x',
    'y', 'z', '$', '%', '@',
    '{', '}', '[', ']', '-', '+',
    '*', '(', ')', '<', '>', '?',
];
$asciiTopo = array_reverse($asciiTopo);

function getAsciiFromFloat($r) {
    global $asciiTopo;
    $range = count($asciiTopo) - 1;
    $index = (int)($range * $r);
    $a = $asciiTopo[$index];
    return $a;

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
function get16ColorFromFloat($r, $ascii) {
    global $fgColors;
    $range = count($fgColors) - 1;
    $index = (int)($range * $r);
    $c = $fgColors[$index];
    return "\033[${c}m${ascii}\033[0m";
}

function get256ColorFromFloat($r, $ascii) {
    $range = 255;
    $color = (int)($range * $r);
    return "\033[38;5;${color}m${ascii}\033[0m";
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
$mode = $argv[1] ?? null;
$mode = $mode == '16' ? '16' : '256';
$loopTimer = max(0, min($argv[2] ?? 3, 100));
$lineDrawMs = max(0, min($argv[3] ?? 50, 1000));
while (1) {

    $perlinType = $l % 2 == 0 ? "mine" : "theirs";

    $size = getSize();
    $h = (int)$size[0] *  ($mode == 16 ? 5 : 1 );
    $w = (int)$size[1];

    # randomize the perlin noise type for fun
    if ($perlinType == "mine") {
        $perlin = new PerlinMap2D(max($w, $h), 70);
        $min = 0; $max = 1;
    } else {
        $generator->setPersistence(.5); //map roughness
        $generator->setSize(max($w, $h)); //heightmap size: 100x100
        $generator->setMapSeed(microtime(true));
        $map = $generator->generate();
        list($min, $max) = getMinMax($map);
    }

    // write random characters for size
	$scr = '';
    for ($i=0; $i<$h; $i++) {
        $line = '';
        for ($j=0; $j<$w; $j++) {
            if ($perlinType == 'mine') {
                $m = $perlin->getValueAtPoint($i, $j);
            } else {
                $m = $map[$i][$j];
            }
            $r = getRatio($m, $min, $max); // float
            $ascii = getAsciiFromFloat($r);
            if ($mode == '256')
                $colored = get256ColorFromFloat($r, $ascii);
            else
                $colored = get16ColorFromFloat($r, $ascii);
            $scr .= $colored;
            $line .= $colored;
            #echo "$colored";
            #usleep(100);
        }
        $scr .= "\n";
        echo "$line";
        usleep($lineDrawMs * 1000);
        echo  ($i < $h-1 ? "\n" : "");
    }
    sleep($loopTimer);

    #echo trim($scr);
    setCursor(0, 0);
    $l++;
}
