<?php
/**
 * 抓取动漫和剧集的封面图片
 */
require __DIR__ . '/../bootstrap.php';

$per = 100;

// 动漫
/*$total = Model::factory('ComicModel')
    ->where_equal('cover', '')
    ->count();
$total_round = ceil($total / $per);

for ($i = 1; $i <= $total_round; $i++) {
    $comics = Model::factory('ComicModel')
        ->where_equal('cover', '')
        ->order_by_asc('id')
        ->offset(($i - 1) * $per)
        ->limit($per)
        ->find_many();
    foreach ($comics as $comic) {
        logger('cover', "抓取 {$comic->name} 的封面");
        $cover_path = getCoverFilePath('comic', $comic->id, $comic->letv_cover_url);
        fetchSaveCoverImage($comic, $cover_path);
    }
}*/

// 剧集
$total = Model::factory('EpisodeModel')
    ->where_equal('cover', '')
    ->count();
$total_round = ceil($total / $per);

for ($i = 1; $i <= $total_round; $i++) {
    $episodes = Model::factory('EpisodeModel')
        ->where_equal('cover', '')
        ->order_by_asc('id')
        ->offset(($i - 1) * $per)
        ->limit($per)
        ->find_many();

    $comic_ids = array();
    foreach ($episodes as $episode) {
        $comic_ids[$episode->comic_id] = $episode->comic_id;
    }
    $comics = Model::factory('ComicModel')
        ->where_in('id', $comic_ids)
        ->find_many();
    $keyed_comics = array();
    foreach ($comics as $comic) {
        $keyed_comics[$comic->id] = $comic;
    }

    foreach ($episodes as $episode) {
        logger('cover', "抓取 {$keyed_comics[$episode->comic_id]->name} - {$episode->name} 的封面");
        $cover_path = getCoverFilePath('episode', $episode->id, $episode->letv_cover_url);
        fetchSaveCoverImage($episode, $cover_path);
    }
}
