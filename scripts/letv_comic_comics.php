<?php
/**
 * 抓取LeTV动漫列表
 */
require __DIR__ . '/../bootstrap.php';

use ThauEx\SimpleHtmlDom\SHD;

$options = getopt('s:e:h');

// 获取开始和结束的页面
if (isset($options['s'])) {
    if (is_numeric($options['s']) && intval($options['s']) > 0) {
        $start = intval($options['s']);
    } else {
        echo 'Invalid start pager', PHP_EOL;
        die;
    }
} else {
    $start = getComicListMaxPager();
}

if (isset($options['e'])) {
    if (is_numeric($options['e']) && intval($options['e']) > 0 && intval($options['e']) <= $start) {
        $end = intval($options['e']);
    } else {
        echo 'Invalid end pager', PHP_EOL;
        die;
    }
} else {
    $end = 1;
}

// 抓取动漫列表页面
$url_format = 'http://so.letv.com/list/c3_t-1_a-1_y-1_f-1_at-1_o1_i-1_p%d.html';
for ($i = $start; $i >= $end; $i--) {
    logger('list', "抓取第 $i 页");
    logger('memory', memory_get_usage(true));
    $str_html = getContent(sprintf($url_format, $i));
    $shd_html = SHD::strGetHtml($str_html);

    $comics = parseComicList($shd_html);

    // 解析动漫详细信息
    foreach ($comics as $comic) {
        logger('detail', "抓取 {$comic->name} 的详情");
        logger('memory', memory_get_usage(true));
        logger('cache', md5($comic->letv_url));
        $str_html = getContent($comic->letv_url);
        $shd_html = SHD::strGetHtml($str_html);

        parseComicDetail($comic, $shd_html);
    }
}


echo 'Done.', PHP_EOL;

