<?php

namespace App\Http\Controllers;

use App\Models\Profile;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class DatingController extends Controller
{
    private function excluded(int $id): array
    {
        return DB::table('blocks')->where('user_id', $id)->pluck('target_id')
            ->merge(DB::table('blocks')->where('target_id', $id)->pluck('user_id'))->all();
    }

    private function card(Profile $profile): array
    {
        return ['id' => $profile->user_id, 'name' => $profile->user->name, 'age' => $profile->birthday->age,
            'looking_for' => $profile->looking_for, 'gender' => $profile->gender, 'city' => $profile->city, 'goal' => $profile->goal,
            'bio' => $profile->bio, 'interests' => $profile->interests, 'photos' => $profile->photos,
            'hidden' => $profile->hidden, 'demo' => $profile->demo];
    }

    public function state(Request $request)
    {
        $user = $request->user();
        $query = Profile::with('user')->where('hidden', false);
        if ($user) {
            $query->where('user_id', '!=', $user->id)->whereNotIn('user_id', $this->excluded($user->id));
            $query->whereNotIn('user_id', DB::table('reactions')->where('user_id', $user->id)->pluck('target_id'));
        } else {
            $query->where('demo', true);
        }
        if ($request->filled('city')) $query->where('city', $request->string('city')->trim()->toString());
        if (in_array($request->gender, ['female', 'male'])) $query->where('gender', $request->gender);
        if ($request->filled('goal')) $query->where('goal', $request->goal);
        if ($request->filled('min_age')) $query->whereDate('birthday', '<=', now()->subYears(max(18, (int) $request->min_age)));
        if ($request->filled('max_age')) $query->whereDate('birthday', '>', now()->subYears(min(100, (int) $request->max_age) + 1));
        $profiles = $query->orderBy('id')->limit(100)->get()->map(fn ($p) => $this->card($p));
        $me = $user ? Profile::with('user')->where('user_id', $user->id)->first() : null;
        $connections = collect();
        $likes = collect();
        if ($user) {
            $connections = DB::table('connections')->where(fn ($q) => $q->where('first_id', $user->id)->orWhere('second_id', $user->id))->get()
                ->map(function ($connection) use ($user) {
                    $other = $connection->first_id == $user->id ? $connection->second_id : $connection->first_id;
                    if (in_array($other, $this->excluded($user->id))) return null;
                    $profile = Profile::with('user')->where('user_id', $other)->first();
                    return $profile ? ['id' => $connection->id, 'profile' => $this->card($profile), 'last' => DB::table('messages')->where('connection_id', $connection->id)->latest('id')->value('body')] : null;
                })->filter()->values();
            $likes = Profile::with('user')->where('hidden', false)->whereNotIn('user_id', $this->excluded($user->id))
                ->whereNotIn('user_id', DB::table('reactions')->where('user_id', $user->id)->where('kind', 'like')->pluck('target_id'))
                ->whereIn('user_id', DB::table('reactions')->where('target_id', $user->id)->where('kind', 'like')->pluck('user_id'))->get()->map(fn ($p) => $this->card($p));
        }
        return response()->json(['me' => $me ? $this->card($me) : null, 'profiles' => $profiles, 'connections' => $connections, 'likes' => $likes]);
    }

    public function register(Request $request)
    {
        $data = $request->validate(['name' => 'required|string|max:40', 'email' => 'required|email|max:255|unique:users',
            'password' => ['required', 'confirmed', Password::min(8)], 'birthday' => ['required', 'date', 'before_or_equal:'.now()->subYears(18)->format('Y-m-d'), 'after:1926-01-01'],
            'gender' => ['required', Rule::in(['female', 'male'])], 'city' => 'required|string|max:80', 'consent' => 'accepted']);
        $user = DB::transaction(function () use ($data) {
            $user = User::create(['name' => $data['name'], 'email' => $data['email'], 'password' => Hash::make($data['password'])]);
            Profile::create(['user_id' => $user->id, 'birthday' => $data['birthday'], 'gender' => $data['gender'], 'city' => $data['city'], 'interests' => [], 'photos' => []]);
            return $user;
        });
        Auth::login($user);
        $request->session()->regenerate();
        return response()->json(['ok' => true, 'csrf' => csrf_token()]);
    }

    public function login(Request $request)
    {
        $credentials = $request->validate(['email' => 'required|email', 'password' => 'required|string']);
        if (! Auth::attempt($credentials, $request->boolean('remember'))) {
            return response()->json(['message' => 'Почта или пароль не совпали. Попробуйте ещё раз.'], 422);
        }
        $request->session()->regenerate();
        return response()->json(['ok' => true, 'csrf' => csrf_token()]);
    }

    public function demo(Request $request)
    {
        abort_unless(app()->environment('local', 'testing'), 404);
        $user = DB::transaction(function () {
            $user = User::create(['name' => 'Алекс', 'email' => 'demo-'.bin2hex(random_bytes(12)).'@svyaz.test', 'password' => Hash::make(bin2hex(random_bytes(24)))]);
            Profile::create(['user_id' => $user->id, 'birthday' => '2001-04-12', 'gender' => 'male', 'city' => 'Москва', 'bio' => 'За спонтанные поездки, хороший кофе и разговоры обо всём.', 'interests' => ['Путешествия', 'Кофе', 'Музыка'], 'photos' => ['/images/alex-ready.jpg']]);
            foreach (Profile::where('demo', true)->limit(3)->get() as $profile) {
                DB::table('reactions')->insert(['user_id' => $profile->user_id, 'target_id' => $user->id, 'kind' => 'like', 'created_at' => now(), 'updated_at' => now()]);
            }
            return $user;
        });
        Auth::login($user);
        $request->session()->regenerate();
        return response()->json(['ok' => true, 'csrf' => csrf_token()]);
    }

    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        return response()->json(['ok' => true, 'csrf' => csrf_token()]);
    }

    public function update(Request $request)
    {
        $data = $request->validate(['name' => 'required|string|max:40', 'city' => 'required|string|max:80', 'bio' => 'nullable|string|max:1000',
            'goal' => ['required', Rule::in(['Отношения', 'Дружба', 'Общение'])], 'looking_for' => ['required', Rule::in(['all', 'male', 'female'])],
            'interests' => 'present|array|max:10', 'interests.*' => 'string|max:40', 'hidden' => 'boolean']);
        DB::transaction(function () use ($data, $request) {
            $request->user()->update(['name' => $data['name']]);
            Profile::where('user_id', $request->user()->id)->firstOrFail()->update(collect($data)->except('name')->all());
        });
        return response()->json(['ok' => true, 'csrf' => csrf_token()]);
    }

    public function photo(Request $request)
    {
        $request->validate(['photo' => 'required|image|mimes:jpg,jpeg,png,webp|max:5120']);
        $profile = Profile::where('user_id', $request->user()->id)->firstOrFail();
        abort_if(count($profile->photos) >= 6, 422, 'Можно добавить до 6 фото.');
        $path = $request->file('photo')->store('profiles', 'public');
        $profile->update(['photos' => [...$profile->photos, Storage::url($path)]]);
        return response()->json(['ok' => true, 'csrf' => csrf_token()]);
    }

    public function deletePhoto(Request $request)
    {
        $data = $request->validate(['index' => 'required|integer|min:0|max:5']);
        $profile = Profile::where('user_id', $request->user()->id)->firstOrFail();
        $photos = $profile->photos;
        abort_unless(isset($photos[$data['index']]), 404);
        $removed = $photos[$data['index']];
        array_splice($photos, $data['index'], 1);
        $profile->update(['photos' => $photos]);
        if (str_starts_with($removed, '/storage/profiles/')) Storage::disk('public')->delete(substr($removed, 9));
        return response()->json(['ok' => true, 'csrf' => csrf_token()]);
    }

    public function react(Request $request, User $target)
    {
        $data = $request->validate(['kind' => ['required', Rule::in(['like', 'skip'])]]);
        $id = $request->user()->id;
        abort_if($id === $target->id || in_array($target->id, $this->excluded($id)), 403);
        abort_unless(Profile::where('user_id', $target->id)->where('hidden', false)->exists(), 404);
        $connection = DB::transaction(function () use ($id, $target, $data) {
            DB::table('reactions')->updateOrInsert(['user_id' => $id, 'target_id' => $target->id], ['kind' => $data['kind'], 'created_at' => now(), 'updated_at' => now()]);
            if ($data['kind'] !== 'like' || ! DB::table('reactions')->where(['user_id' => $target->id, 'target_id' => $id, 'kind' => 'like'])->exists()) return null;
            $pair = ['first_id' => min($id, $target->id), 'second_id' => max($id, $target->id)];
            DB::table('connections')->insertOrIgnore([...$pair, 'created_at' => now(), 'updated_at' => now()]);
            return DB::table('connections')->where($pair)->value('id');
        });
        return response()->json(['connection' => $connection]);
    }

    public function undo(Request $request)
    {
        $last = DB::table('reactions')->where('user_id', $request->user()->id)->where('kind', 'skip')->latest('updated_at')->first();
        if ($last) DB::table('reactions')->where('id', $last->id)->delete();
        return response()->json(['ok' => true, 'csrf' => csrf_token()]);
    }

    private function connection(Request $request, int $id)
    {
        $connection = DB::table('connections')->where('id', $id)->where(fn ($q) => $q->where('first_id', $request->user()->id)->orWhere('second_id', $request->user()->id))->first();
        abort_unless($connection, 404);
        $other = $connection->first_id == $request->user()->id ? $connection->second_id : $connection->first_id;
        abort_if(in_array($other, $this->excluded($request->user()->id)), 403);
        return $connection;
    }

    public function messages(Request $request, int $id)
    {
        $this->connection($request, $id);
        return response()->json(DB::table('messages')->where('connection_id', $id)->orderByDesc('id')->limit(200)->get()->reverse()->values());
    }

    public function send(Request $request, int $id)
    {
        $this->connection($request, $id);
        $data = $request->validate(['body' => 'required|string|max:2000']);
        abort_if(trim($data['body']) === '', 422);
        DB::table('messages')->insert(['connection_id' => $id, 'sender_id' => $request->user()->id, 'body' => trim($data['body']), 'created_at' => now(), 'updated_at' => now()]);
        return response()->json(['ok' => true, 'csrf' => csrf_token()]);
    }

    public function block(Request $request, User $target)
    {
        abort_if($target->id === $request->user()->id, 422);
        DB::table('blocks')->insertOrIgnore(['user_id' => $request->user()->id, 'target_id' => $target->id]);
        return response()->json(['ok' => true, 'csrf' => csrf_token()]);
    }

    public function report(Request $request, User $target)
    {
        abort_if($target->id === $request->user()->id, 422);
        $data = $request->validate(['reason' => 'required|string|max:1000']);
        DB::table('reports')->insert(['user_id' => $request->user()->id, 'target_id' => $target->id, 'reason' => $data['reason'], 'created_at' => now(), 'updated_at' => now()]);
        return $this->block($request, $target);
    }

    public function destroy(Request $request)
    {
        $request->validate(['password' => 'required|current_password']);
        $profile = Profile::where('user_id', $request->user()->id)->first();
        foreach ($profile?->photos ?? [] as $photo) {
            if (str_starts_with($photo, '/storage/profiles/')) Storage::disk('public')->delete(substr($photo, 9));
        }
        $user = $request->user();
        $response = $this->logout($request);
        $user->delete();
        return $response;
    }
}

