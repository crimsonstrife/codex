<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MentionController extends Controller
{
    public function users(Request $request): JsonResponse
    {
        $q = trim((string) $request->get('q', ''));

        $users = User::query()
            ->when($q !== '', fn($query) =>
                $query->where('name', 'like', "%{$q}%")
                      ->orWhere('email', 'like', "%{$q}%")
            )
            ->orderBy('name')
            ->limit(10)
            ->get(['id', 'name', 'email']);

        return response()->json(
            $users->map(fn($u) => [
                'id'       => $u->id,
                'label'    => $u->name,
                'sublabel' => $u->email,
                'url'      => '#',
            ])
        );
    }
}
