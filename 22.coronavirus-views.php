<?php

if(php_sapi_name() !== 'cli') {
    exit;
}

$_SERVER = [
    "SERVER_PROTOCOL" => "HTTP/1.1",
    "HTTP_HOST"       => "knife.media",
    "SERVER_NAME"     => "knife.media",
    "REQUEST_URI"     => "/",
    "REQUEST_METHOD"  => "GET"
];

define('WP_CACHE', false);
define('WP_DEBUG', true);
define('WP_USE_THEMES', false);

require( __DIR__ . '/../wordpress/wp-load.php');


if(!defined('KNIFE_VIEWS')) {
    exit('Undefined db settings');
}

function median($arr) {
 sort($arr);

 $count = count($arr);
 $middle = floor($count / 2);

 if($count % 2) {
     return $arr[$middle];
 }

 return ($arr[$middle-1] + $arr[$middle]) / 2;
}

{
    $posts = get_posts([
        'posts_per_page' => -1,
        'post_status' => 'publish',
        'post_type' => ['post'],
        'orderby' => 'id',
        'order' => 'desc',
        'date_query' => [
            [
                'year' => '2020'
            ],
        ],
        'fields' => 'ids'
    ]);

    // Mix with default values
    $conf = KNIFE_VIEWS;

    // Create custom db connection
    $db = new wpdb($conf['user'], $conf['password'], $conf['name'], $conf['host']);

    $ids = implode(',', $posts);

    // Get data
    $data = $db->get_results("SELECT * FROM posts WHERE post_id IN ({$ids}) ORDER BY pageviews DESC");

    // Slice data
#   $data = array_slice($data, 100, -500);

    // Compiled data
    $views = [];

    foreach($data as $item) {
        if(empty($item->pageviews)) {
            continue;
        }

        // Get category
        $category = get_the_category($item->post_id);

        // Skip news
        if(empty($category[0]) || in_array($category[0]->slug, ['news'])) {
            continue;
        }

        $group = 'other';

        // Get format
        $term = has_term('coronavirus', 'special', $item->post_id);

        if ($term) {
            $group = 'coronavirus';
        }

        $views[$group][] = $item->pageviews;
    }

    $groups = array_keys($views);

    foreach($groups as $g) {
        $mid = round(array_sum($views[$g]) / count($views[$g]), 2);
        $med = median($views[$g]);

        echo "$g: $mid / $med \n";
    }
}
