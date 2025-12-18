<?php

namespace App\Models;

use CodeIgniter\Model;

class AdFeedbackModel extends Model
{
    protected $table = 'ad_feedbacks';
    protected $primaryKey = 'id';
    protected $returnType = 'array';
    protected $useSoftDeletes = false;
    protected $allowedFields = [
        'ad_id',
        'author_user_id',
        'rating',
        'content',
        'photos',
        'status',
        'admin_notes',
        'reviewed_by',
        'reviewed_at',
        'is_reported',
        'created_at',
        'updated_at',
    ];
    protected $useTimestamps = false; // timestamps handled by SQL defaults

    protected $validationRules = [
        'ad_id' => 'required|is_natural_no_zero',
        'author_user_id' => 'required|is_natural_no_zero',
        'rating' => 'required|is_natural_no_zero|less_than_equal_to[5]',
        'content' => 'required|min_length[5]',
    ];
}


