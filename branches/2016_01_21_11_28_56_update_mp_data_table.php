<?php
use Lib\Migration;
class UpdateMpData extends Migration
{
    public function up()
    {
        $this->db->exec('ALTER TABLE mp_datas ADD COLUMN author VARCHAR(50) default \'nonamed\'');
    }
    public function down()
    {
        $this->db->exec('ALTER TABLE author DROP COLUMN author');
    }
} 
?>