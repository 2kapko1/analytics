<?php

namespace Tests\Feature;

use Tests\TestCase;

class RegistrationDisabledTest extends TestCase
{
    public function test_register_page_is_not_available(): void
    {
        $this->get('/register')->assertNotFound();
    }

    public function test_register_post_is_not_available(): void
    {
        $this->post('/register', [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
        ])->assertNotFound();
    }
}
