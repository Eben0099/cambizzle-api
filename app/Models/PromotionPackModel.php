<?php
namespace App\Models;

use CodeIgniter\Model;

/**
 * PromotionPackModel
 * Gère les packs de promotion (durée, prix, type)
 */
class PromotionPackModel extends Model
{
    protected $table = 'promotion_packs';
    protected $primaryKey = 'id';
    protected $allowedFields = [
        'name',           // Nom du pack (ex: Boost 7 jours)
        'duration_days',  // Durée en jours
        'price',
        'slug',          // Prix en FCFA
        'description',           // Type de promotion (ex: boost, urgent, etc.)
        'is_active',      // Statut d'activation
        'created_at',
        'updated_at',
    ];
    protected $useTimestamps = true;
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';
}
