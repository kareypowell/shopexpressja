<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class AuditLogController extends Controller
{
    /**
     * Create a new controller instance.
     */
    public function __construct()
    {
        $this->middleware('auth');
        $this->middleware('can:viewAny,App\Models\AuditLog');
    }

    /**
     * Display the audit log management interface.
     */
    public function index()
    {
        return view('admin.audit-logs.index');
    }
}