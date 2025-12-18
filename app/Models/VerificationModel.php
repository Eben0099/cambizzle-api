<?php
namespace App\Models;
use CodeIgniter\Model;
class VerificationModel extends Model {
    protected $table = 'verifications';
    protected $primaryKey = 'id';
    protected $returnType = 'App\\Entities\\VerificationEntity';
    protected $allowedFields = [
        'user_id',
        'type',
        'document_path',
        'status',
        'created_at',
        'updated_at',
    ];
}
