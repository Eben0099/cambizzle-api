<?php

namespace App\Models;

use CodeIgniter\Model;

class AdFilterValueModel extends Model
{
    protected $table            = 'ad_filter_values';
    protected $primaryKey       = ['ad_id', 'filter_id'];
    protected $useAutoIncrement = false;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = false;
    protected $protectFields    = true;
    protected $allowedFields    = [
        'ad_id',
        'filter_id',
        'value'
    ];

    protected bool $allowEmptyInserts = false;
    protected bool $updateOnlyChanged = true;

    // Dates
    protected $useTimestamps = false;
    protected $dateFormat    = 'datetime';
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';

    // Validation
    protected $validationRules      = [
        'ad_id' => 'required|integer|is_not_unique[ads.id]',
        'filter_id' => 'required|integer|is_not_unique[filters.id]',
        'value' => 'required|string|max_length[1000]'
    ];

    protected $validationMessages   = [
        'ad_id' => [
            'required' => 'L\'annonce est obligatoire',
            'integer' => 'ID annonce invalide',
            'is_not_unique' => 'Annonce non trouvée'
        ],
        'filter_id' => [
            'required' => 'Le filtre est obligatoire',
            'integer' => 'ID filtre invalide',
            'is_not_unique' => 'Filtre non trouvé'
        ],
        'value' => [
            'required' => 'La valeur du filtre est obligatoire',
            'string' => 'La valeur doit être une chaîne de caractères',
            'max_length' => 'La valeur ne peut pas dépasser 1000 caractères'
        ]
    ];

    protected $skipValidation       = false;
    protected $cleanValidationRules = true;

    // Callbacks
    protected $allowCallbacks = false; // Désactiver les callbacks pour éviter les conflits

    /**
     * Insertion personnalisée pour gérer la clé primaire composite
     * Utilise une requête SQL directe car CodeIgniter a du mal avec les clés composites
     */
    public function insertFilterValue(array $data): bool
    {
        try {
            // Nettoyer et valider les données
            $cleanData = [
                'ad_id' => (int) $data['ad_id'],
                'filter_id' => (int) $data['filter_id'],
                'value' => trim((string) $data['value'])
            ];

            // Vérifier que les données sont valides
            if (empty($cleanData['value'])) {
                log_message('error', '[AdFilterValueModel] Valeur vide pour filtre ' . $cleanData['filter_id']);
                return false;
            }

            // Vérifier d'abord si le filtre existe déjà (clé composite)
            $existing = $this->db->table($this->table)
                ->where('ad_id', $cleanData['ad_id'])
                ->where('filter_id', $cleanData['filter_id'])
                ->get()
                ->getRowArray();

            if ($existing) {
                // Mettre à jour la valeur existante
                log_message('debug', '[AdFilterValueModel] Filtre existe, mise à jour: ad_id=' . $cleanData['ad_id'] . ', filter_id=' . $cleanData['filter_id']);
                $result = $this->db->table($this->table)
                    ->where('ad_id', $cleanData['ad_id'])
                    ->where('filter_id', $cleanData['filter_id'])
                    ->update(['value' => $cleanData['value']]);
            } else {
                // Insérer nouvelle valeur
                log_message('debug', '[AdFilterValueModel] Insertion nouveau filtre: ad_id=' . $cleanData['ad_id'] . ', filter_id=' . $cleanData['filter_id']);
                $result = $this->db->table($this->table)
                    ->insert($cleanData);
            }

            if ($result) {
                log_message('debug', '[AdFilterValueModel] ✅ Filtre traité avec succès: ad_id=' . $cleanData['ad_id'] . ', filter_id=' . $cleanData['filter_id'] . ', value="' . $cleanData['value'] . '"');
                return true;
            } else {
                $dbError = $this->db->error();
                log_message('error', '[AdFilterValueModel] ❌ Échec traitement filtre: ' . json_encode($dbError));
                return false;
            }

        } catch (\Exception $e) {
            log_message('error', '[AdFilterValueModel] ❌ Exception lors du traitement: ' . $e->getMessage());
            log_message('error', '[AdFilterValueModel] Trace: ' . $e->getTraceAsString());
            return false;
        }
    }

    /**
     * Récupérer les valeurs de filtres pour une annonce
     */
    public function getByAd(int $adId): array
    {
        return $this->where('ad_id', $adId)->findAll();
    }

    /**
     * Supprimer toutes les valeurs de filtres d'une annonce
     */
    public function deleteByAd(int $adId): bool
    {
        return $this->where('ad_id', $adId)->delete() !== false;
    }
}
