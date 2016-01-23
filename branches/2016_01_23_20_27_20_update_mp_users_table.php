<?php
use Lib\Migration;
class UpdateMpUsers extends Migration
{
    public function up()
    {
        $this->db->exec('alter table mp_users add column phone varchar(100)');
    }
    public function down()
    {
        $this->db->exec('alter table mp_users drop column phone');
    }
} 
?>