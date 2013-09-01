<?php
require '../bootstrap.php';

// Prepare app
$app = new \Slim\Slim(array(
    'templates.path' => '../templates',
    'log.level' => \Slim\Log::ERROR,
    'log.enabled' => true,
    'log.writer' => new \Slim\Extras\Log\DateTimeFileWriter(array(
        'path' => '../logs',
        'name_format' => 'y-m-d'
    ))
));

// Prepare view
$app->view(new \Slim\Views\Twig());
$app->view->parserOptions = array(
    'charset' => 'utf-8',
    'cache' => realpath('../templates/cache'),
    'auto_reload' => true,
    'strict_variables' => false,
    'autoescape' => true
);
$app->view->parserExtensions = array(new \Slim\Views\TwigExtension());

// Define routes
$app->get('/', function () use ($app) {
    $page = 1;
    $per = 10;

    $total = Model::factory('ComicModel')->count();
    $comics = Model::factory('ComicModel')
        ->order_by_asc('updated')
        ->offset(($page - 1) * $per)
        ->limit($per)
        ->find_many();

    $app->render('index.html', array(
        'comics' => $comics,
        'current' => '',
    ));
});

// Run app
$app->run();
