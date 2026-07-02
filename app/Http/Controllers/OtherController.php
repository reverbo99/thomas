<?php

namespace App\Http\Controllers;

use Auth;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules;
use Illuminate\Support\Facades\Hash;
use App\Http\Controllers\AuthController;
use App\Models\Access;
use App\Models\Booking;

class OtherController extends Controller
{
    private function requireLocalAdminAccess(): void
    {
        $user = Auth::user();
        abort_unless($user && $user->isActive() && $user->hasAccess(Access::LINKS['LOCAL_ADMINS']), 403);
    }

    public function local_admin()
    {
        $this->requireLocalAdminAccess();
        $users = User::where('role', 'admin')
            ->orderBy('id', 'asc')
            ->skip(1)
            ->take(PHP_INT_MAX) // or use a large number instead of PHP_INT_MAX
            ->get();
        return view('system.local_admin', compact('users'));
    }

    public function local_admin_form()
    {
        $this->requireLocalAdminAccess();
        return view('system.local_admin_create');
    }

    public function local_admin_store(Request $request)
    {
        $this->requireLocalAdminAccess();
        $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', Rule::unique('users')],
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
            'role' => ['nullable', 'in:admin,local_admin,bus_campany,vender,customer,special_hire,local_bus_owner'],
        ]);

        try {
            $user = User::create([
                'name' => $request->name,
                'email' => $request->email,
                'password' => Hash::make($request->password),
                'role' => $request->input('role', 'admin'),
                'contact' => $request->contact,
                'status' => 'accept', // Assuming you want to set the status to active
            ]);

            // You might want to add event or notification here
            // event(new UserCreated($user));

            return redirect()->route('system.local_admin')
                ->with('success', __('system.messages.local_admin_created'));
        } catch (\Throwable $th) {
            // Log the error for debugging
            \Log::error('Error creating local admin: ' . $th->getMessage());

            return back()->withInput()
                ->with('error', __('system.messages.local_admin_create_error', ['error' => $th->getMessage()]));
        }
    }

    public function local_admin_edit($id)
    {
        $this->requireLocalAdminAccess();
        $user = User::findOrFail($id);
        return view('system.local_admin_edit', compact('user'));
    }
    public function local_admin_update(Request $request, $id)
    {
        $this->requireLocalAdminAccess();
        $user = User::findOrFail($id);

        $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', Rule::unique('users')->ignore($user->id)],
            'password' => ['nullable', 'confirmed', Rules\Password::defaults()],
            'role' => ['nullable', 'in:admin,local_admin,bus_campany,vender,customer,special_hire,local_bus_owner'],
        ]);

        try {
            $user->name = $request->name;
            $user->email = $request->email;
            if ($request->filled('password')) {
                $user->password = Hash::make($request->password);
            }
            if ($request->filled('role')) {
                $user->role = $request->role;
            }
            $user->contact = $request->contact;
            $user->status = 'accept'; // Assuming you want to set the status to active
            $user->save();

            return redirect()->route('system.local_admin')
                ->with('success', __('system.messages.local_admin_updated'));
        } catch (\Throwable $th) {
            // Log the error for debugging
            \Log::error('Error updating local admin: ' . $th->getMessage());

            return back()->withInput()
                ->with('error', __('system.messages.local_admin_update_error', ['error' => $th->getMessage()]));
        }
    }
    public function local_admin_destroy($id)
    {
        $this->requireLocalAdminAccess();
        $user = User::findOrFail($id);
        try {
            $user->delete();
            return redirect()->route('system.local_admin')
                ->with('success', __('system.messages.local_admin_deleted'));
        } catch (\Throwable $th) {
            // Log the error for debugging
            \Log::error('Error deleting local admin: ' . $th->getMessage());

            return back()->with('error', __('system.messages.local_admin_delete_error', ['error' => $th->getMessage()]));
        }
    }

    public function update_role(Request $request)
    {
        $this->requireLocalAdminAccess();
        $request->validate([
            'id' => ['required', 'integer', 'exists:users,id'],
            'link' => ['required', 'string', Rule::in(array_values(Access::LINKS))],
            'status' => ['required', Rule::in(['active', 'inactive'])],
        ]);

        $user = User::findOrFail($request->id);


        try {
            $data = Access::updateOrcreate(
                [
                    'user_id' => $request->id,
                    'link' => $request->link,
                ],
                [
                    'status' => $request->status,
                ]
            );
            if ($data->wasRecentlyCreated) {
                $message = __('system.messages.access_created');
            } else {
                $message = __('system.messages.access_updated');
            }

            return back()
                ->with('success', $message);
        } catch (\Throwable $th) {
            // Log the error for debugging
            \Log::error('Error updating user role: ' . $th->getMessage());

            return back()->withInput()
                ->with('error', __('system.messages.role_update_error', ['error' => $th->getMessage()]));
        }
    }

    // for Bus owner

    public function local_bus_owners($id)
    {
        $user = User::findOrFail($id);
        return view('controller.owner_permissions_edit', compact('user'));
    }

    public function local_bus_update(Request $request)
    {
        $user = User::findOrFail($request->id);


        try {
            $data = Access::updateOrcreate(
                [
                    'user_id' => $request->id,
                    'link' => $request->link,
                ],
                [
                    'status' => $request->status,
                ]
            );
            if ($data->wasRecentlyCreated) {
                $message = __('local_bus_owners.access_created_success');
            } else {
                $message = __('local_bus_owners.access_updated_success');
            }

            return back()
                ->with('success', $message);
        } catch (\Throwable $th) {
            \Log::error('Error updating user role: ' . $th->getMessage());

            return back()->withInput()
                ->with('error', __('local_bus_owners.error_updating_user_role', ['error' => $th->getMessage()]));
        }
    }
}
