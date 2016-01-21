<?php
namespace Lib;
abstract class Migration
{
    protected $db;
    public function __construct($db)
    {
        $this->db = $db;
    }
    abstract function up();
    abstract function down();
}
?>
