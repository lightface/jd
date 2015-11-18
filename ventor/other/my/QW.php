<?php

namespace my;



class QW {
    static $default=false;
    static $default_badword=false;
    public static function setlog($data, $filename = 'log.txt') {
        $data = var_export($data, TRUE);
        file_put_contents(WEB_ROOT.'runtime/logs/'.$filename, date('Y-m-d H:i:s ：').$data."\n", FILE_APPEND);
    }

    /**
     * 对特殊字符进行转义
     * @param	$string: 原始字符串或者字符数组
     * @return	string OR array
     */
    public static function slashes($string) {
        if(is_array($string)) {
            foreach($string as $key => $val) {
                unset($string[$key]);
                $string[addslashes($key)] = self::slashes($val);
            }
        } else {
            $string = trim(addslashes(self::format_br($string)));
        }
        return $string;
    }

    /**
     * 验证IP格式
     * @param	$ip: ip
     * @return	string || FALSE
     */
    public static function is_ip($ip) {
        preg_match("/[\d\.]{7,15}/", $ip, $ipmatches);
        return isset($ipmatches[0]) && $ipmatches[0] ? $ipmatches[0] : FALSE;
    }

    /**
     * 将换行统一转换为 \n
     * @param	$string: 原始字符串
     * @return	string
     */
    public static function format_br($string) {
        return str_replace(array("\r\n", "\r"), array("\n", "\n"), $string);
    }

    /**
     * 过滤字符串中的空白字符
     * @param	$string: 原始字符串
     * @return	string
     */
    public static function trim($string, $trim_blank = FALSE) {
        if (strlen($string) === 0) {
            return '';
        }
        $string = preg_replace('/(\r|\n|\t|\f|\v)/', '', $string);
        return str_replace(array('　', ' ', '&nbsp;'), array('', '', ''), $string);
    }

    /**
     * 过滤所有html标签
     * @param	$string: 原始字符串
     * @return	string
     */
    public static function str_filter($string) {
        if (is_array($string)) {
            foreach ($string as $key => $val) {
                $string[$key] = self::str_filter($val);
            }
        } else {
            $string = preg_replace('/&amp;((#(\d{3,5}|x[a-fA-F0-9]{4}));)/', '&\\1',
                str_replace(
                    array('&', '"', '<', '>'),
                    array('&amp;', '&quot;', '&lt;', '&gt;'),
                    $string
                )
            );
        }
        return $string;
    }

    /**
     * 允许html标签，过滤js/object/iframe代码
     * @param	$string
     * @return	string
     */
    public static function html_filter($string) {
        if (is_array($string)) {
            foreach ($string as $key => $val) {
                $string[$key] = self::html_filter($val);
            }
        } else {
            /*$string = str_replace(
                array('&', '<?', '?>'),
                array('&amp;', '&lt;?', '?&gt;'),
                $string
            );*/
            $string = str_replace(
                array('<?', '?>'),
                array('&lt;?', '?&gt;'),
                $string
            );
            $pattern = array(
                '/<script/i',
                '/<\/script>/i',
                '/\son([a-zA-Z]*)=/i',
                '/<iframe(.*?)>(.*?)<\/iframe>/i',
                '/<object/i',
                '/<\/object>/i',
            );
            $replacement = array(
                '&lt;script',
                '&lt;/script&gt;',
                ' on$1&#61',
                '',
                '&lt;object',
                '&lt;object&gt;'
            );
            $string = preg_replace($pattern, $replacement, $string);
        }

        return $string;
    }

    /**
     * 将字符串中的网址和图片地址自动加上链接
     * @param	$string: 原始字符串
     * @return	string
     */
    public static function url_parse($string) {
        return preg_replace(array(
            "/([^>=\"'\/]|^)((https?|ftp):\/\/|www\.)([a-z0-9\/\-_+=.~!%@?#%&;:$\\│]+\.(gif|jpg|png))(?![\w\/\-+\.$&?#]{1})/i",
            "/([^>=\"'\/@]|^)((https?|ftp|gopher|news|telnet|mms|rtsp|thunder|ed2k):\/\/)([a-z0-9\/\-_+=.~!%@?#%&;:$\\│\|]+)/i",
            "/([^>=\"'\/@]|^)((www\.)([\w\-]+\.))([a-z0-9\/\-_+=.~!%@?#%&;:$\\│\|]+)/i"
        ),
            array(
                '<br /><a href="$2$4" target="_blank"><img src="$2$4" border="0" /></a><br />',
                '<a href="$2$4" target="_blank">$2$4</a>',
                '<a href="http://$2$5" target="_blank">$2$5</a>'
            ),
            $string
        );
    }

    /**
     * 格式化数字参数(不允许小于0)
     * @param	$n: 原始数字
     * @param	$default: 如果原始数字小于0或者不是数字类型则返回默认值
     * @return	int
     */
    public static function number_filter($number, $default = 0) {
        if (is_array($number)) {
            foreach ($number as $key => $val) {
                $number[$key] = self::number_filter($val);
            }
        } else {
            if (is_numeric($number)) {
                if ($number > 2147483647) {
                    $number = 2147483647;
                } else if ($number <= 0) {
                    $number = $default;
                } else {
                    $number = intval($number);
                }
            } else {
                $number = $default;
            }
        }
        return $number;
    }

    /**
     * 设置cookie
     * @param	$key: cookie名字
     * @param	$value: cookie值
     * @param	$life: cookie有效期(单位:秒)
     * @param	$prefix: 是否包含cookie前缀
     * @return	void
     */
    public static function set_cookie($key, $value, $life = 0, $prefix = TRUE) {
        $key = ($prefix ? BBC::get_config('cookie.pre') : '').$key;
        if ($value == '' || $life < 0) {
            $value = '';
            $life = -1;
        }
        $life = $life > 0 ? TIME_STAMP + $life : ($life < 0 ? TIME_STAMP - 31536000 : 0);
        $secure = BBC::get_request()->server('SERVER_PORT') == '443' ? 1 : 0;
        if (PHP_VERSION < 5.2) {
            setcookie($key, $value, $life, BBC::get_config('cookie.path').'; HttpOnly', BBC::get_config('cookie.domain'), $secure);
        } else {
            setcookie($key, $value, $life, BBC::get_config('cookie.path'), BBC::get_config('cookie.domain'), $secure, TRUE);
        }
    }

    /**
     * 获取cookie
     * @param	$key: cookie名字
     * @param	$prefix: 是否包含cookie前缀
     * @return	string
     */
    public static function get_cookie($key, $prefix = TRUE) {
        $key = $prefix ? BBC::get_config('cookie.pre').$key : $key;
        return (isset($_COOKIE[$key]) ? $_COOKIE[$key] : '');
    }

    /**
     * 设置header
     * @param	$string: header的内容
     * @param	$replace: 是否替换之前的header
     * @return	void
     */
    public static function set_header($string, $replace = TRUE) {
        header($string, $replace);
        preg_match('/^\s*Location:/is', $string) && exit();
    }

    /**
     * 跳转url
     * @param	$url: url
     * @return	void
     */
    public static function redirect($url) {
        self::set_header("HTTP/1.1 302 Found");
        self::set_header("Location: $url");
    }

    /**
     * XXTEA 加密
     * @param	$string: 待加密的字符串
     * @param	$key: 解密的key，不指定就使用系统的配置
     * @return	string
     */
    public static function encrypt($string, $key = '') {
        if (empty($key)) {
            $key = BBC::get_config('security.key');
        }
        return XXTea::encrypt($string, $key);
    }

    /**
     * XXTEA 解密
     * @param	$string: 密文
     * @param	$key: 解密的key，不指定就使用系统的配置
     * @return	string
     */
    public static function decrypt($string, $key = '') {
        if (empty($key)) {
            $key = BBC::get_config('security.key');
        }
        return XXTea::decrypt($string, $key);
    }

    /**
     * 根据文件路径获取目录，同时处理 windows 系统下根目录使用原生 dirname 变为 \ 的问题
     * @param	$path: 文件路径
     * @return	string
     */
    public static function dirname($path) {
        return self::path_fix(str_replace('\\', '/', dirname($path)));
    }

    /**
     * 路径前后的 /
     * @param	$path: 文件路径
     * @param 	$add: / 的位置
     * @return	string
     */
    public static function path_fix($path, $add = 'all') {
        $path = trim($path, '/');

        if (strlen($path) === 0) {
            return '/';
        }

        $left = $right = '';

        if ($add == 'left' || $add == 'all') {
            $left = '/';
        }

        if ($add == 'right' || $add == 'all') {
            $right = '/';
        }

        return $left.$path.$right;
    }

    /**
     * 字符串长度(中英文都算一个字)
     * @param	$string: 原始字符串
     * @param	$encoding: 编码方式
     * @return	int
     */
    public static function str_len($string, $encoding = 'utf-8') {
        if (function_exists('mb_strlen')) {
            return mb_strlen($string, $encoding);
        } else {
            return strlen(utf8_decode($string));
        }
    }

    /**
     * 截取字符串(中英文都算一个字)
     * @param	$string: 原始字符串
     * @param	$start: 截取开始的位置
     * @param	$length: 截取长度
     * @param	$encoding: 编码方式
     * @return	string
     * @copyright	http://phputf8.sourceforge.net
     */
    public static function sub_str($string, $start, $length = null, $encoding = 'utf-8') {
        if (function_exists('mb_substr')) {
            return mb_substr($string, $start, (is_null($length) ? self::str_len($string) : $length), $encoding);
        } else {
            if ($length === 0) {
                return '';
            }
            if ($start < 0 && $length < 0 && $length < $start) {
                return '';
            }
            if ($start < 0) {
                $stringlen = self::str_len($string);
                $start = $stringlen + $start;
                if ($start < 0) {
                    $start = 0;
                }
            }
            $Op = $Lp = '';
            if ($start > 0) {
                $Ox = (int)($start/65535);
                $Oy = $start%65535;
                if ($Ox) {
                    $Op = '(?:.{65535}){'.$Ox.'}';
                }
                $Op = '^(?:'.$Op.'.{'.$Oy.'})';
            } else {
                $Op = '^';
            }
            if (is_null($length)) {
                $Lp = '(.*)$';
            } else {
                if (!isset($stringlen)) {
                    $stringlen = strlen(utf8_decode($string));
                }
                if ($start > $stringlen) {
                    return '';
                }
                if ($length > 0) {
                    $length = min($stringlen - $start, $length);
                    $Lx = (int)( $length / 65535 );
                    $Ly = $length % 65535;
                    if ($Lx) {
                        $Lp = '(?:.{65535}){'.$Lx.'}';
                    }
                    $Lp = '('.$Lp.'.{'.$Ly.'})';
                } else if ($length < 0) {
                    if ( $length < ($start - $stringlen) ) {
                        return '';
                    }
                    $Lx = (int)((-$length) / 65535);
                    $Ly = (-$length) % 65535;
                    if ($Lx) {
                        $Lp = '(?:.{65535}){'.$Lx.'}';
                    }
                    $Lp = '(.*)(?:'.$Lp.'.{'.$Ly.'})$';
                }
            }
            if (!preg_match('#'.$Op.$Lp.'#us',$string, $match)) {
                return '';
            }
            return $match[1];
        }
    }

    /**
     * 截取字符串(中文算两个字)
     * @param	$string: 原始字符串
     * @param	$length: 截取长度
     * @param	$dot: 省略符
     * @return	string
     */
    public static function cut_str($string, $length, $dot = ' ...') {
        if(strlen($string) <= $length) {
            return $string;
        }

        if (strpos($string, '<') !== FALSE) {
            $string = self::clear_html($string);
        }

        $string = str_replace(array('&amp;', '&quot;'), array('&', '"'), $string);
        $strcut = '';

        if(strtolower(BBC::get_config('output.charset')) == 'utf-8') {

            $n = $tn = $noc = 0;
            while($n < strlen($string)) {

                $t = ord($string[$n]);
                if($t == 9 || $t == 10 || (32 <= $t && $t <= 126)) {
                    $tn = 1; $n++; $noc++;
                } elseif(194 <= $t && $t <= 223) {
                    $tn = 2; $n += 2; $noc += 2;
                } elseif(224 <= $t && $t < 239) {
                    $tn = 3; $n += 3; $noc += 2;
                } elseif(240 <= $t && $t <= 247) {
                    $tn = 4; $n += 4; $noc += 2;
                } elseif(248 <= $t && $t <= 251) {
                    $tn = 5; $n += 5; $noc += 2;
                } elseif($t == 252 || $t == 253) {
                    $tn = 6; $n += 6; $noc += 2;
                } else {
                    $n++;
                }

                if($noc >= $length) {
                    break;
                }

            }
            if($noc > $length) {
                $n -= $tn;
            }

            $strcut = substr($string, 0, $n);

        } else {
            for($i = 0; $i < $length; $i++) {
                $strcut .= ord($string[$i]) > 127 ? $string[$i].$string[++$i] : $string[$i];
            }
        }

        $strcut = str_replace(array('&', '"'), array('&amp;', '&quot;'), $strcut);
        return $strcut.$dot;
    }

    /**
     * 去掉字符串中的html内容
     * @param	$string: 原始字符串
     * @return	string
     */
    public static function clear_html($string) {
        return preg_replace('/<(.[^>]*)>/i', '', $string);
    }

    /**
     * 生成指定长度的随机字符
     * @param	$length: 随机字符串的长度
     * @param	$numeric: 随即字符串是否只是数字
     * @return	string
     */
    public static function random($length, $numeric = 0) {
        $seed = base_convert(md5(microtime().$_SERVER['DOCUMENT_ROOT']), 16, $numeric ? 10 : 35);
        $seed = $numeric ? (str_replace('0', '', $seed).'012340567890') : ($seed.'zZ'.strtoupper($seed));
        $hash = '';
        $max = strlen($seed) - 1;
        for($i = 0; $i < $length; $i++) {
            $hash .= $seed{mt_rand(0, $max)};
        }
        return $hash;
    }

    /**
     * 同名参数的值以逗号隔开，用于数据库的 IN 操作
     * @param	$array: 一维数组
     * @return	string or 0
     */
    public static function implode($array, $delimiter = "'") {
        if(!empty($array)) {
            return $delimiter.implode($delimiter.','.$delimiter, $array).$delimiter;
        } else {
            return 0;
        }
    }

    /**
     * 参数是否是日期格式
     * @param	$ymd: 日期字符串
     * @param	$sep: 日期分隔符
     * @return	bool
     */
    public static function is_date($ymd, $sep = '-') {
        if(empty($ymd)) {
            return FALSE;
        }
        $tmp = explode($sep, $ymd);
        if (count($tmp) != 3) {
            return FALSE;
        }
        $tmp = array_map('intval', $tmp);
        list($year, $month, $day) = $tmp;
        return checkdate($month, $day, $year);
    }

    /**
     * 验证字符串是否是email格式
     * @param	$email: 要验证的字符串
     * @return	bool
     */
    public static function is_email($email) {
        return strlen($email) >= 6 && preg_match("/^[\w\-\.]+@[\w\-\.]+(\.\w+)+$/", $email);
    }

    /**
     * 是否是手机格式
     * @param	$cellphone: 手机号码
     * @return	bool
     */
    public static function is_cellphone($cellphone) {
        return preg_match('/^1[0-9]{10}$/', $cellphone);
    }

    /**
     * 日期转UNIX时间戳
     * @param	$date: 日期字符串
     * @return	int
     */
    public static function to_timestamp($date) {
        return function_exists('date_default_timezone_set') ? strtotime($date) - BBC::get_config('timezone') * 3600 : strtotime($date);
    }

    /**
     * 日期加减
     * @param	$date: 日期字符串
     * @param 	$days: 加减的天数
     * @return	string
     */
    public static function date_add($days, $date, $format = 'Y-m-d') {
        if (is_date($date)) {
            $date = to_timestamp($date);
        } else {
            $date = intval($date);
        }
        return self::date_format($date + ($days * 86400), $format);
    }

    /**
     * 格式化日期
     * @param	$time_stamp: 时间戳
     * @param 	$format: 格式
     * @return	string
     */
    public static function date_format($time_stamp, $format = 'Y-m-d') {
        return gmdate($format, $time_stamp + BBC::get_config('timezone') * 3600);
    }

    /**
     * 时间友好型提示风格化（即微博中的XXX小时前、昨天等等）
     *
     * 即微博中的 XXX 小时前、昨天等等, 时间超过 $time_limit 后返回按 out_format 的设定风格化时间戳
     *
     * @param  int
     * @param  int
     * @param  string
     * @param  array
     * @param  int
     * @return string
     */
    public static function date_friendly($timestamp, $time_limit = 604800, $out_format = 'Y-m-d H:i', $formats = null, $time_now = null)
    {
        if (!$timestamp)
        {
            return false;
        }

        if ($formats == null)
        {
            $formats = array(
                'YEAR' => ('%s 年前'),
                'MONTH' => ('%s 月前'),
                'DAY' => ('%s 天前'),
                'HOUR' => ('%s 小时前'),
                'MINUTE' => ('%s 分钟前'),
                'SECOND' => ('%s 秒前'),
            );
        }

        $time_now = $time_now == null ? time() : $time_now;
        $seconds = $time_now - $timestamp;

        if($seconds >= 0) {
            if ($seconds == 0)
            {
                $seconds = 1;
            }

            if (!$time_limit OR $seconds > $time_limit)
            {
                return date($out_format, $timestamp + BBC::get_config('timezone') * 3600);
            }

            $minutes = floor($seconds / 60);
            $hours = floor($minutes / 60);
            $days = floor($hours / 24);
            $months = floor($days / 30);
            $years = floor($months / 12);

            if ($years > 0)
            {
                $diffFormat = 'YEAR';
            }
            else
            {
                if ($months > 0)
                {
                    $diffFormat = 'MONTH';
                }
                else
                {
                    if ($days > 0)
                    {
                        $diffFormat = 'DAY';
                    }
                    else
                    {
                        if ($hours > 0)
                        {
                            $diffFormat = 'HOUR';
                        }
                        else
                        {
                            $diffFormat = ($minutes > 0) ? 'MINUTE' : 'SECOND';
                        }
                    }
                }
            }

            $dateDiff = null;

            switch ($diffFormat)
            {
                case 'YEAR' :
                    $dateDiff = sprintf($formats[$diffFormat], $years);
                    break;
                case 'MONTH' :
                    $dateDiff = sprintf($formats[$diffFormat], $months);
                    break;
                case 'DAY' :
                    $dateDiff = sprintf($formats[$diffFormat], $days);
                    break;
                case 'HOUR' :
                    $dateDiff = sprintf($formats[$diffFormat], $hours);
                    break;
                case 'MINUTE' :
                    $dateDiff = sprintf($formats[$diffFormat], $minutes);
                    break;
                case 'SECOND' :
                    $dateDiff = sprintf($formats[$diffFormat], $seconds);
                    break;
            }
        } else {
            $seconds = abs($seconds);
            $formats = array(
                'YEAR' => ('%s 年后'),
                'MONTH' => ('%s 月后'),
                'DAY' => ('%s 天后'),
                'HOUR' => ('%s 小时后'),
                'MINUTE' => ('%s 分钟后'),
                'SECOND' => ('%s 秒后'),
            );
            if (!$time_limit OR $seconds > $time_limit)
            {
                return date($out_format, $timestamp + BBC::get_config('timezone') * 3600);
            }

            $minutes = floor($seconds / 60);
            $hours = floor($minutes / 60);
            $days = floor($hours / 24);
            $months = floor($days / 30);
            $years = floor($months / 12);

            if ($years > 0)
            {
                $diffFormat = 'YEAR';
            }
            else
            {
                if ($months > 0)
                {
                    $diffFormat = 'MONTH';
                }
                else
                {
                    if ($days > 0)
                    {
                        $diffFormat = 'DAY';
                    }
                    else
                    {
                        if ($hours > 0)
                        {
                            $diffFormat = 'HOUR';
                        }
                        else
                        {
                            $diffFormat = ($minutes > 0) ? 'MINUTE' : 'SECOND';
                        }
                    }
                }
            }

            $dateDiff = null;

            switch ($diffFormat)
            {
                case 'YEAR' :
                    $dateDiff = sprintf($formats[$diffFormat], $years);
                    break;
                case 'MONTH' :
                    $dateDiff = sprintf($formats[$diffFormat], $months);
                    break;
                case 'DAY' :
                    $dateDiff = sprintf($formats[$diffFormat], $days);
                    break;
                case 'HOUR' :
                    $dateDiff = sprintf($formats[$diffFormat], $hours);
                    break;
                case 'MINUTE' :
                    $dateDiff = sprintf($formats[$diffFormat], $minutes);
                    break;
                case 'SECOND' :
                    $dateDiff = sprintf($formats[$diffFormat], $seconds);
                    break;
            }
        }
        return $dateDiff;
    }

    /**
     * 创建目录
     * @param	$dir: 要创建的目录
     * @param	$mode: 该目录的访问权限
     * @param	$make_index: 是否创建空index文件
     * @return	void
     */
    public static function check_dir($dir, $mode = 0777, $make_index = TRUE) {
        if(!is_dir($dir)) {
            self::check_dir(dirname($dir));
            $result = @mkdir($dir, $mode);
            if ($result === FALSE) {
                return FALSE;
            }
            if($make_index) {
                touch($dir.'/index.html');
                @chmod($dir.'/index.html', 0777);
            }
        }
        return TRUE;
    }

    /**
     * 获取文件扩展名
     * @param	$filename
     * @return	string
     */
    public static function file_ext($filename) {
        $path_info = pathinfo($filename);
        return isset($path_info['extension']) ? strtolower($path_info['extension']) : '';
    }

    /**
     * 脚本执行时间和数据库查询次数
     * @return	array
     */
    public static function debug_info($db_server = 'default') {
        return array(number_format(array_sum(explode(' ', microtime())) - START_TIME, 4), BBC::db($db_server)->query_num);
    }

    /**
     * 根据记录总数和每页显示条数返回翻页的html以及对当前页码进行验证
     * @param	$total: 记录总数
     * @param	$limit: 每页显示条数
     * @param	$page: 当前页码
     * @param	$url: 翻页的url
     * @param	$url: 翻页的url后面带参数
     * @return	array
     */
    public static function get_pages($total, $limit, $page, $param = '') {
        $max_page = ceil($total / $limit);

        if ($max_page < 1) {
            $max_page = 1;
        }
        if ($page > $max_page) {
            $page = $max_page;
        }

        if ($max_page < 2) {
            return array($page, '');
        }

        $output = '';
        $list_num = 6;
        $offset = 2;

        if ($max_page < $list_num) {
            $from = 1;
            $to = $max_page;
        } else {
            $from = $page - $offset;
            $to = $from + $list_num - 1;
            if ($from < 1) {
                $to = $page + 1 - $from;
                $from = 1;
                if ($to - $from < $list_num) {
                    $to = $list_num;
                }
            } elseif ($to > $max_page) {
                $from = $max_page - $list_num + 1;
                $to = $max_page;
            }
        }

        $output = ($page - $offset > 1 && $max_page >= $page ? '<a href="?page=1'.$param.'" class="first" target="_self">1 ...</a>' : '').
            ($page > 1 ? '<a href="?page='.($page - 1).'" class="prev" target="_self">&lsaquo;&lsaquo;</a>' : '');

        for ($i = $from; $i <= $to; $i++) {
            $output .= $i == $page ? '<strong>'.$i.'</strong>' : '<a href="?page='.$i.$param.'" target="_self">'.$i.'</a>';
        }

        $output .= ($page < $max_page ? '<a href="?page='.($page + 1).$param.'" class="next" target="_self">&rsaquo;&rsaquo;</a>' : '').
            ($to < $max_page ? '<a href="?page='.$max_page.$param.'" class="last" target="_self">... '.$max_page.'</a>' : '').
            ($max_page > $page ? '<kbd><input type="text" size="3" onkeydown="if(event.keyCode==13) {self.window.location=\'?page=\'+this.value+\''.$param.'\'; return false;}" /></kbd>' : '');

        $output = !empty($output) ? '<div class="pages_btns"><div class="pages">'.$output.'</div></div>' : '';

        return array($page, $output);
    }

    /**
     * @param $data
     * @return mixed
     */
    public static function null_to_space($data,$default=false,$badword=false){
        self::$default = $default;
        self::$default_badword = $badword;
        array_walk_recursive($data,'QW::process');
        return $data;
    }
    public static function process (&$val) {
        if($val===null){
            if(self::$default) {
                $val='0';
            } else {
                $val='';
            }
        }
        if(self::$default_badword) {
            require './framework/fun/badword.src.php';
            $val= strtr($val,$badword);
        }
        unset($val);
    }
    /*//黑词过滤
    public static function rm_badword($data){
        if(is_array($data)) {
            array_walk_recursive($data,'QW::process_badword');
        } else {
//            require_once './framework/fun/badword.src.php';
            return strtr($data,$badword);
        }
        return $data;
    }
    public static function process_badword (&$v) {
//        require_once './framework/fun/badword.src.php';
        $v = strtr($v,$badword);
        unset($v);
    }*/

    public static function is_mobile($ignore_cookie = false)
    {
        $user_agent = strtolower($_SERVER['HTTP_USER_AGENT']);

        if (preg_match('/playstation/i', $user_agent) OR preg_match('/ipad/i', $user_agent) OR preg_match('/ucweb/i', $user_agent))
        {
            return false;
        }

        if (preg_match('/iemobile/i', $user_agent) OR preg_match('/mobile\ssafari/i', $user_agent) OR preg_match('/iphone\sos/i', $user_agent) OR preg_match('/android/i', $user_agent) OR preg_match('/symbian/i', $user_agent) OR preg_match('/series40/i', $user_agent))
        {
            return true;
        }

        return false;
    }
}