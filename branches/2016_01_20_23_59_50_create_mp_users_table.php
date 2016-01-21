<?php

use Lib\Migration;

class CreateMpUsers extends Migration
{
    public function up()
    {
        $this->db->exec('
          CREATE TABLE mp_users(
            id int(5) not null auto_increment,
            login varchar(50) not null unique,
            email varchar(100) not null,
            password varchar(100) not null,
            primary key(id)
          );
        ');
    }
    public function down()
    {
        $this->db->exec('DROP TABLE mp_users');
    }
}
?>