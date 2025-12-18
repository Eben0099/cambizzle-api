<?php

namespace App\Entities;

use CodeIgniter\Entity\Entity;

class ReportEntity extends Entity
{
    protected $datamap = [];
    protected $dates   = ['created_at', 'handled_at'];
    protected $casts   = [];

    // Accesseurs
    public function getReportType(): string
    {
        return $this->attributes['report_type'] ?? '';
    }

    public function getReportReason(): string
    {
        return $this->attributes['report_reason'] ?? '';
    }

    public function getDescription(): ?string
    {
        return $this->attributes['description'] ?? null;
    }

    public function getAdminNotes(): ?string
    {
        return $this->attributes['admin_notes'] ?? null;
    }

    public function getEvidenceFiles(): array
    {
        $files = $this->attributes['evidence_files'] ?? '[]';
        if (is_string($files)) {
            return json_decode($files, true) ?? [];
        }
        return is_array($files) ? $files : [];
    }

    public function isPending(): bool
    {
        return ($this->attributes['status'] ?? '') === 'pending';
    }

    public function isResolved(): bool
    {
        return ($this->attributes['status'] ?? '') === 'resolved';
    }

    public function isDismissed(): bool
    {
        return ($this->attributes['status'] ?? '') === 'dismissed';
    }

    public function isHandled(): bool
    {
        return !empty($this->attributes['handled_at']);
    }

    // Méthodes utilitaires
    public function toArray(bool $onlyChanged = false, bool $cast = true, bool $recursive = false): array
    {
        $data = parent::toArray($onlyChanged, $cast, $recursive);

        // Décoder les fichiers de preuve JSON
        $data['evidence_files'] = $this->getEvidenceFiles();
        $data['is_pending'] = $this->isPending();
        $data['is_resolved'] = $this->isResolved();
        $data['is_dismissed'] = $this->isDismissed();
        $data['is_handled'] = $this->isHandled();

        return $data;
    }
}
