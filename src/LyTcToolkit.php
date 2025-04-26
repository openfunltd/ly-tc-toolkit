<?php

declare(strict_types=1);

namespace OpenFun\LyTcToolkit;

class LyTcToolkit
{
    // 用公報逐字稿裡有可能出現的稱謂來比對發言者字串，例如「陳研究員小明：」
    const GAZETTE_SPEAKER_PREG = '/^(([a-zA-Z \x{4E00}-\x{9FFF}]+)?(院長|秘書|部長|次長|署長|主任|科長|課長|股長|科員|辦事員|書記|教授|講師|助教|校長|教師|局長|處長|司長|組長|主計|審計|總裁|董事|經理|主席|參事|委員|警監|警正|警佐|技監|技正|技士|技佐|分析師|法官|人員|廳長|工程司|所長|代表|談判|執行長|主持人|參謀|隊長|發言人|館長|檢察|人事長|督察|指揮|工程|警政|女士|先生|提名人|研究員|視察|法遵|副理|廠長|教育長|區長|學務長|總理|保護官|專員|律師|港務長)([a-zA-Z \x{4E00}-\x{9FFF}]+)?)：(.*)$/u';

    /**
     *  @param string $str The string to be parsed Ex: １２３, 一百三十萬, 貳佰萬 ...
     *
     */
    public static function parseNumber($str): int
    {
        if (preg_match('#^[０１２３４５６７８９]+$#u', $str)) {
            return self::parseFullWidthNumber($str);
        }

        if (preg_match('#^[零○一二三四五六七八九壹貳參肆伍陸柒捌玖]+$#', $str)) {
            return self::parseSimpleChineseNumber($str);
        }

        return self::parseChineseNumber($str);
    }

    protected static function getChineseNumberMap()
    {
        return [
            '零' => 0,
            '○' => 0,
            '一' => 1,
            '壹' => 1,
            '二' => 2,
            '貳' => 2,
            '三' => 3,
            '參' => 3,
            '四' => 4,
            '肆' => 4,
            '五' => 5,
            '伍' => 5,
            '六' => 6,
            '陸' => 6,
            '七' => 7,
            '柒' => 7,
            '八' => 8,
            '捌' => 8,
            '九' => 9,
            '玖' => 9,
            '十' => 10,
            '拾' => 10,
            '百' => 100,
            '佰' => 100,
            '千' => 1000,
            '仟' => 1000,
            '萬' => 10000,
            '億' => 100000000,
            '兆' => 1000000000000,
            '廿' => 20,
            '卅' => 30,
            '卌' => 40,
        ];
    }

    public static function parseSimpleChineseNumber($str)
    {
        $map = self::getChineseNumberMap();
        $ret = '';
        for ($i = 0; $i < mb_strlen($str); $i++) {
            $char = mb_substr($str, $i, 1);
            if (!isset($map[$char])) {
                throw new \Exception("Invalid character: $char");
            }

            $ret .= $map[$char];
        }
        return intval($ret);
    }

    public static function parseFullWidthNumber($str)
    {
        $map = ['０', '１', '２', '３', '４', '５', '６', '７', '８', '９'];
        foreach ($map as $num => $wide_num) {
            $str = str_replace($wide_num, (string) $num, $str);
        }
        return intval($str);
    }

    public static function parseChineseNumber($str)
    {
        $stack = [];
        $map = self::getChineseNumberMap();
        // 二十三億四千五百萬三千四百五十六
        $chars = preg_split('//u', $str, -1, PREG_SPLIT_NO_EMPTY);
        while (count($chars)) {
            $char = array_shift($chars);
            $char_number = $map[$char] ?? null;
            if (is_null($char_number)) {
                throw new \Exception("Invalid character: $char");
            }

            // 如果是一到九，放入 stack
            if ($char_number < 10) {
                // 如果 stack 最後一個是 10 的倍數，則相加
                if (count($stack) and end($stack)[0] == 10) {
                    $n = array_pop($stack)[1] + $char_number;
                    array_push($stack, [1, $n]);
                    continue;
                }
                array_push($stack, [1, $char_number]);
                continue;
            }

            // 如果是千或是百
            if ($char_number == 1000 or $char_number == 100) {
                // 如果 stack 最後一個是個位數，就乘上 $char_number (Ex: 一千, 二百)
                if (count($stack) and end($stack)[0] == 1) {
                    $n = array_pop($stack)[1] * $char_number;
                    if ($char_number == 100 and count($stack) and end($stack)[0] == 1000) {
                        $n += array_pop($stack)[1];
                    }
                    array_push($stack, [$char_number, $n]);
                    continue;
                }
            }

            // 如果是億或是萬
            if ($char_number == 100000000 or $char_number == 10000) {
                if (count($stack) and end($stack)[0] < $char_number) {
                    $n = array_pop($stack)[1] * $char_number;
                    array_push($stack, [$char_number, $n]);
                    continue;
                }
            }

            // 如果是十
            if (in_array($char_number, [10, 20, 30])) {
                // 上一位數如果是個位數
                if (count($stack) and end($stack)[0] == 1) {
                    $n = array_pop($stack)[1] * $char_number;
                    if (count($stack) and in_array(end($stack)[0], [100, 1000])) {
                        $n += array_pop($stack)[1];
                    }
                    array_push($stack, [10, $n]);
                    continue;
                }

                array_push($stack, [10, $char_number]);
                continue;
            }

            error_log("fail log: " . json_encode([
                'str' => $str,
                'char_number' => $char_number,
                'stack' => $stack,
                'chars' => $chars,
            ], JSON_UNESCAPED_UNICODE));
            throw new \Exception("Invalid character: $str");
        }

        $s = 0;
        foreach ($stack as $item) {
            $s += $item[1];
        }
        return $s;
    }
}
