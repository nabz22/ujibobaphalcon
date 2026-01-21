<?php

use Phalcon\Mvc\Model;

class OdooSync extends Model
{
    public $id;
    public $entity_type;    // 'notes', 'users', etc
    public $entity_id;      // ID dari entity local
    public $odoo_id;        // ID dari Odoo
    public $sync_status;    // 'pending', 'synced', 'failed'
    public $sync_direction; // 'push', 'pull', 'bidirectional'
    public $error_message;
    public $synced_at;
    public $created_at;
    public $updated_at;

    public function initialize()
    {
        $this->setSource('odoo_sync');
    }

    /**
     * Filter validator
     */
    public function validation()
    {
        $this->validate(
            new \Phalcon\Validation\Validator\PresenceOf([
                'field'    => 'entity_type',
                'message'  => 'Entity type is required'
            ])
        );

        return !$this->validationHasFailed();
    }

    /**
     * Get sync status
     */
    public static function getSyncStatus($entityType, $entityId)
    {
        return self::findFirst([
            'conditions' => 'entity_type = ?1 AND entity_id = ?2',
            'bind'       => [1 => $entityType, 2 => $entityId]
        ]);
    }

    /**
     * Mark as synced
     */
    public function markSynced($odooId, $errorMessage = null)
    {
        $this->odoo_id = $odooId;
        $this->sync_status = $errorMessage ? 'failed' : 'synced';
        $this->error_message = $errorMessage;
        $this->synced_at = date('Y-m-d H:i:s');
        $this->updated_at = date('Y-m-d H:i:s');
        
        return $this->save();
    }
}
