<?php

namespace Lib;

class MigrationManager
{
    const DIR_PATH = '#SECRET#';
    const PREG_VALID_FNAME = "#\d{4}\_\d{2}\_\d{2}\_\d{2}\_\d{2}\_\d{2}\_(\w+)\_table\.php#";
    const MIGRATIONS_TABLE = 'mp_migrations';

    protected $db;
    protected $applied;
    
    public function __construct($db)
    {
        $this->db = $db;       
    }
    public function init()
    {
        $result = $this->db->query("select * from ".self::MIGRATIONS_TABLE." order by date_apply desc, id desc");
        if(!$result){
            throw new \Exception('Bad initialization of MigrationManager object (error: '.implode(';',$this->db->errorInfo()).')');
        }
        $this->applied = [];
        foreach($result as $fetch){
            $this->applied[] = $fetch['filename'];
        }
    }
    protected function testMigrationFileName($filename)
    {
        return preg_match_all(
          self::PREG_VALID_FNAME,
          $filename,
          $matched,
          PREG_SET_ORDER
        );
    }
    protected function getClassByFileName($filename, $strip_date = false)
    {
        $filename = trim(str_replace('_table.php', '', $filename));
        if(empty($filename)){
            throw new Exception('Filename is not defined');
        }
        $filename_parts = explode('_', $filename);
        if($strip_date === true){
            $filename_parts = array_slice($filename_parts, 6);
        }
        array_walk($filename_parts, function(&$item){
            $item = UcFirst($item);
        });
        return implode('', $filename_parts);
    }
    protected function getFileList()
    {
        $dir = new \DirectoryIterator(self::DIR_PATH);
        $filelist = [];
        foreach($dir as $file){
            if($this->testMigrationFileName($file)){
                $filelist[] = (string)$file;
            }
        }
        sort($filelist);
        return $filelist;
    }
    public function create($filename)
    {
        $now = date('Y_m_d_H_i_s');
        $fileob = new \SplFileObject(
          self::DIR_PATH.$now.'_'.$filename.'.php',
          'w'
        );
        $classname = $this->getClassByFileName(str_replace('_table', '', $filename));
        $fileob->flock(LOCK_EX);
        $fileob->fwrite(
"<?php
use Lib\Migration;
class $classname extends Migration
{
    public function up()
    {
    }
    public function down()
    {
    }
} 
?>"
);
        $fileob->flock(LOCK_UN);
        return true;
    }
    protected function includeFileWithClass($path)
    {
        $fp = tmpfile();
        if(!$fp){
            throw new Exception('Error of filesystem in '.__FUNCTION__);
        }
        $tmpfname = stream_get_meta_data($fp)['uri'];
        $content = file_get_contents($path);
        $namespace_name = str_replace('/', '_', substr($tmpfname, 1, strlen($tmpfname)));
        $content = str_replace('<?php', "<?php\nnamespace $namespace_name;\n\n", $content);
        fwrite($fp, $content);
        require($tmpfname);
        return $namespace_name;
    }
    protected function handleMethod($classname, $instance, $method)
    {
        $invoker = new \ReflectionMethod($classname, $method);
        $invoker->invoke($instance);
    }
    public function apply($numbers = 0)
    {
        if($numbers < 0){
            $numbers = 0;
        }
        $filelist = $this->getFileList($numbers);
        $i = 0;
        foreach($filelist as $file){
            if(array_search($file, $this->applied) === false){
                
                if($numbers > 0 && $i >= $numbers){
                    break;
                }
                
                $namespace_name = $this->includeFileWithClass(self::DIR_PATH.$file);
                $classname = '\\'.$namespace_name.'\\'.$this->getClassByFileName($file, true);
                
                $class_desc = new \ReflectionClass($classname);
                $instance = $class_desc->newInstance($this->db);
                
                $this->handleMethod($classname, $instance, 'up');
                
                $stmt = $this->db->prepare('insert into mp_migrations (filename, date_apply) values (?, ?)');
                $ex = $stmt->execute([$file, date('Y-m-d H:i:s')]);
                
                if($ex === false){
                    $this->handleMethod($classname, $instance, 'down');
                    throw new \Exception('Failed to store new migration history '.$file.' (error: '.implode(';',$this->db->errorInfo()).')');
                }
                
                array_unshift($this->applied, $file);
                echo $file." succesfully applied\n";
                $i++;
            }
        }
    }
    public function revert($numbers = 0)
    {
        if($numbers < 0){
            $numbers = 0;
        }
        $i = 0;
        foreach($this->applied as $file){
            if($numbers > 0 && $i >= $numbers){
                break;
            }
            if(file_exists(self::DIR_PATH.$file)){
                
                $namespace_name = $this->includeFileWithClass(self::DIR_PATH.$file);
                
                $namespace_name = $this->includeFileWithClass(self::DIR_PATH.$file);
                $classname = '\\'.$namespace_name.'\\'.$this->getClassByFileName($file, true);
                
                $class_desc = new \ReflectionClass($classname);
                $instance = $class_desc->newInstance($this->db);
                
                $this->handleMethod($classname, $instance, 'down');
                
                $stmt = $this->db->prepare('delete from mp_migrations where filename = ?');
                $ex = $stmt->execute([$file]);
                
                if($ex === false){
                    $this->handleMethod($classname, $instance, 'up');
                    throw new \Exception('Failed to delete migration history '.$file.' (error: '.implode(';',$this->db->errorInfo()).')');
                }
                
                array_shift($this->applied);
                echo $file." succesfully reverted\n";
            }
            $i++;
        }
    }
}
?>
