<?php
/**
 * 抓取代理服务器地址
 */
require __DIR__ . '/../bootstrap.php';

use ThauEx\SimpleHtmlDom\SHD;

$options = getopt('t:');

// 获取开始和结束的页面
if (isset($options['t'])) {
    if (is_numeric($options['t']) && intval($options['t']) > 0) {
        $timeout = intval($options['t']);
    } else {
        echo 'Invalid timeout number', PHP_EOL;
        die;
    }
} else {
    $timeout = 10;
}

// Create a stream
$opts = array(
    'http'=>array(
        'method'=>"GET",
        'header'=>"Accept-Language: en-US,en;q=0.8\r\n"
        . "Cookie: hl=zh; pv=7; userno=20130812-014928; from=direct; __utma=251962462.1677292567.1376270354.1378000012.1378004575.4; __utmb=251962462.5.10.1378004575; __utmc=251962462; __utmz=251962462.1376270354.1.1.utmcsr=(direct)|utmccn=(direct)|utmcmd=(none); __atuvc=2%7C33%2C0%7C34%2C0%7C35%2C6%7C36\r\n"
        . 'Referer: http://www.freeproxylists.net/zh/?c=&pt=&pr=HTTP&a%5B%5D=1&a%5B%5D=2&u=30'
        . 'User-Agent: Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/29.0.1547.57 Safari/537.36',
        'timeout' => 10,
    ),
);
$context = stream_context_create($opts);

// 获取最大的分页数
logger('proxy', '获取最大的分页数');
$max = 1;
$proxy_page = 'http://www.freeproxylists.net/zh/?pr=HTTP&a[]=1&a[]=2&u=50';
$content = SHD::getContent($proxy_page, false, $context);
$shd_html = SHD::strGetHtml($content);
foreach ($shd_html->find('.page a') as $item) {
    $item_text = trim($item->plaintext);
    if (is_numeric($item_text) && intval($item_text) > $max) {
        $max = intval($item_text);
    }
}

// 抓取代理服务器列表
logger('proxy', '抓取代理服务器列表');
$proxy_page_format = 'http://www.freeproxylists.net/zh/?pr=HTTP&a[]=1&a[]=2&u=50&page=%d';
for ($i = 1; $i <= $max; $i++) {
    $proxy_page = sprintf($proxy_page_format, $i);
    $content = SHD::getContent($proxy_page, false, $context);
    $shd_html = SHD::strGetHtml($content);

    foreach ($shd_html->find('.DataGrid tr') as $shd_row) {
        if ($shd_row->class == 'Caption') continue;
        $item = array();
        foreach ($shd_row->find('td') as $shd_field) {
            $item[] = $shd_field->innertext;
        }

        if (count($item) < 10) continue;

        preg_match('/IPDecode\("(.+)"\)/', $item[0], $matches);
        $ip = urlDecode($matches[1]);
        $item[0] = $ip;
        array_walk($item, function(&$value) {$value = trim($value);});

        $proxy = Model::factory('ProxyModel')
            ->where('ip', $item[0])
            ->where('port', $item[1])
            ->find_one();
        if (!$proxy) {
            $proxy = Model::factory('ProxyModel')->create();
            $proxy->ip = $item[0];
            $proxy->port = $item[1];
            $proxy->type = $item[3];
            $proxy->region = $item[4];
            $proxy->created = date('Y-m-d H:i:s');
            $proxy->updated = $proxy->created;
            $proxy->save();
        }
    }
}

// 检测代理服务器可用性
logger('proxy', '检测代理服务器可用性');
$proxies = Model::factory('ProxyModel')->find_many();
foreach ($proxies as $proxy) {
    logger('proxy', "检测 {$proxy->ip}");
    $available = checkProxy($proxy, $timeout);
    logger('proxy', "{$proxy->ip} " . ($available ? '可用' : '不可用'));
}

echo 'Done.';
