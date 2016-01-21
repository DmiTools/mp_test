<?php

require('vendor/autoload.php');

if(!isset($argv[1])){
    $argv[1] = '';
}
$topic = strtolower(trim($argv[1]));

$dbh = new \PDO('mysql:host=localhost;dbname=#SECRET#', '#SECRET#', '#SECRET#');
$manager = new \Lib\MigrationManager($dbh);
try
{
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
