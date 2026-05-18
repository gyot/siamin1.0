<?php

namespace Tests\Feature;

use App\Models\Pegawai;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_me_endpoint_accepts_bearer_token_without_stateful_session(): void
    {
        $pegawai = Pegawai::create([
            'nama' => 'Wahyu Ramdhani',
            'nip' => '1987654321001',
        ]);

        $user = new User();
        $user->forceFill([
            'id_pegawai' => $pegawai->id_pegawai,
            'email' => 'wahyu@example.test',
            'user' => 'wahyu.ramdhani',
            'password' => 'secret123',
            'role' => 'operator',
            'status' => 'aktif',
        ]);
        $user->save();

        $user->refresh();

        $token = $user->createToken('test-token')->plainTextToken;

        $response = $this
            ->withHeader('Authorization', 'Bearer '.$token)
            ->withHeader('Accept', 'application/json')
            ->getJson('/api/v1/me');

        $response
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.user.email', 'wahyu@example.test')
            ->assertJsonPath('data.pegawai.id_pegawai', $pegawai->id_pegawai)
            ->assertJsonPath('data.pegawai.nama', 'Wahyu Ramdhani');
    }
}
