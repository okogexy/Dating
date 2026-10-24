<?php

namespace Tests\Feature;

use App\Models\Profile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class DatingTest extends TestCase
{
    use RefreshDatabase;

    private function person(string $name = 'Тест', bool $demo = false): User
    {
        $user = User::factory()->create(['name' => $name]);
        Profile::create(['user_id' => $user->id, 'birthday' => '2000-01-01', 'gender' => 'female', 'city' => 'Москва', 'interests' => ['Кофе'], 'photos' => [], 'demo' => $demo]);
        return $user;
    }

    public function test_registration_creates_private_account_and_rejects_minors(): void
    {
        $data = ['name' => 'Тим', 'email' => 'tim@example.test', 'password' => 'password123', 'password_confirmation' => 'password123', 'birthday' => now()->subYears(17)->toDateString(), 'gender' => 'male', 'city' => 'Москва', 'consent' => true];
        $this->postJson('/api/register', $data)->assertUnprocessable()->assertJsonValidationErrors('birthday');
        $data['birthday'] = '2000-01-01';
        $this->postJson('/api/register', $data)->assertOk()->assertJsonStructure(['csrf']);
        $this->assertAuthenticated();
        $this->assertDatabaseHas('profiles', ['city' => 'Москва']);
        $this->getJson('/api/state')->assertOk()->assertJsonPath('me.name', 'Тим')->assertJsonMissingPath('me.email')->assertJsonMissingPath('me.birthday');
    }

    public function test_only_mutual_likes_open_chat_and_outsiders_cannot_read_it(): void
    {
        $alice = $this->person('Алиса'); $bob = $this->person('Боб'); $outsider = $this->person('Посторонний');
        $this->actingAs($alice)->postJson('/api/reactions/'.$bob->id, ['kind' => 'like'])->assertOk()->assertJsonPath('connection', null);
        $response = $this->actingAs($bob)->postJson('/api/reactions/'.$alice->id, ['kind' => 'like'])->assertOk();
        $id = $response->json('connection');
        $this->assertNotNull($id);
        $this->getJson('/api/state')->assertJsonCount(0, 'likes')->assertJsonCount(1, 'connections');
        $this->postJson('/api/chats/'.$id, ['body' => 'Привет!'])->assertOk();
        $this->actingAs($alice)->getJson('/api/chats/'.$id)->assertOk()->assertJsonPath('0.body', 'Привет!');
        $this->actingAs($outsider)->getJson('/api/chats/'.$id)->assertNotFound();
        $this->postJson('/api/chats/'.$id, ['body' => 'Взлом'])->assertNotFound();
        $this->assertDatabaseCount('messages', 1);
    }

    public function test_blocks_hide_both_users_and_prevent_chat(): void
    {
        $alice = $this->person(); $bob = $this->person();
        $this->actingAs($alice)->postJson('/api/reactions/'.$bob->id, ['kind' => 'like']);
        $id = $this->actingAs($bob)->postJson('/api/reactions/'.$alice->id, ['kind' => 'like'])->json('connection');
        $this->actingAs($alice)->postJson('/api/block/'.$bob->id)->assertOk();
        $this->actingAs($bob)->getJson('/api/state')->assertOk()->assertJsonCount(0, 'profiles')->assertJsonCount(0, 'connections');
        $this->postJson('/api/chats/'.$id, ['body' => 'Привет'])->assertForbidden();
        $this->postJson('/api/reactions/'.$alice->id, ['kind' => 'like'])->assertForbidden();
    }

    public function test_guest_sees_only_demo_profiles_and_filters_apply(): void
    {
        $this->person('Скрытый пользователь'); $demo = $this->person('Демо', true);
        $this->getJson('/api/state')->assertOk()->assertJsonCount(1, 'profiles')->assertJsonPath('profiles.0.name', 'Демо');
        $this->getJson('/api/state?gender=male')->assertJsonCount(0, 'profiles');
        $this->getJson('/api/state?city=Казань')->assertJsonCount(0, 'profiles');
        Profile::where('user_id', $demo->id)->update(['hidden' => true]);
        $this->getJson('/api/state')->assertJsonCount(0, 'profiles');
    }

    public function test_skip_undo_and_profile_update_persist(): void
    {
        $alice = $this->person(); $bob = $this->person('Боб');
        $this->actingAs($alice)->postJson('/api/reactions/'.$bob->id, ['kind' => 'skip'])->assertOk();
        $this->getJson('/api/state')->assertJsonCount(0, 'profiles');
        $this->postJson('/api/undo')->assertOk();
        $this->getJson('/api/state')->assertJsonCount(1, 'profiles');
        $this->putJson('/api/profile', ['name' => 'Новое имя', 'city' => 'Казань', 'bio' => 'Люблю жизнь', 'goal' => 'Дружба', 'looking_for' => 'all', 'interests' => ['Книги'], 'hidden' => false])->assertOk();
        $this->getJson('/api/state')->assertJsonPath('me.name', 'Новое имя')->assertJsonPath('me.city', 'Казань')->assertJsonPath('me.interests.0', 'Книги');
    }

    public function test_upload_validation_and_account_deletion(): void
    {
        Storage::fake('public');
        $alice = $this->person();
        $this->actingAs($alice)->postJson('/api/photos', ['photo' => UploadedFile::fake()->create('script.php', 1, 'text/plain')])->assertUnprocessable();
        $this->postJson('/api/photos', ['photo' => UploadedFile::fake()->createWithContent('portrait.png', base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAwMCAO+jRZkAAAAASUVORK5CYII='))])->assertOk();
        $this->assertCount(1, Profile::where('user_id', $alice->id)->first()->photos);
        $this->deleteJson('/api/account', ['password' => 'wrong'])->assertUnprocessable();
        $this->deleteJson('/api/account', ['password' => 'password'])->assertOk();
        $this->assertDatabaseMissing('users', ['id' => $alice->id]);
        $this->assertDatabaseMissing('profiles', ['user_id' => $alice->id]);
        $this->assertGuest();
        $this->assertSame([], Storage::disk('public')->allFiles('profiles'));
    }

    public function test_demo_sessions_are_isolated(): void
    {
        $this->person('Демо', true);
        $this->postJson('/api/demo')->assertOk();
        $first = auth()->id();
        $this->postJson('/api/logout')->assertOk();
        $this->postJson('/api/demo')->assertOk();
        $this->assertNotSame($first, auth()->id());
    }

    public function test_login_and_authenticated_endpoints(): void
    {
        $user = $this->person();
        $this->putJson('/api/profile', [])->assertUnauthorized();
        $this->postJson('/api/login', ['email' => $user->email, 'password' => 'incorrect'])->assertUnprocessable();
        $this->postJson('/api/login', ['email' => $user->email, 'password' => 'password'])->assertOk()->assertJsonStructure(['csrf']);
        $this->assertAuthenticatedAs($user);
    }

    public function test_home_is_public_and_cabinet_requires_authentication(): void
    {
        $this->get('/')->assertOk()->assertSee('Связь — найди свой вайб');
        $this->get('/cabinet')->assertRedirect(route('login'));
        $this->actingAs($this->person())->get('/cabinet')->assertOk()->assertSee('Личный кабинет — Связь');
        $this->get('/')->assertOk()->assertSee('Связь — найди свой вайб');
    }

    public function test_reporting_persists_reason_and_blocks_target(): void
    {
        $alice = $this->person(); $bob = $this->person();
        $this->actingAs($alice)->postJson('/api/report/'.$bob->id, ['reason' => 'Спам или реклама'])->assertOk();
        $this->assertDatabaseHas('reports', ['user_id' => $alice->id, 'target_id' => $bob->id, 'reason' => 'Спам или реклама']);
        $this->assertDatabaseHas('blocks', ['user_id' => $alice->id, 'target_id' => $bob->id]);
        $this->getJson('/api/state')->assertJsonCount(0, 'profiles');
        $this->postJson('/api/report/'.$alice->id, ['reason' => 'Спам'])->assertUnprocessable();
    }
}
