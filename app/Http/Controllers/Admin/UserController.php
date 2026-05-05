<?php

namespace App\Http\Controllers\Admin;

use App\Models\User;
use App\Mail\UserWelcome;
use App\Utils\PasswordGenerator;
use App\Models\UserPassword;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use Inertia\Inertia;

class UserController extends Controller
{
    /**
     * Show all users (admin only).
     */
    public function index()
    {
        $users = User::with('plainPassword')->get()->map(function ($user) {
            return [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'role' => $user->role,
                'plain_password' => $user->plainPassword ? $user->plainPassword->plain_password : 'N/A',
                'last_login_at' => $user->last_login_at,
                'created_at' => $user->created_at,
            ];
        });
        
        return Inertia::render('Admin/Users/Index', ['users' => $users]);
    }

    /**
     * Show create user form (admin only).
     */
    public function create()
    {
        return Inertia::render('Admin/Users/Create');
    }

    /**
     * Store a new admin user (admin only).
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:8|confirmed',
            'role' => 'required|in:admin,user',
        ]);

        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
            'role' => $validated['role'],
        ]);

        // Store plain password for admin reference
        UserPassword::create([
            'user_id' => $user->id,
            'plain_password' => $validated['password'],
        ]);

        return redirect()->route('admin.users.index')->with('success', 'User created successfully');
    }

    /**
     * Show edit user form (admin only).
     */
    public function show(User $user)
    {
        return Inertia::render('Admin/Users/Show', ['user' => $user]);
    }

    /**
     * Show edit user form (admin only).
     */
    public function edit(User $user)
    {
        return Inertia::render('Admin/Users/Edit', ['user' => $user]);
    }

    /**
     * Update user (admin only).
     */
    public function update(Request $request, User $user)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users,email,' . $user->id,
            'role' => 'required|in:admin,user',
            'password' => 'nullable|string|min:8|confirmed',
        ]);

        $user->update([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'role' => $validated['role'],
        ]);

        if ($validated['password'] ?? null) {
            $user->update(['password' => Hash::make($validated['password'])]);
            
            // Update or create plain password record
            UserPassword::updateOrCreate(
                ['user_id' => $user->id],
                ['plain_password' => $validated['password']]
            );
        }

        return redirect()->route('admin.users.index')->with('success', 'User updated successfully');
    }

    /**
     * Delete user (admin only).
     */
    public function destroy(User $user)
    {
        $user->delete();
        return redirect()->route('admin.users.index')->with('success', 'User deleted successfully');
    }

    /**
     * Bulk import users from CSV or email list (admin only).
     */
    public function bulkImport(Request $request)
    {
        $validated = $request->validate([
            'emails' => 'required|string',
            'send_emails' => 'boolean',
        ]);

        $emailsText = $validated['emails'];
        $sendEmails = $validated['send_emails'] ?? true;

        // Parse emails from various formats (comma-separated, newline-separated, etc.)
        $emails = $this->parseEmails($emailsText);

        if (empty($emails)) {
            return response()->json([
                'success' => false,
                'message' => 'No valid emails found in the provided text.',
            ], 422);
        }

        if (count($emails) > 500) {
            return response()->json([
                'success' => false,
                'message' => 'Maximum 500 users can be imported at once.',
            ], 422);
        }

        $results = [
            'created' => [],
            'failed' => [],
            'skipped' => [],
        ];

        DB::beginTransaction();

        try {
            foreach ($emails as $email) {
                $email = trim($email);

                // Check if email already exists
                if (User::where('email', $email)->exists()) {
                    $results['skipped'][] = [
                        'email' => $email,
                        'reason' => 'Email already exists',
                    ];
                    continue;
                }

                // Validate email format
                if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                    $results['failed'][] = [
                        'email' => $email,
                        'reason' => 'Invalid email format',
                    ];
                    continue;
                }

                try {
                    // Generate password and create user
                    $password = PasswordGenerator::generate();
                    $name = explode('@', $email)[0]; // Use email prefix as default name

                    $user = User::create([
                        'name' => $name,
                        'email' => $email,
                        'password' => Hash::make($password),
                        'role' => 'user',
                    ]);

                    // Store plain password for admin reference
                    UserPassword::create([
                        'user_id' => $user->id,
                        'plain_password' => $password,
                    ]);

                    // Send welcome email if requested
                    if ($sendEmails) {
                        try {
                            // Get frontend URL from config
                            $frontendUrls = config('app.frontend_url', 'https://statanex.com');
                            $appUrl = explode(',', $frontendUrls)[0];
                            $appUrl = trim($appUrl);
                            
                            Mail::to($email)->send(new UserWelcome($user, $password, $appUrl));
                        } catch (\Exception $e) {
                            \Log::error("Failed to send welcome email to {$email}: " . $e->getMessage());
                            // Don't fail the user creation if email fails
                        }
                    }

                    $results['created'][] = [
                        'email' => $email,
                        'name' => $name,
                        'password' => $password,
                    ];
                } catch (\Exception $e) {
                    $results['failed'][] = [
                        'email' => $email,
                        'reason' => 'Error creating user: ' . $e->getMessage(),
                    ];
                }
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => sprintf(
                    'Bulk import completed. Created: %d, Failed: %d, Skipped: %d',
                    count($results['created']),
                    count($results['failed']),
                    count($results['skipped'])
                ),
                'results' => $results,
            ]);
        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => 'An error occurred during bulk import: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Parse emails from various formats.
     * Supports: comma-separated, newline-separated, space-separated
     */
    private function parseEmails(string $text): array
    {
        // Replace common separators with newlines
        $text = str_replace([',', ';', "\t"], "\n", $text);

        // Split by newlines and filter empty values
        $emails = array_filter(
            array_map('trim', explode("\n", $text)),
            fn($email) => !empty($email)
        );

        return array_values($emails);
    }
}
