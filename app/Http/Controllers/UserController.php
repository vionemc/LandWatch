<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Contracts\Hashing\Hasher;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

use function abort;
use function back;
use function redirect;
use function view;

class UserController extends Controller
{
    public function __construct(private Hasher $hasher)
    {
        //
    }

    public function index(): View
    {
        $users = User::sortable()->filterable()->paginate();
        $filters = [];

        return view('user.index', [
            'items' => $users,
            'filters' => $filters,
        ]);
    }

    public function create(): View
    {
        return view('user.create');
    }

    public function edit(string $id): View
    {
        $user = User::find($id);
        if ($user === null) {
            abort(404, 'Model not found.');
        }

        return view('user.edit', [
            'user' => $user,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $rules = [
            'id' => 'sometimes|required|integer',
            'name' => 'required|string|max:255',
            'email' => ['required', 'string', 'email', 'max:255'],
            'password' => ['string', 'min:8'],
        ];

        if ($request->has('id')) {
            $user = User::find($request->get('id'));
            if ($user === null) {
                abort(404, 'Model not found.');
            }
            $rules['password'][] = 'nullable';
            $rules['email'][] = Rule::unique(User::class)->ignore($user->id);
        } else {
            $rules['password'][] = 'required';
            $rules['password'][] = 'confirmed';
            $user = new User();
        }

        $validated = $request->validate($rules);

        if ($validated['password'] !== null) {
            $validated['password'] = $this->hasher->make($validated['password']);
        }
        $user->fill($validated)->save();

        return redirect('users');
    }

    public function destroy(string $id): RedirectResponse
    {
        $user = User::find($id);
        if ($user === null) {
            abort(404, 'Model not found.');
        }

        $user->delete();
        return back();
    }
}
