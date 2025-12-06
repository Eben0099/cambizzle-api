<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateAdFeedbacks extends Migration
{
    public function up()
    {
        // ad_feedbacks table
        $this->forge->addField([
            'id' => [
                'type' => 'INT',
                'constraint' => 10,
                'unsigned' => true,
                'auto_increment' => true,
            ],
            'ad_id' => [
                'type' => 'INT',
                'constraint' => 10,
                'unsigned' => true,
                'null' => false,
            ],
            'author_user_id' => [
                'type' => 'INT',
                'constraint' => 10,
                'unsigned' => true,
                'null' => false,
            ],
            'rating' => [
                'type' => 'TINYINT',
                'constraint' => 3,
                'unsigned' => true,
                'null' => false,
            ],
            'content' => [
                'type' => 'TEXT',
                'null' => false,
            ],
            'photos' => [
                'type' => 'TEXT', // store JSON string for portability
                'null' => true,
            ],
            'status' => [
                'type' => 'ENUM',
                'constraint' => ['pending', 'approved', 'rejected'],
                'default' => 'pending',
                'null' => false,
            ],
            'admin_notes' => [
                'type' => 'TEXT',
                'null' => true,
            ],
            'reviewed_by' => [
                'type' => 'INT',
                'constraint' => 10,
                'unsigned' => true,
                'null' => true,
            ],
            'reviewed_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
            'is_reported' => [
                'type' => 'TINYINT',
                'constraint' => 1,
                'unsigned' => true,
                'default' => 0,
                'null' => false,
            ],
            'created_at' => [
                'type' => 'DATETIME',
                'null' => false,
                'default' => 'CURRENT_TIMESTAMP',
            ],
            'updated_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
        ]);

        $this->forge->addKey('id', true);
        $this->forge->addKey(['ad_id', 'status']);
        $this->forge->addKey('author_user_id');
        $this->forge->addForeignKey('ad_id', 'ads', 'id', 'CASCADE', 'CASCADE');
        $this->forge->addForeignKey('author_user_id', 'users', 'id_user', 'CASCADE', 'CASCADE');
        $this->forge->createTable('ad_feedbacks');

        // Add unique constraint (ad_id, author_user_id)
        $this->db->query('ALTER TABLE ad_feedbacks ADD CONSTRAINT uq_ad_feedbacks_unique_per_user UNIQUE (ad_id, author_user_id)');

        // Add ratings aggregates to ads
        if (!$this->db->fieldExists('average_rating', 'ads')) {
            $this->forge->addColumn('ads', [
                'average_rating' => [
                    'type' => 'DECIMAL',
                    'constraint' => '3,2',
                    'default' => 0.00,
                    'null' => false,
                    'after' => 'view_count'
                ],
            ]);
        }
        if (!$this->db->fieldExists('ratings_count', 'ads')) {
            $this->forge->addColumn('ads', [
                'ratings_count' => [
                    'type' => 'INT',
                    'constraint' => 10,
                    'unsigned' => true,
                    'default' => 0,
                    'null' => false,
                    'after' => 'average_rating'
                ],
            ]);
        }
    }

    public function down()
    {
        // Remove unique constraint first
        $this->db->query('ALTER TABLE ad_feedbacks DROP INDEX uq_ad_feedbacks_unique_per_user');

        // Drop table
        $this->forge->dropTable('ad_feedbacks', true);

        // Remove fields from ads
        if ($this->db->fieldExists('ratings_count', 'ads')) {
            $this->forge->dropColumn('ads', 'ratings_count');
        }
        if ($this->db->fieldExists('average_rating', 'ads')) {
            $this->forge->dropColumn('ads', 'average_rating');
        }
    }
}


