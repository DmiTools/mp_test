<?php
use Lib\Migration;
class CreateMpData extends Migration
{
    public function up()
    {
        $this->db->exec('CREATE TABLE mp_datas (id int(10) not null, data text not null, primary key(id))');
    }
    public function down()
    {
        $this->db->exec('DROP TABLE mp_datas');
    }
} 
?>