<?php
/**
 * 抓取LeTV动漫类型
 */
require __DIR__ . '/../bootstrap.php';

use ThauEx\SimpleHtmlDom\SHD;

$html = SHD::fileGetHtml("http://so.letv.com/list/c3_t-1_a-1_y-1_f-1_at-1_o1_i-1_p.html");

foreach ($html->find('.soydl') as $soydl) {
    if (trim($soydl->find('dt', 0)->plaintext) == '影片类型') {

        foreach ($soydl->find('dd a') as $type) {
            $name = trim($type->plaintext);
            if ($name == '全部') continue;

            $type = Model::factory('TypeModel')
                ->where('name', $name)
                ->find_one();
            if (!$type) {
                $type = Model::factory('TypeModel')->create();
                $type->name = $name;
                $type->save();
            }
        }

        break;
    }
}

echo 'Done.' . PHP_EOL;