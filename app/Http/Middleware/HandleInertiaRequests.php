<?php

namespace App\Http\Middleware;

use App\Http\Resources\UserResource;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Inertia\Middleware;
use Laravel\Jetstream\Jetstream;
use Tighten\Ziggy\Ziggy;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that is loaded on the first page visit.
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determine the current asset version.
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        $newspaperFile = Jetstream::localizedMarkdownPath('newspaper.md');
        $text = Str::markdown(file_get_contents($newspaperFile));

        return [
            ...parent::share($request),
            'auth' => [
                'user' => auth()->check() ? UserResource::make(auth()->user()) : null,
            ],
            'ziggy' => fn () => [
                ...(new Ziggy)->toArray(),
                'location' => $request->url(),
                'query' => $request->query(),
            ],
            'newspaper' => $text
        ];
    }
}
