<?php

namespace App\Observers;

use App\Models\User;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;

class UserObserver
{
    /**
     * Handle the User "updated" event.
     */
    public function updated(User $user): void
    {
        Log::info('UserObserver: User updated', [
            'user_id' => $user->id,
            'changed_fields' => $user->getChanges(),
            'dirty_fields' => $user->getDirty()
        ]);
        
        // Check if permissions or roles have changed
        if ($user->wasChanged('permission_updated_at')) {
            Log::info('UserObserver: Permission timestamp changed, broadcasting event', [
                'user_id' => $user->id,
                'user_name' => $user->name,
                'permission_updated_at' => $user->permission_updated_at
            ]);
            
            try {
                // Broadcast to a global permissions channel
                $event = new \App\Events\GlobalPermissionChanged($user);
                Log::info('UserObserver: Created event object', [
                    'event_class' => get_class($event),
                    'broadcast_on' => method_exists($event, 'broadcastOn') ? $event->broadcastOn() : 'No broadcastOn method',
                    'broadcast_as' => method_exists($event, 'broadcastAs') ? $event->broadcastAs() : 'No broadcastAs method'
                ]);
                
                broadcast($event);
                Log::info('UserObserver: Event broadcast completed');
            } catch (\Exception $e) {
                Log::error('UserObserver: Error broadcasting event', [
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString()
                ]);
            }
        } else {
            Log::info('UserObserver: Permission timestamp not changed');
        }
    }
}
