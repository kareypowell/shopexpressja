<?php

namespace App\Traits;

trait Auditable
{
    /**
     * Determine if this model instance should be audited
     */
    public function shouldAudit(): bool
    {
        // Check if auditing is disabled for this instance
        if (property_exists($this, 'auditingDisabled') && $this->auditingDisabled === true) {
            return false;
        }

        // Check if there's a custom audit condition method
        if (method_exists($this, 'auditCondition')) {
            return $this->auditCondition();
        }

        return true;
    }

    /**
     * Get fields that should be excluded from audit for this model
     */
    public function getAuditExcludedFields(): array
    {
        return property_exists($this, 'auditExcluded') ? $this->auditExcluded : [];
    }

    /**
     * Get additional data to include in audit logs for this model
     */
    public function getAuditAdditionalData(): array
    {
        $data = [];

        // Add model-specific context
        if (method_exists($this, 'getAuditContext')) {
            $data = array_merge($data, $this->getAuditContext());
        }

        // Add relationship context if available
        if (method_exists($this, 'getAuditRelationshipContext')) {
            $data['relationships'] = $this->getAuditRelationshipContext();
        }

        return $data;
    }

    /**
     * Disable auditing for this model instance
     */
    public function disableAuditing(): self
    {
        $this->auditingDisabled = true;
        return $this;
    }

    /**
     * Enable auditing for this model instance
     */
    public function enableAuditing(): self
    {
        $this->auditingDisabled = false;
        return $this;
    }

    /**
     * Check if auditing is disabled for this instance
     */
    public function isAuditingDisabled(): bool
    {
        return property_exists($this, 'auditingDisabled') && $this->auditingDisabled === true;
    }
}