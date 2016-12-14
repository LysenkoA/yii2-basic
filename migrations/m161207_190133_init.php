<?php

use yii\db\Migration;

class m161207_190133_init extends Migration
{
    public function safeUp()
    {

        $tableOptions = null;
        if ($this->db->driverName === 'mysql') {
            $tableOptions = 'CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE=InnoDB';
        }
		
        $this->createTable('{{%user}}', [
            'id' => $this->primaryKey(),
            'username' => $this->string()->notNull()->unique(),
            'auth_key' => $this->string(32)->notNull(),
            'password_hash' => $this->string()->notNull(),
            'password_reset_token' => $this->string()->unique(),
			'email_confirm_token' => $this->string(),
            'email' => $this->string()->notNull()->unique(),
            'status' => $this->smallInteger()->notNull(),
            'role' => $this->smallInteger()->notNull(),
            'created_at' => $this->integer()->notNull(),
            'updated_at' => $this->integer()->notNull(),
            'notif_mail' => $this->integer(1)->defaultValue(0),
            'notif_mess' => $this->integer(1)->defaultValue(0),
        ], $tableOptions);
		
        $this->createTable('{{%post}}', [
            'id' => $this->primaryKey(),
            'author_id' => $this->integer()->notNull(),
            'title' => $this->string(100),
            'preview' => $this->text(300),
            'body' => $this->text(10000),
            'date_create' => $this->dateTime(),
            'date_update' => $this->dateTime(),
        ]);

        // creates index for column `author_id`
        $this->createIndex(
            'idx-post-author_id',
            '{{%post}}',
            'author_id'
        );

        // add foreign key for table `user`
        $this->addForeignKey(
            'fk-post-author_id',
            '{{%post}}',
            'author_id',
            '{{%user}}',
            'id',
            'CASCADE'
        );
		
    }

    public function safeDown()
    {		
		
        // drops foreign key for table `user`
        $this->dropForeignKey(
            'fk-post-author_id',
            '{{%post}}'
        );

        // drops index for column `author_id`
        $this->dropIndex(
            'idx-post-author_id',
            '{{%post}}'
        );

        $this->dropTable('{{%post}}');		
		
        $this->dropTable('{{%user}}');
    }

}
