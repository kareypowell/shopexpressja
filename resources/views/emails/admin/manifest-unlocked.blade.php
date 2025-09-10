<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manifest Unlocked Notification</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            color: #333;
            max-width: 600px;
            margin: 0 auto;
            padding: 20px;
        }
        .header {
            background-color: #f8f9fa;
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 20px;
            border-left: 4px solid #ffc107;
        }
        .alert {
            background-color: #fff3cd;
            border: 1px solid #ffeaa7;
            color: #856404;
            padding: 15px;
            border-radius: 4px;
            margin-bottom: 20px;
        }
        .info-section {
            background-color: #f8f9fa;
            padding: 15px;
            border-radius: 4px;
            margin-bottom: 15px;
        }
        .info-section h3 {
            margin-top: 0;
            color: #495057;
            border-bottom: 2px solid #dee2e6;
            padding-bottom: 5px;
        }
        .info-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 10px;
            margin-bottom: 10px;
        }
        .info-item {
            margin-bottom: 8px;
        }
        .info-label {
            font-weight: bold;
            color: #6c757d;
        }
        .info-value {
            color: #212529;
        }
        .unlock-reason {
            background-color: #fff;
            border: 1px solid #dee2e6;
            padding: 15px;
            border-radius: 4px;
            font-style: italic;
            margin-top: 10px;
        }
        .action-buttons {
            text-align: center;
            margin-top: 30px;
        }
        .btn {
            display: inline-block;
            padding: 12px 24px;
            margin: 0 10px;
            text-decoration: none;
            border-radius: 4px;
            font-weight: bold;
            text-align: center;
        }
        .btn-primary {
            background-color: #007bff;
            color: white;
        }
        .btn-secondary {
            background-color: #6c757d;
            color: white;
        }
        .footer {
            margin-top: 30px;
            padding-top: 20px;
            border-top: 1px solid #dee2e6;
            font-size: 0.9em;
            color: #6c757d;
        }
        .status-badge {
            display: inline-block;
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 0.8em;
            font-weight: bold;
            text-transform: uppercase;
        }
        .status-open {
            background-color: #d4edda;
            color: #155724;
        }
        .status-closed {
            background-color: #f8d7da;
            color: #721c24;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>🔓 Manifest Unlocked</h1>
        <p>A closed manifest has been unlocked and is now available for editing.</p>
    </div>

    <div class="alert">
        <strong>Action Taken:</strong> The manifest "{{ $manifest->name }}" has been unlocked by {{ $unlockedBy->full_name }} and is now open for modifications.
    </div>

    <!-- Manifest Information -->
    <div class="info-section">
        <h3>📋 Manifest Details</h3>
        <div class="info-grid">
            <div class="info-item">
                <span class="info-label">Manifest Name:</span>
                <span class="info-value">{{ $manifest->name }}</span>
            </div>
            <div class="info-item">
                <span class="info-label">Manifest Number:</span>
                <span class="info-value">{{ $manifest->manifest_number ?? 'N/A' }}</span>
            </div>
            <div class="info-item">
                <span class="info-label">Type:</span>
                <span class="info-value">{{ ucfirst($manifest->type ?? 'Unknown') }}</span>
            </div>
            <div class="info-item">
                <span class="info-label">Current Status:</span>
                <span class="status-badge status-open">Open</span>
            </div>
        </div>
        <div class="info-grid">
            <div class="info-item">
                <span class="info-label">Created Date:</span>
                <span class="info-value">{{ $manifest->created_at->format('M j, Y g:i A') }}</span>
            </div>
            <div class="info-item">
                <span class="info-label">Total Packages:</span>
                <span class="info-value">{{ $manifest->packages()->count() }}</span>
            </div>
            <div class="info-item">
                <span class="info-label">Delivered Packages:</span>
                <span class="info-value">{{ $manifest->packages()->where('status', 'delivered')->count() }}</span>
            </div>
            <div class="info-item">
                <span class="info-label">Office:</span>
                <span class="info-value">{{ $manifest->office->name ?? 'N/A' }}</span>
            </div>
        </div>
        @if($manifest->vessel_name || $manifest->voyage_number)
        <div class="info-grid">
            @if($manifest->vessel_name)
            <div class="info-item">
                <span class="info-label">Vessel Name:</span>
                <span class="info-value">{{ $manifest->vessel_name }}</span>
            </div>
            @endif
            @if($manifest->voyage_number)
            <div class="info-item">
                <span class="info-label">Voyage Number:</span>
                <span class="info-value">{{ $manifest->voyage_number }}</span>
            </div>
            @endif
        </div>
        @endif
    </div>

    <!-- Unlock Information -->
    <div class="info-section">
        <h3>🔓 Unlock Details</h3>
        <div class="info-grid">
            <div class="info-item">
                <span class="info-label">Unlocked By:</span>
                <span class="info-value">{{ $unlockedBy->full_name }}</span>
            </div>
            <div class="info-item">
                <span class="info-label">Email:</span>
                <span class="info-value">{{ $unlockedBy->email }}</span>
            </div>
            <div class="info-item">
                <span class="info-label">Unlocked At:</span>
                <span class="info-value">{{ $unlockedAt->format('M j, Y g:i A T') }}</span>
            </div>
            <div class="info-item">
                <span class="info-label">User Role:</span>
                <span class="info-value">{{ $unlockedBy->role->name ?? 'Unknown' }}</span>
            </div>
        </div>
        
        <div class="info-item">
            <span class="info-label">Reason for Unlocking:</span>
        </div>
        <div class="unlock-reason">
            {{ $reason }}
        </div>
    </div>

    <!-- Audit Trail Information -->
    @if($manifest->audits()->count() > 0)
    <div class="info-section">
        <h3>📝 Recent Audit Activity</h3>
        @foreach($manifest->audits()->with('user')->limit(5)->get() as $audit)
        <div class="info-item" style="border-bottom: 1px solid #dee2e6; padding-bottom: 8px; margin-bottom: 8px;">
            <div style="display: flex; justify-content: space-between; align-items: center;">
                <span class="info-value">
                    <strong>{{ $audit->action_label }}</strong> by {{ $audit->user->full_name ?? 'System' }}
                </span>
                <span style="font-size: 0.8em; color: #6c757d;">
                    {{ $audit->performed_at->format('M j, Y g:i A') }}
                </span>
            </div>
            @if($audit->reason)
            <div style="font-size: 0.9em; color: #6c757d; margin-top: 4px;">
                {{ $audit->reason }}
            </div>
            @endif
        </div>
        @endforeach
    </div>
    @endif

    <!-- Action Buttons -->
    <div class="action-buttons">
        <a href="{{ config('app.url') }}/admin/manifests/{{ $manifest->id }}" class="btn btn-primary">
            View Manifest
        </a>
        <a href="{{ config('app.url') }}/admin/manifests" class="btn btn-secondary">
            All Manifests
        </a>
    </div>

    <div class="footer">
        <p><strong>Important Notes:</strong></p>
        <ul>
            <li>The manifest is now open and can be modified by authorized users</li>
            <li>All changes to packages and manifest details will be tracked</li>
            <li>The manifest may be automatically closed again if all packages are delivered</li>
            <li>This unlock action has been logged in the audit trail</li>
        </ul>
        
        <p><strong>Next Steps:</strong></p>
        <ol>
            <li>Review the reason for unlocking to understand the business need</li>
            <li>Monitor any changes made to the manifest or its packages</li>
            <li>Ensure proper procedures are followed for any modifications</li>
            <li>Consider if additional oversight is needed for this manifest</li>
        </ol>
        
        <p><em>This email was automatically generated by the {{ config('app.name', 'ShipShark Ltd') }} Management System.</em></p>
        <p><em>Notification sent at: {{ now()->format('M j, Y g:i A T') }}</em></p>
    </div>
</body>
</html>