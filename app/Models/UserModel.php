<?php

namespace App\Models;

use CodeIgniter\Model;

class UserModel extends Model
{

    protected $table            = 'users';
    protected $primaryKey       = 'id_user';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = true;
    protected $protectFields    = true;
    protected $allowedFields = [
        'role_id', 'slug', 'first_name', 'last_name', 'email', 'phone',
        'password_hash', 'photo_url', 'otp_code', 'otp_expires_at',
        'is_verified', 'verification_token', 'reset_token', 'reset_token_expires',
        'google_id', 'facebook_id',
        'is_identity_verified', 'identity_document_type',
        'identity_document_number', 'identity_document_url', 'identity_verified_at',
        'is_suspended', 'suspended_at', 'suspended_by', 'suspension_reason',
        'unsuspended_at', 'unsuspended_by', 'referral_code_id'
    ];

    // Dates
    protected $useTimestamps = true;
    protected $dateFormat    = 'datetime';
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';
    protected $deletedField  = 'deleted';

    // Validation
    protected $validationRules      = [
        'email'    => 'permit_empty|valid_email|is_unique[users.email,id_user,{id_user}]',
        'phone'    => 'if_exist|permit_empty|is_unique[users.phone,id_user,{id_user}]',
        'password_hash' => 'if_exist|min_length[6]',
        'first_name' => 'if_exist|permit_empty|min_length[2]|max_length[100]',
        'last_name' => 'if_exist|permit_empty|min_length[2]|max_length[100]',
        'photo_url' => 'if_exist|permit_empty|max_length[500]',
        'identity_document_url' => 'if_exist|permit_empty|max_length[500]',
        'role_id' => 'if_exist|integer|greater_than[0]',
    ];
    protected $validationMessages   = [
        'email' => [
            'required' => 'L\'email est requis',
            'valid_email' => 'Veuillez fournir un email valide',
            'is_unique' => 'Cet email est déjà utilisé'
        ],
        'phone' => [
            'is_unique' => 'Ce numéro de téléphone est déjà utilisé'
        ],
        'password_hash' => [
            'required' => 'Le mot de passe est requis',
            'min_length' => 'Le mot de passe doit contenir au moins 6 caractères'
        ]
    ];
    protected $skipValidation       = false;
    protected $cleanValidationRules = true;

    // Callbacks
    protected $allowCallbacks = true;
    protected $beforeInsert   = ['generateSlug'];
    protected $beforeUpdate   = ['generateSlug'];


    protected function generateSlug(array $data)
    {
        // Ne générer un slug que si on a au moins un prénom ou nom, et seulement lors d'une mise à jour complète
        if (isset($data['data']['first_name']) && isset($data['data']['last_name']) && 
            !empty(trim($data['data']['first_name'])) && !empty(trim($data['data']['last_name']))) {
            
            $fullName = trim($data['data']['first_name']) . ' ' . trim($data['data']['last_name']);
            $slug = url_title($fullName, '-', true);

            // Vérifier si le slug existe déjà
            $existing = $this->where('slug', $slug)
                ->where('id_user !=', $data['id'] ?? 0)
                ->first();

            if ($existing) {
                $slug .= '-' . uniqid();
            }

            $data['data']['slug'] = $slug;
        }
        return $data;
    }

    public function getUsers($perPage = 10, $page = 1, $search = null, $filters = [])
    {
        $builder = $this->builder();

        // Recherche
        if (!empty($search)) {
            $builder->groupStart()
                ->like('first_name', $search)
                ->orLike('last_name', $search)
                ->orLike('email', $search)
                ->orLike('phone', $search)
                ->groupEnd();
        }

        // Filtres
        if (!empty($filters['role_id'])) {
            $builder->where('role_id', $filters['role_id']);
        }

        if (!empty($filters['is_verified'])) {
            $builder->where('is_verified', $filters['is_verified']);
        }

        if (!empty($filters['is_identity_verified'])) {
            $builder->where('is_identity_verified', $filters['is_identity_verified']);
        }

        // Pagination
        $offset = ($page - 1) * $perPage;
        $builder->limit($perPage, $offset);

        // Tri
        $builder->orderBy('created_at', 'DESC');

        $query = $builder->get();
        return $query->getResultArray();
    }

    public function countUsers($search = null, $filters = [])
    {
        $builder = $this->builder();

        // Recherche
        if (!empty($search)) {
            $builder->groupStart()
                ->like('first_name', $search)
                ->orLike('last_name', $search)
                ->orLike('email', $search)
                ->orLike('phone', $search)
                ->groupEnd();
        }

        // Filtres
        if (!empty($filters['role_id'])) {
            $builder->where('role_id', $filters['role_id']);
        }

        if (!empty($filters['is_verified'])) {
            $builder->where('is_verified', $filters['is_verified']);
        }

        if (!empty($filters['is_identity_verified'])) {
            $builder->where('is_identity_verified', $filters['is_identity_verified']);
        }

        return $builder->countAllResults();
    }
}