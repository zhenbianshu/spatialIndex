<?php

class GeoHash
{
    const LEVEL_MAX = 26; // 26位已能达到 0.6m 的精度
    const MERCATOR_LENGTH = 40075452.74; // 墨卡托投影下地球的赤道周长
    private static $LNG_RANGE = ['min' => -180, 'max' => 180];
    private static $LAT_RANGE = ['min' => -90, 'max' => 90];

    public function geoRadius()
    {
        /**
         *  数据准备：将地理点通过 getHashInt($lng, $lat) 获取其 hashInt 值，存入redis 的 sorted set
         *  查询：
         *      1. 通过 getIntLimit($center_lng, $center_lat, $radius); 获取到九个方格的 hashInt 值 和 其 range
         *      2. 用 ZRANGEBYSCORE key hashInt hashInt+range 获取九个方格内的地址点
         *      3. 遍历各个点（九个方格点范围略大），计算距离，返回严格符合要求的点
         */
    }

    /**
     * 获取某点geohash对应的Int值
     *
     * @param $lng
     * @param $lat
     *
     * @return number
     */
    public function getHashInt($lng, $lat)
    {
        $bits_lng = $this->getBits($lng, self::$LNG_RANGE);
        $bits_lat = $this->getBits($lat, self::$LAT_RANGE);

        $bits = $this->assembleBits($bits_lng, $bits_lat);

        return $this->getFormalInt($bits);
    }

    /**
     * 测试某一目的点是否在中心点N米范围内
     *
     * @param $center_lng
     * @param $center_lat
     * @param $radius
     * @param $aim_lng
     * @param $aim_lat
     */
    public function testInRadius($center_lng, $center_lat, $radius, $aim_lng, $aim_lat)
    {
        $limit = $this->getIntLimit($center_lng, $center_lat, $radius);
        $int_aim = $this->getHashInt($aim_lng, $aim_lat);

        $res = 'not_in_radius';
        foreach ($limit['cells'] as $int_cell) {
            if ($int_aim > $int_cell && $int_aim < $int_cell + $limit['range']) {
                $res = 'in_radius';
                break;
            }
        }
        echo $res . PHP_EOL;
    }

    /**
     * 通过二进制哈希串获取到规范化为52位的正整数
     *
     * @param $bits
     *
     * @return number
     */
    private function getFormalInt($bits)
    {
        $bits_padded = str_pad($bits, self::LEVEL_MAX * 2, '0');

        return bindec($bits_padded);
    }

    /**
     * 获取int值的限制
     *
     * @param $lng
     * @param $lat
     * @param $radius
     *
     * @return array
     */
    private function getIntLimit($lng, $lat, $radius)
    {
        $level = $this->getLevel($radius);
        $bits_lng = $this->getBits($lng, self::$LNG_RANGE, $level);
        $bits_lat = $this->getBits($lat, self::$LAT_RANGE, $level);

        $cells = $this->getRoundCells($bits_lng, $bits_lat);
        $cells['mid'] = $this->getFormalInt($this->assembleBits($bits_lng, $bits_lat));

        $range = $this->getLevelRange($level);

        $limit = [
            'cells' => $cells,
            'range' => $range,
        ];

        return $limit;
    }


    /**
     * 通过经度/纬度 和其范围值获取到其二进制哈希串
     *
     * @param $loc
     * @param $range
     * @param int $level
     *
     * @return string
     */
    private function getBits($loc, $range, $level = self::LEVEL_MAX)
    {
        $bits = '';
        for ($i = 0; $i < $level; $i++) {
            $mid = ($range['min'] + $range['max']) / 2;
            if ($loc < $mid) {
                $bits .= '0';
                $range = ['min' => $range['min'], 'max' => $mid];
            } else {
                $bits .= '1';
                $range = ['min' => $mid, 'max' => $range['max']];
            }
        }

        return $bits;
    }

    /**
     * 组合经度和纬度的二进制串
     *
     * @param $lng
     * @param $lat
     *
     * @return string
     */
    private function assembleBits($lng, $lat)
    {
        $bits_assembled = '';
        $arr_lng = str_split($lng);
        $arr_lat = str_split($lat);

        for ($i = 0, $c = count($arr_lng); $i < $c; $i++) {
            $bits_assembled .= $arr_lat[$i] . $arr_lng[$i];
        }

        return $bits_assembled;
    }

    /**
     * 获取中心格子四周的八个格子
     *
     * @param $bits_lng
     * @param $bits_lat
     *
     * @return mixed
     */
    private function getRoundCells($bits_lng, $bits_lat)
    {
        $lng_incr = decbin(bindec($bits_lng) + 1);
        $lng_decr = decbin(bindec($bits_lng) - 1);

        $lat_incr = decbin(bindec($bits_lat) + 1);
        $lat_decr = decbin(bindec($bits_lat) - 1);

        $cells['mid'] = $this->getFormalInt($this->assembleBits($bits_lng, $bits_lat));
        $cells['up'] = $this->getFormalInt($this->assembleBits($bits_lng, $lat_incr));
        $cells['down'] = $this->getFormalInt($this->assembleBits($bits_lng, $lat_decr));
        $cells['left'] = $this->getFormalInt($this->assembleBits($lng_decr, $bits_lat));
        $cells['right'] = $this->getFormalInt($this->assembleBits($lng_incr, $bits_lat));
        $cells['left_up'] = $this->getFormalInt($this->assembleBits($lng_decr, $lat_incr));
        $cells['right_up'] = $this->getFormalInt($this->assembleBits($lng_incr, $lat_incr));
        $cells['left_down'] = $this->getFormalInt($this->assembleBits($lng_incr, $lat_decr));
        $cells['right_down'] = $this->getFormalInt($this->assembleBits($lng_incr, $lat_decr));

        return $cells;
    }

    /**
     * 通过范围值获取geohash层级
     *
     * @param $range_meter
     *
     * @return int
     */
    private function getLevel($range_meter)
    {
        $level = 0;
        $global = self::MERCATOR_LENGTH;
        while ($global > $range_meter) {
            $global /= 2;
            $level++;
        }

        return $level;
    }

    /**
     * 通过哈希层级获取每一格子的范围
     *
     * @param $level
     *
     * @return number
     */
    private function getLevelRange($level)
    {
        $range = pow(2, 2 * (self::LEVEL_MAX - $level));

        return $range;
    }
}

$geohash = new GeoHash();
// 中心地址：新浪总部大厦
$geohash->testInRadius(116.276231, 40.041143, 3000, 116.276301, 40.041532); // 新浪餐厅 in
$geohash->testInRadius(116.276317, 40.04168, 3000, 116.27236, 40.04214); // 百度科技园 in
$geohash->testInRadius(116.276317, 40.04168, 3000, 116.274826521, 40.0321647826); // 兰园小区 in
$geohash->testInRadius(116.276317, 40.04168, 3000, 116.298505, 40.023749); // 上地医院 in
$geohash->testInRadius(116.276317, 40.04168, 3000, 116.31763, 40.01522); // 圆明园 not in