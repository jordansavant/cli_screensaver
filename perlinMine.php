<?php

class PerlinMap2D
{
    /// <summary>
    /// Two dimensional perlin noise map.
    /// </summary>
    /// <param name="size">Total number of random points.</param>
    /// <param name="subdivisions">Number of places between each point allowed to be smoothed into a gradient of all four surrounding points.</param>
    /// <param name="random">The random number generator, you may pass your own if you want the same seed generation etc.</param>
    public function __construct(int $size = 256, int $subdivisions = 10, string $seed = null)
    {
        $seed = $seed !== null ? $seed : microtime(true);
        $this->numericMapSeed = is_numeric($seed)
            ? $seed
            : intval(substr(md5($seed), -8), 16);
        mt_srand($this->numericMapSeed);

        $this->size = $size;
        $this->rowSize = (int)sqrt($size);
        $this->subdivisions = $subdivisions;
        $this->noiseMap = [];

        for ($i = 0; $i < $size; $i++)
        {
            $this->noiseMap[$i] = $this->getRandomFloat();
        }
    }

    private function getRandomFloat()
    {
        return mt_rand() / getrandmax();
    }

    /// <summary>
    /// Will retrieve perlin value at any location passed.
    /// </summary>
    /// <param name="x">x coordinate in map</param>
    /// <param name="y">y coordinate in map</param>
    /// <returns></returns>
    public function getValueAtPoint(float $x, float $y)
    {
        // Locate subdivision
        // X
        $realX = $x;
        $realX = $realX % ($this->rowSize * $this->subdivisions);
        while ($realX < 0) {
            $realX += ($this->rowSize * $this->subdivisions);
        }

        $perlinX = (int)($realX / $this->subdivisions);
        $perlinX = $perlinX % $this->rowSize;
        while ($perlinX < 0) {
            $perlinX += $this->rowSize;
        }

        $tX = $realX - ($perlinX * $this->subdivisions);
        $tX = $tX / $this->subdivisions;

        // Y
        $realY = $y;
        $realY = $realY % ($this->rowSize * $this->subdivisions);
        while ($realY < 0) {
            $realY += ($this->rowSize * $this->subdivisions);
        }

        $perlinY = (int)($realY / $this->subdivisions);
        $perlinY = $perlinY % $this->rowSize;
        while ($perlinY < 0) {
            $perlinY += $this->rowSize;
        }

        $tY = $realY - ($perlinY * $this->subdivisions);
        $tY = $tY / $this->subdivisions;

        // Smooth step subdivisions value.
        $tX = $this->smoothstep($tX);
        $tY = $this->smoothstep($tY);

        $perlinMinX = $perlinX;
        $perlinMinY = $perlinY;

        $perlinMaxX = ($perlinX + 1) % $this->rowSize;
        while ($perlinMinX < 0) {
            $perlinMinX += $this->rowSize;
        }
        $perlinMaxY = ($perlinY + 1) % $this->rowSize;
        while ($perlinMinY < 0) {
            $perlinMinY += $this->rowSize;
        }

        $randomAt00 = $this->noiseMap[(int)$perlinMinY * $this->rowSize + (int)$perlinMinX];
        $randomAt10 = $this->noiseMap[(int)$perlinMinY * $this->rowSize + (int)$perlinMaxX];
        $randomAt01 = $this->noiseMap[(int)$perlinMaxY * $this->rowSize + (int)$perlinMinX];
        $randomAt11 = $this->noiseMap[(int)$perlinMaxY * $this->rowSize + (int)$perlinMaxX];

        $nx0 = $this->lerp($randomAt00, $randomAt10, $tX);
        $nx1 = $this->lerp($randomAt01, $randomAt11, $tX);

        $tTotal = $this->lerp($nx0, $nx1, $tY);

        return $tTotal;
    }

    private function smoothstep(float $t)
    {
        return $t * $t * ( 3 - 2 * $t );
    }

    private function lerp(float $v1, float $v2, float $ratio)
    {
        return $v1 + $ratio * ($v2 - $v1);
    }

}

