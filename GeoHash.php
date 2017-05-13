<?php

class GeoHash
{
    const DOUBLE_LENGTH = 64;
    private static $LNG_RANGE = ['min' => -180, 'max' => 180];
    private static $LAT_RANGE = ['min' => -90, 'max' => 90];

    private $round_bin;
    private $bits_bin;
    private $lng;
    private $lat;
    private $bits_lng;
    private $bits_lat;
    private $level = 26;// 26位已能达到 0.6m 的精度

    public function __construct($lng, $lat, $level)
    {
        // todo :validate lng lat precision

        $this->lng = $lng;
        $this->lat = $lat;
        $this->level = $level ?: $this->level;
    }

    public function getHashBin()
    {
        $this->bits_lng = $this->getBits($this->lng, self::$LNG_RANGE);
        $this->bits_lat = $this->getBits($this->lat, self::$LAT_RANGE);


        $this->round_bin = $this->getAssembled($this->bits_lng, $this->bits_lat);
        return $this->round_bin;
    }

    public function getHashInt()
    {
        $bits_bin = $this->getHashBin();
        $bits_padded = str_pad($bits_bin, $this->level * 2, '0');
        return bindec($bits_padded);
    }

    private function getBits($loc, $range)
    {
        $bits = '';
        for ($i = 0; $i < $this->level; $i++) {
            $mid = ($range['min'] + $range['max']) / 2;
            if ($loc < $mid) {
                $bits .= '0';
                $range = ['min' => $range['min'], 'max' => $mid];
            } else {
                $bits .= '1';
                $range = ['min' => $mid, 'max' => $range['max']];
            }
        }

        $this->bits_bin = $bits;
        return $bits;
    }

    private function getAssembled($lng, $lat)
    {
        $bits_assembled = '';
        $arr_lng = explode('', $lng);
        $arr_lat = explode('', $lat);

        for ($i = 0, $c = count($arr_lng); $i < $c; $i++) {
            $bits_assembled .= $arr_lat[$i] . $arr_lng[$i];
        }

        return $bits_assembled;
    }

    private function getRoundBin($about = 1)
    {
        $accuracy = pow(2, $about - 1);
        $lng_incr = decbin(bindec($this->bits_lng) + $accuracy);
        $lng_decr = decbin(bindec($this->bits_lng) - $accuracy);

        $lat_incr = decbin(bindec($this->bits_lat) + $accuracy);
        $lat_decr = decbin(bindec($this->bits_lat) - $accuracy);

        $round['up'] = $this->getAssembled($this->bits_lng, $lat_incr);
        $round['down'] = $this->getAssembled($this->bits_lng, $lat_decr);
        $round['left'] = $this->getAssembled($lng_decr, $this->bits_lat);
        $round['right'] = $this->getAssembled($lng_incr, $this->bits_lat);
        $round['left_up'] = $this->getAssembled($lng_decr, $lat_incr);
        $round['right_up'] = $this->getAssembled($lng_incr, $lat_incr);
        $round['left_down'] = $this->getAssembled($lng_incr, $lat_decr);
        $round['right_down'] = $this->getAssembled($lng_incr, $lat_decr);

        return $round;
    }

    private function getRoundInt($about = 1)
    {


    }
}




/*
 *
 *  通过墨卡托投影下最大半径 获取到此半径应该使用的level
 *  最后将level和周边内的格子内的数据计算一遍，得到每个格子的最大、最小的int值，将数据取出来后过滤掉距离不对的。
 *
 *
 *
 uint8_t geohashEstimateStepsByRadius(double range_meters, double lat) {
    if (range_meters == 0) return 26;
    int step = 1;
                          // 地球半径
    while (range_meters < MERCATOR_MAX) {
        range_meters *= 2;
        step++;
    }
    step -= 2; /* Make sure range is included in most of the base cases. */

    /* Wider range torwards the poles... Note: it is possible to do better
     * than this approximation by computing the distance between meridians
     * at this latitude, but this does the trick for now.
    if (lat > 66 || lat < -66) {
        step--;
        if (lat > 80 || lat < -80) step--;
    }

    /* Frame to valid range.
    if (step < 1) step = 1;
    if (step > 26) step = 26;
    return step;
}*/