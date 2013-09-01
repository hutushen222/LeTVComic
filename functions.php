<?php

function str_start_with($needle, $haystack) {
    return !strncmp($haystack, $needle, strlen($needle));
}

function str_end_with($needle, $haystack) {
    $length = strlen($needle);
    if ($length == 0) {
        return true;
    }

    return (substr($haystack, -$length) === $needle);
}

function orm_transaction_start($connection_name = ORM::DEFAULT_CONNECTION) {
    ORM::raw_execute('SET autocommit = 0', NULL, $connection_name);
    ORM::raw_execute('START TRANSACTION;', NULL, $connection_name);
}

function orm_transaction_commit($connection_name = ORM::DEFAULT_CONNECTION) {
    ORM::raw_execute('COMMIT', NULL, $connection_name);
    ORM::raw_execute('SET autocommit = 1', NULL, $connection_name);
}

function orm_transaction_rollback($connection_name = ORM::DEFAULT_CONNECTION) {
    ORM::raw_execute('ROLLBACK',NULL, $connection_name);
    ORM::raw_execute('SET autocommit = 1', NULL, $connection_name);
}

/**
 * 抓取网页内容
 *
 * @param $url
 * @param null $proxy
 * @param int $hours
 * @param int $timeout
 *
 * @return string
 */
function getContent($url, $proxy = null, $hours = 24, $timeout = 10) {
    $file = \ThauEx\SimpleHtmlDom\SHD::$fileCacheDir . DIRECTORY_SEPARATOR . md5($url);

    if(file_exists($file)) {
        $currentTime = time();
        $expireTime = $hours * 60 * 60;
        $file_time = filemtime($file);

        if ($currentTime - $expireTime < $file_time) {
            return file_get_contents($file);
        }
    }

    $context = null;
    if ($proxy) {
        $opts = array(
            'http'=>array(
                'method'=>"GET",
                'proxy' => 'tcp://' . $proxy->ip . ':' . $proxy->port,
                'timeout' => $timeout,
            )
        );
        $context = stream_context_create($opts);
    }

    $content = file_get_contents($url, false, $context);
    $content .= '<!-- cached: ' . time() . ' -->';
    file_put_contents($file, $content);

    return $content;
}

/**
 * 日志接口
 *
 * @param $type
 * @param $message
 */
function logger($type, $message) {
    echo sprintf('%s - [%s] - %s', date('Y-m-d H:i:s'), $type, $message), PHP_EOL;
}

/**
 * 检查代理可用性
 *
 * @param $proxy
 * @param int $timeout
 *
 * @return mixed
 */
function checkProxy($proxy, $timeout = 5) {
    $available = ProxyModel::NOT_AVAILABLE;

    // Create a stream
    $opts = array(
        'http'=>array(
            'method'=>"GET",
            'proxy' => 'tcp://' . $proxy->ip . ':' . $proxy->port,
            'timeout' => $timeout,
        )
    );
    $context = stream_context_create($opts);

    // Open the file using the HTTP headers set above
    $content = file_get_contents('http://ifconfig.me/ip', false, $context);
    if (trim($content) == $proxy->ip) {
        $available = ProxyModel::AVAILABLE;
        $proxy->available = ProxyModel::AVAILABLE;
        $proxy->updated = date('Y-m-d H:i:s');
        $proxy->save();
    } elseif ($proxy->available == ProxyModel::AVAILABLE) {
        $proxy->available = ProxyModel::NOT_AVAILABLE;
        $proxy->updated = date('Y-m-d H:i:s');
        $proxy->save();
    }

    return $available;
}


/**
 * 解析动漫列表页面
 *
 * @param $shd_html
 */
function parseComicList($shd_html)
{
    $comics = array();
    foreach ($shd_html->find('.info2_box') as $shd_info) {
        $shd_comic_name = $shd_info->find('.info_dl .tit h1 a', 0);
        preg_match('/\/(\d+)\.html$/', $shd_comic_name->href, $matches);
        $letv_id = $matches[1];

        $comic = Model::factory('ComicModel')
            ->where('letv_id', $letv_id)
            ->find_one();

        if (!$comic) {
            $comic = Model::factory('ComicModel')->create();

            $comic->name = trim($shd_comic_name->plaintext);
            $comic->year = trim($shd_info->find('.info_dl .year_dl a', 0)->plaintext);
            $comic->letv_id = $letv_id;
            $comic->letv_url = $shd_comic_name->href;
            $comic->letv_cover_url = $shd_info->find('.imgA img', 0)->src;

            $comic->created = date('Y-m-d H:i:s');
            $comic->updated = $comic->created;

            $comic->save();
        }
        $comics[$comic->id] = $comic;
    }
    return $comics;
}

/**
 * 获得LeTV动漫的最大页数
 *
 * @return int
 */
function getComicListMaxPager() {
    $max = 1;
    $html = \ThauEx\SimpleHtmlDom\SHD::fileGetHtml("http://so.letv.com/list/c3_t-1_a-1_y-1_f-1_at-1_o1_i-1_p.html");
    foreach ($html->find('.page a') as $item) {
        $item_text = trim($item->plaintext);
        if (is_numeric($item_text) && intval($item_text) > $max) {
            $max = intval($item_text);
        }
    }
    return $max;
}

/**
 * 解析动漫详情页面
 *
 * @param $comic
 * @param $shd_html
 */
function parseComicDetail($comic, $shd_html)
{
    try {
        orm_transaction_start();

        // 已完结
        $shd_status = $shd_html->find('.info .text .i-t', 0);
        if ($shd_status && preg_match('/^共(.+)集$/', trim($shd_status->plaintext))) {
            $comic->complete = ComicModel::COMPLETE;
        }

        $shd_info = $shd_html->find('.comic .intro .textInfo', 0);

        // 原作 & 监督
        $shd_original_sv = $shd_info->find('.p1');
        if ($shd_original_sv) {
            foreach ($shd_original_sv as $shd_item) {
                $text = trim($shd_item->plaintext);
                if (str_start_with('原作', $text)) {
                    $comic->original = trim($shd_item->find('a', 0)->plaintext);
                }

                if (str_start_with('监督', $text)) {
                    $comic->supervision = trim($shd_item->find('a', 0)->plaintext);
                }
            }
        }

        // 声优
        $shd_seiyuus = $shd_info->find('.p2 a');
        if ($shd_seiyuus) {
            foreach ($shd_seiyuus as $shd_seiyuu) {
                $seiyuu_name = trim($shd_seiyuu->plaintext);
                $seiyuu = Model::factory('SeiyuuModel')
                    ->where('name', $seiyuu_name)
                    ->find_one();
                if (!$seiyuu) {
                    $seiyuu = Model::factory('SeiyuuModel')->create();
                    $seiyuu->name = $seiyuu_name;
                    $seiyuu->save();
                }

                $comic_seiyuu = Model::factory('ComicSeiyuuModel')
                    ->where('comic_id', $comic->id)
                    ->where('seiyuu_id', $seiyuu->id)
                    ->find_one();
                if (!$comic_seiyuu) {
                    $comic_seiyuu = Model::factory('ComicSeiyuuModel')->create();
                    $comic_seiyuu->comic_id = $comic->id;
                    $comic_seiyuu->seiyuu_id = $seiyuu->id;
                    $comic_seiyuu->save();
                }
            }
        }

        // 国家/地区
        $shd_region = $shd_info->find('.p3 a', 0);
        if ($shd_region) {
            $comic->region = trim($shd_region->plaintext);
        }

        // 类型
        $shd_types = $shd_info->find('.p5 a');
        if ($shd_types) {
            foreach ($shd_types as $shd_type) {
                $type_name = trim($shd_type->plaintext);
                $type = Model::factory('TypeModel')
                    ->where('name', $type_name)
                    ->find_one();
                if (!$type) {
                    $type = Model::factory('TypeModel')->create();
                    $type->name = $type_name;
                    $type->save();
                }

                $comic_type = Model::factory('ComicTypeModel')
                    ->where('comic_id', $comic->id)
                    ->where('type_id', $type->id)
                    ->find_one();
                if (!$comic_type) {
                    $comic_type = Model::factory('ComicTypeModel')->create();
                    $comic_type->comic_id = $comic->id;
                    $comic_type->type_id = $type->id;
                    $comic_type->save();
                }
            }
        }

        // 剧情介绍
        $shd_synopsis = $shd_info->find('.p7', 0);
        if ($shd_synopsis) {
            $comic->synopsis = trim($shd_synopsis->plaintext);
        }

        // 剧集列表
        $shd_episodes = $shd_html->find('.comic .listTab .list dl');
        if ($shd_episodes) {
            foreach ($shd_episodes as $shd_episode) {
                $shd_episode_name = $shd_episode->find('dd .p1 a', 0);
                preg_match('/\/(\d+)\.html$/', $shd_episode_name->href, $matches);
                $series_id = $matches[1];

                $episode = Model::factory('EpisodeModel')
                    ->where('comic_id', $comic->id)
                    ->where('series_id', $series_id)
                    ->find_one();
                if (!$episode) {
                    $episode = Model::factory('EpisodeModel')->create();
                    $episode->comic_id = $comic->id;
                    $episode->series_id = $series_id;


                    $episode->name = trim($shd_episode_name->plaintext);
                    $text_duration = trim($shd_episode->find('dt .time', 0)->plaintext);
                    list($minutes, $seconds) = explode(':', $text_duration);
                    $episode->duration = $minutes * 60 + $seconds;

                    $episode->letv_url = $shd_episode_name->href;
                    $episode->letv_cover_url = $shd_episode->find('dt img', 0)->src;

                    $episode->created = date('Y-m-d H:i:s');
                    $episode->updated = $episode->created;
                    $episode->save();
                }
            }
        }

        $comic->updated = date('Y-m-d H:i:s');
        $comic->save();
        orm_transaction_commit();
    } catch (Exception $e) {
        orm_transaction_rollback();
        echo $e->getMessage(), PHP_EOL;
    }
}