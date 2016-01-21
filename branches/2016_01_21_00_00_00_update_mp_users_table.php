<?php

use Lib\Migration;

class UpdateMpUsers extends Migration
{
    public function up()
    {
        $this->db->exec('
          ALTER TABLE mp_users ADD COLUMN active int(1) default 1;
        ');
    }
    public function down()
    {
        $this->db->exec('
          ALTER TABLE mp_users DROP COLUMN active;
        ');
    }
}
?>