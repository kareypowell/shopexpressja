<?php

namespace App\Http\Controllers;

use App\Models\BroadcastMessage;
use Illuminate\Http\Request;

class BroadcastMessageController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
        $this->authorizeResource(BroadcastMessage::class, 'broadcastMessage');
    }

    /**
     * Display the broadcast messaging interface
     */
    public function index()
    {
        $this->authorize('viewAny', BroadcastMessage::class);
        return view('admin.broadcast-messages.index');
    }

    /**
     * Display the broadcast composer interface
     */
    public function create()
    {
        $this->authorize('create', BroadcastMessage::class);
        return view('admin.broadcast-messages.create');
    }

    /**
     * Display a specific broadcast message
     */
    public function show(BroadcastMessage $broadcastMessage)
    {
        $this->authorize('view', $broadcastMessage);
        
        return view('admin.broadcast-messages.show', compact('broadcastMessage'));
    }
}