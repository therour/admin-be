<?php

namespace Tests\Feature\Api;

use App\Models\User;
use Tests\ApiTestCase;
use Database\Factories\UserFactory;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Testing\Fluent\AssertableJson;
use Illuminate\Foundation\Testing\RefreshDatabase;

class AuthTest extends ApiTestCase
{
    use RefreshDatabase;
    
    /**
     * A basic feature test example.
     *
     * @return void
     */
    public function test_login()
    {
        $user = User::factory()->create();
        
        $response = $this->postJson('/api/v1/auth/login', [
            'email' => $user->email,
            'password' => UserFactory::DEFAULT_PASSWORD,
        ]);
        
        $response->assertJson(fn (AssertableJson $assert) => 
            $assert->has('token')
                ->where('data.name', $user->name)
                ->where('data.email', $user->email)
        );

        $response->assertStatus(200);
    }
    
    public function test_get_me()
    {
        $user = User::factory()->create();
        $token = $user->createToken('custom-token')->plainTextToken;
        
        $response = $this->getJson('/api/v1/auth/me', [
            'Authorization' => 'Bearer '. $token,
        ]);

        $response->assertJson(fn (AssertableJson $assert) => 
            $assert
                ->where('data.name', $user->name)
                ->where('data.email', $user->email)
        );

        $response->assertStatus(200);
    }
}
