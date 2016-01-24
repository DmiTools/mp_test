<?php

require('vendor/autoload.php');

if(!isset($argv[1])){
    $argv[1] = '';
}
$topic = strtolower(trim($argv[1]));

try
{
    $env = new SplFileObject('.env', 'r');
      
    foreach($env as $nv){
        $nv = str_replace("\n", "", $nv);
        $nv_list = explode('=', $nv);
        if(isset($nv_list[0]) && isset($nv_list[1])){
            putenv($nv_list[0].'='.$nv_list[1]);
        }
    }
    
    $pdo_string = 'mysql:host='.getenv('db_host').';dbname='.getenv('db_name');
    
    $dbh = new \PDO($pdo_string, getenv('db_user'), getenv('db_password'));
    
    $manager = new \Lib\MigrationManager($dbh, getenv('dir_path'));
    $manager->init();
    
    switch($topic)
    {
        case 'create':
            $filename = strtolower(trim($argv[2]));
            if(strlen($filename) <= 0){
                throw new Exception('Empty filename for new migration file');
            }
            $manager->create($filename);
        break;
        case 'apply':
            if(!isset($argv[2])){
                $argv[2] = '';
            }
            $numbers = intval(trim($argv[2]));
            if($numbers <= 0){
                $numbers = 0;
            }
            $manager->apply($numbers);
        break;
        case 'revert':
            if(!isset($argv[2])){
                $argv[2] = '';
            }
            $numbers = intval(trim($argv[2]));
            if($numbers <= 0){
                $numbers = 0;
            }
            $manager->revert($numbers);
        break;
        case 'status':
            $manager->status();
        break;
        default:
            throw new Exception("Wrong parameter $topic");
        break;
    }
}
catch(Exception $ex)
{
    echo "\n{$ex->getMessage()}\n\n";
}
?>
