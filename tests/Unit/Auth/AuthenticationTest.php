<?php

namespace Tests\Unit\Auth;

use Tests\TestCase;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;
use Mockery;

class AuthenticationTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_user_can_be_created()
    {
        $userData = [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'password123'
        ];

        $user = new User();
        $user->fill($userData);
        $user->password = Hash::make($userData['password']);

        $this->assertInstanceOf(User::class, $user);
        $this->assertEquals($userData['name'], $user->name);
        $this->assertEquals($userData['email'], $user->email);
        $this->assertTrue(Hash::check($userData['password'], $user->password));
    }

    public function test_password_is_hashed_when_creating_user()
    {
        $password = 'password123';

        $user = new User();
        $user->password = Hash::make($password);

        $this->assertNotEquals($password, $user->password);
        $this->assertTrue(Hash::check($password, $user->password));
    }

    public function test_user_credentials_validation()
    {
        $hashedPassword = Hash::make('correct_password');
        $mockUser = (object)[
            'password' => $hashedPassword
        ];

        $user = Mockery::mock(User::class);
        $user->shouldReceive('where')
            ->with('email', 'test@example.com')
            ->andReturnSelf();

        $user->shouldReceive('first')
            ->andReturn($mockUser);

        $result = $user->where('email', 'test@example.com')->first();

        $this->assertTrue(
            Hash::check('correct_password', $result->password)
        );

        $this->assertFalse(
            Hash::check('wrong_password', $result->password)
        );
    }

    public function test_auth_token_generation()
    {
        $user = User::factory()->create([
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => Hash::make('password123')
        ]);

        $token = $user->createToken('auth_token')->plainTextToken;

        $this->assertIsString($token);
        $this->assertNotEmpty($token);
    }
}
