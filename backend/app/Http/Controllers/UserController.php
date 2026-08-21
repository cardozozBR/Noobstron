<?php

namespace App\Http\Controllers;

use App\Enums\Feature;
use App\Support\TenantCapabilities;

use App\Models\User;
use App\Services\AuditService;
use App\Services\RolePermissionSync;
use App\Services\TenantContext;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class UserController extends Controller
{
    public function index()
    {
        $tenant = app(TenantContext::class)->get();

        return view('users.index', [
            'users' => User::query()
                ->orderBy('name')
                ->paginate(20)
                ->withQueryString(),
            'tenant' => $tenant,
        ]);
    }
    public function create()
    {
        return view('users.create', [
            'tenant' => app(TenantContext::class)->get(),
        ]);
    }

    public function store(
        Request $request,
        AuditService $audits,
        RolePermissionSync $permissionSync,
        TenantCapabilities $capabilities
    ) {
        $tenant = app(TenantContext::class)->get();

        $limit = $capabilities->limit(
            $tenant,
            Feature::USERS
        );

        if (
            $limit !== null
            && User::query()
                ->where('tenant_id', $tenant->id)
                ->count() >= $limit
        ) {
            return back()
                ->withInput()
                ->withErrors([
                    'limit' => 'Limite de usuários do tenant atingido.',
                ]);
        }

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],

            'email' => [
                'required',
                'email',
                'max:255',
                Rule::unique('users', 'email')
                    ->where(
                        fn ($query) => $query->where(
                            'tenant_id',
                            $tenant->id
                        )
                    ),
            ],

            'password' => [
                'required',
                'string',
                'min:8',
                'confirmed',
            ],

            'role' => [
                'required',
                Rule::in(['admin', 'user']),
            ],
        ]);

        $role = auth()->user()->isAdmin()
            ? $data['role']
            : 'user';

        $user = User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => $data['password'],
            'role' => $role,
        ]);

        $permissionSync->sync($user);

        $audits->log(
            'user.created',
            'Usuário criado: '
                . $user->name
                . ' ('
                . $user->email
                . ').'
        );

        return redirect()
            ->route('users.index')
            ->with(
                'success',
                'Usuário criado com sucesso.'
            );
    }
    public function destroy(
        int $id,
        AuditService $audits
    ) {
        $user = User::findOrFail($id);

        if ($user->id === auth()->id()) {
            return redirect()
                ->route('users.index')
                ->with(
                    'error',
                    'Você não pode excluir sua própria conta.'
                );
        }

        if ($user->isAdmin()) {
            return redirect()
                ->route('users.index')
                ->with(
                    'error',
                    'Não é permitido excluir outro administrador.'
                );
        }

        $userName = $user->name;
        $userEmail = $user->email;

        $user->delete();

        $audits->log(
            'user.deleted',
            'Usuário excluído: '
                . $userName
                . ' ('
                . $userEmail
                . ').'
        );

        return redirect()
            ->route('users.index')
            ->with(
                'success',
                'Usuário excluído com sucesso.'
            );
    }

    public function edit(int $id)
    {
        $tenant = app(TenantContext::class)->get();

        $user = User::findOrFail($id);

        return view('users.edit', [
            'user' => $user,
            'tenant' => $tenant,
        ]);
    }

    public function update(
        Request $request,
        int $id,
        AuditService $audits,
        RolePermissionSync $permissionSync
    ) {
        $tenant = app(TenantContext::class)->get();

        $user = User::findOrFail($id);

        $oldRole = $user->role;

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],

            'email' => [
                'required',
                'email',
                'max:255',
                Rule::unique('users', 'email')
                    ->where(
                        fn ($query) => $query->where(
                            'tenant_id',
                            $tenant->id
                        )
                    )
                    ->ignore($user->id),
            ],

            'password' => [
                'nullable',
                'string',
                'min:8',
                'confirmed',
            ],

            'role' => [
                'required',
                Rule::in(['admin', 'user']),
            ],
        ]);

        $changes = [];

        if ($user->name !== $data['name']) {
            $changes[] = 'nome';
        }

        if ($user->email !== $data['email']) {
            $changes[] = 'e-mail';
        }

        if (!empty($data['password'])) {
            $changes[] = 'senha';
        }

        if (
            auth()->user()->isAdmin()
            && $user->id !== auth()->id()
        ) {
            if (
                $user->isAdmin()
                && $data['role'] !== 'admin'
            ) {
                return redirect()
                    ->route('users.edit', $user->id)
                    ->with(
                        'error',
                        'Não é permitido rebaixar outro administrador.'
                    );
            }

            if ($user->role->value !== $data['role']) {
                $changes[] = 'papel';
            }

            $user->role = $data['role'];
        }

        $user->name = $data['name'];
        $user->email = $data['email'];

        if (!empty($data['password'])) {
            $user->password = $data['password'];
        }

        $user->save();

        $description = empty($changes)
            ? 'Usuário atualizado sem alterações nos dados.'
            : 'Usuário atualizado: '
                . $user->name
                . ' ('
                . $user->email
                . '). Alterações: '
                . implode(', ', $changes)
                . '.';

        $permissionSync->sync($user);

        $audits->log(
            'user.updated',
            $description
        );

        if ($oldRole !== $user->role) {
            $audits->log(
                'user.role.updated',
                'Papel alterado para '
                    . $user->name
                    . ' ('
                    . $user->email
                    . '): '
                    . $oldRole->value
                    . ' → '
                    . $user->role->value
                    . '.'
            );
        }

        return redirect()
            ->route('users.index')
            ->with(
                'success',
                'Usuário atualizado com sucesso.'
            );
    }
}