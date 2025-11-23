<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\UserInvitation;
use App\Services\TenantContext;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;

class InvitationController extends Controller
{
    public function __construct(
        protected TenantContext $tenantContext
    ) {}

    /**
     * Show the invitation acceptance form
     */
    public function show(string $token)
    {
        $invitation = UserInvitation::where('token', $token)->first();
        
        if (!$invitation) {
            return view('invitations.invalid')
                ->with('error', 'Invalid or expired invitation.');
        }
        
        // Check if invitation is expired
        if ($invitation->isExpired()) {
            $invitation->markAsExpired();
            return view('invitations.invalid')
                ->with('error', 'This invitation has expired.');
        }
        
        // Check if already accepted
        if ($invitation->status !== 'pending') {
            return view('invitations.invalid')
                ->with('error', 'This invitation has already been used.');
        }
        
        return view('invitations.accept', compact('invitation'));
    }

    /**
     * Process invitation acceptance
     */
    public function accept(Request $request, string $token)
    {
        // Initial validation - look up invitation
        $invitation = UserInvitation::where('token', $token)->first();
        
        if (!$invitation) {
            return redirect()->route('invitation.accept', $token)
                ->withErrors(['error' => 'Invalid or expired invitation.']);
        }
        
        // Check if invitation is expired
        if ($invitation->isExpired()) {
            $invitation->markAsExpired();
            return redirect()->route('invitation.accept', $token)
                ->withErrors(['error' => 'This invitation has expired.']);
        }
        
        // Check if already accepted (status != 'pending')
        if ($invitation->status !== 'pending') {
            return redirect()->route('invitation.accept', $token)
                ->withErrors(['error' => 'This invitation has already been used.']);
        }
        
        // Validate form input
        $validated = $request->validate([
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'email' => 'required|email|max:255',
            'password' => 'required|string|min:8|confirmed',
        ]);
        
        // Verify email matches invitation
        if ($validated['email'] !== $invitation->email) {
            return back()->withErrors(['email' => 'Email does not match the invitation.'])
                ->withInput();
        }
        
        // Check if user already exists (edge case: user was manually deleted but invitation still exists)
        $existingUser = User::where('tenant_id', $invitation->tenant_id)
            ->where('email', $validated['email'])
            ->first();
        
        if ($existingUser) {
            return back()->withErrors(['email' => 'A user with this email already exists.'])
                ->withInput();
        }
        
        // Use database transaction to ensure atomicity
        try {
            DB::beginTransaction();
            
            // Re-check invitation status inside transaction (prevent race conditions)
            $invitation = UserInvitation::where('token', $token)
                ->where('status', 'pending')
                ->where('expires_at', '>', now())
                ->lockForUpdate()
                ->first();
            
            if (!$invitation) {
                DB::rollBack();
                return redirect()->route('invitation.accept', $token)
                    ->withErrors(['error' => 'This invitation has expired or has already been used.']);
            }
            
            // Store tenant_id before deletion
            $tenantId = $invitation->tenant_id;
            
            // Create user from invitation data
            $user = User::create([
                'tenant_id' => $tenantId,
                'first_name' => $validated['first_name'],
                'last_name' => $validated['last_name'],
                'name' => trim($validated['first_name'] . ' ' . $validated['last_name']),
                'email' => $validated['email'],
                'password' => Hash::make($validated['password']),
                'role_name' => $invitation->role_name,
                'permissions' => $invitation->permissions ?? [],
                'is_active' => true,
            ]);
            
            // Delete invitation after successful user creation
            $invitation->delete();
            
            // Commit transaction
            DB::commit();
            
            // Set tenant in session
            $request->session()->put('tenant_id', $tenantId);
            
            // Log the user in
            Auth::login($user);
            
            Log::info('User accepted invitation and created account', [
                'user_id' => $user->id,
                'invitation_email' => $validated['email'],
                'tenant_id' => $tenantId,
            ]);
            
            return redirect()->route('tenant.dashboard')
                ->with('success', 'Welcome! Your account has been set up successfully.');
                
        } catch (\Exception $e) {
            // Roll back transaction on any error
            DB::rollBack();
            
            Log::error('Error accepting invitation', [
                'token' => $token,
                'error' => $e->getMessage(),
            ]);
            
            return back()->withErrors(['error' => 'An error occurred while setting up your account. Please try again.'])
                ->withInput();
        }
    }
}
