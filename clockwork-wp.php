<?php
/*
 * Plugin Name: Clockwork for WordPress
 * Description: WordPress plugin for Clockwork.
 * Author: Brian Retterer
 * Version: 1.0.0
 * Author URI: http://brianretterer.com
 */

require __DIR__.'/vendor/autoload.php';

$clockwork = new Clockwork\Clockwork();
$clockwork_start = null;
$clockwork_end = null;

function clockwork_wp_init() {
  define('SAVEQUERIES', true);
  global $clockwork;
  global $clockwork_start;
  $clockwork_start = microtime(true);
  header("X-Clockwork-Id: " . $clockwork->getRequest()->id);
  header("X-Clockwork-Version: " . $clockwork::VERSION);

  $clockwork->addDataSource(new Clockwork\DataSource\PhpDataSource());

  $clockwork->setStorage(new Clockwork\Storage\FileStorage(__DIR__."/tmp/"));

  $clockwork->notice('Application Started');


}

function clockwork_wp_cleanfiles() {
  $files = array();
  if ($handle = opendir(__DIR__."/tmp/")) {
    while (false !== ($file = readdir($handle))) {
       if ($file != "." && $file != "..") {
          if(strpos($file, '.json')) {
            unlink(__DIR__."/tmp/".$file);
          }
       }
    }
    closedir($handle);


  
  }
}


function clockwork_wp_shutdown() {
  global $clockwork;
  global $wpdb;
  global $clockwork_end;
  global $clockwork_start;
  $clockwork_end = microtime(true);
  $request = $clockwork->getRequest();
  $queries = array();
  foreach($wpdb->queries as $query) {
    $queries[] = ['query'=>$query[0],'duration'=>$query[1]];
  }

  $request->databaseQueries = $queries;
  $request->timelineData['total'] = ['start'=>$clockwork_start,'end'=>$clockwork_end,'duration'=>$clockwork_end - $clockwork_start,'description'=>'Total execution time.'];
  $clockwork->notice('Application Shutdown');
  $clockwork->resolveRequest();
  $clockwork->storeRequest();


}


function clockwork_url_handler() {
  $request = $_SERVER['REQUEST_URI'];


  if(preg_match('/\/__clockwork\/.*/',$request)) {
    $request = explode('/',$request);

    $storage = new Clockwork\Storage\FileStorage(__DIR__."/tmp/");

    $data = $storage->retrieve($request[2]);

    print $data->toJson();
  clockwork_wp_cleanfiles();

    exit();
  }
}


if(WP_DEBUG) {
  add_action('init', 'clockwork_wp_init');
  add_action('shutdown', 'clockwork_wp_shutdown');
  add_action('parse_request', 'clockwork_url_handler');
} else {
  function my_admin_notice() {
     echo '<div class="updated"><p>';

      echo __('You have the Clockwork WP plugin enabled but need to turn debug mode on to make it work.');

      echo "</p></div>";

  }
add_action( 'admin_notices', 'my_admin_notice' );
}

