<?php

namespace App\Models;

use CodeIgniter\Model;

class AdPhotoModel extends Model
{
    protected $table            = 'ad_photos';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $protectFields    = true;

    // Champs autorisés - ajout de created_at pour correspondre à la structure de la BD
    protected $allowedFields = [
        'ad_id',
        'original_url',
        'thumbnail_url',
        'display_order',
        'alt_text',
        'created_at'
    ];

    // Dates - Correction importante : created_at existe mais pas updated_at
    protected $useTimestamps = true;
    protected $dateFormat    = 'datetime';
    protected $createdField  = 'created_at';
    protected $updatedField  = false; // Pas de champ updated_at dans cette table

    // Validation
    protected $validationRules = [
        'ad_id'         => 'required|integer|is_not_unique[ads.id]',
        'original_url'  => 'required|string|max_length[255]',
        'thumbnail_url' => 'permit_empty|string|max_length[255]',
        'display_order' => 'permit_empty|integer|greater_than_equal_to[0]',
        'alt_text'      => 'permit_empty|string|max_length[255]'
    ];

    protected $validationMessages   = [
        'ad_id' => [
            'required'      => 'L\'annonce est obligatoire.',
            'integer'       => 'L\'ID de l\'annonce est invalide.',
            'is_not_unique' => 'L\'annonce spécifiée n\'existe pas.'
        ],
        'original_url' => [
            'required'   => 'L\'URL de la photo originale est obligatoire.',
            'string'     => 'L\'URL doit être une chaîne de caractères.',
            'max_length' => 'L\'URL de la photo originale ne peut pas dépasser 255 caractères.'
        ],
        'thumbnail_url' => [
            'string'     => 'L\'URL de la miniature doit être une chaîne de caractères.',
            'max_length' => 'L\'URL de la miniature ne peut pas dépasser 255 caractères.'
        ],
        'display_order' => [
            'integer'               => 'L\'ordre d\'affichage doit être un nombre entier.',
            'greater_than_equal_to' => 'L\'ordre d\'affichage doit être un nombre positif.'
        ],
        'alt_text' => [
            'string'     => 'Le texte alternatif doit être une chaîne de caractères.',
            'max_length' => 'Le texte alternatif ne peut pas dépasser 255 caractères.'
        ]
    ];

    protected $skipValidation       = false;
    protected $cleanValidationRules = true;

    // Callbacks
    protected $allowCallbacks = true;
    protected $beforeInsert   = ['setCreatedAt'];
    protected $afterInsert    = [];
    protected $beforeUpdate   = [];
    protected $afterUpdate    = [];
    protected $beforeFind     = [];
    protected $afterFind      = [];
    protected $beforeDelete   = [];
    protected $afterDelete    = [];

    /**
     * Callback pour s'assurer que created_at est défini
     */
    protected function setCreatedAt(array $data)
    {
        if (isset($data['data']) && !isset($data['data']['created_at'])) {
            $data['data']['created_at'] = date('Y-m-d H:i:s');
        }
        return $data;
    }

    /**
     * Récupérer les photos d'une annonce
     */
    public function getByAd(int $adId): array
    {
        return $this->where('ad_id', $adId)
            ->orderBy('display_order', 'ASC')
            ->findAll();
    }

    /**
     * Supprimer toutes les photos d'une annonce
     */
    public function deleteByAd(int $adId): bool
    {
        return $this->where('ad_id', $adId)->delete() !== false;
    }

    /**
     * Récupérer la photo principale d'une annonce
     */
    public function getMainPhoto(int $adId): ?array
    {
        return $this->where('ad_id', $adId)
            ->orderBy('display_order', 'ASC')
            ->first();
    }

    /**
     * Mettre à jour l'ordre des photos
     */
    public function updateDisplayOrder(int $photoId, int $newOrder): bool
    {
        return $this->update($photoId, ['display_order' => $newOrder]);
    }

    /**
     * Insertion personnalisée avec requête SQL directe pour éviter les erreurs de binding
     */
    public function insertPhoto(array $data): int|false
    {
        // Ajouter manuellement created_at
        $data['created_at'] = date('Y-m-d H:i:s');
        
        // Nettoyer et sécuriser les données
        $ad_id = (int) $data['ad_id'];
        $original_url = $this->db->escape($data['original_url']);
        $thumbnail_url = isset($data['thumbnail_url']) && $data['thumbnail_url'] !== null ? $this->db->escape($data['thumbnail_url']) : 'NULL';
        $display_order = (int) ($data['display_order'] ?? 0);
        $alt_text = isset($data['alt_text']) && $data['alt_text'] !== null ? $this->db->escape($data['alt_text']) : 'NULL';
        $created_at = $this->db->escape($data['created_at']);

        // Construire la requête SQL manuellement
        $sql = "INSERT INTO {$this->table} (ad_id, original_url, thumbnail_url, display_order, alt_text, created_at) 
                VALUES ({$ad_id}, {$original_url}, {$thumbnail_url}, {$display_order}, {$alt_text}, {$created_at})";

        // Exécuter la requête directement
        if ($this->db->query($sql)) {
            return $this->db->insertID();
        }
        
        return false;
    }
}