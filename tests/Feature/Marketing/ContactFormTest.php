<?php

namespace Tests\Feature\Marketing;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;

class ContactFormTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test that contact form can be submitted with valid data.
     */
    public function test_contact_form_submission_with_valid_data(): void
    {
        $response = $this->post('/contact', [
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'company' => 'Test Company',
            'message' => 'This is a test message.',
        ]);

        $response->assertRedirect(route('marketing.contact'));
        $response->assertSessionHas('success');
    }

    /**
     * Test that contact form validates required fields.
     */
    public function test_contact_form_validates_required_fields(): void
    {
        $response = $this->post('/contact', []);

        $response->assertSessionHasErrors(['name', 'email', 'message']);
    }

    /**
     * Test that contact form validates email format.
     */
    public function test_contact_form_validates_email_format(): void
    {
        $response = $this->post('/contact', [
            'name' => 'John Doe',
            'email' => 'invalid-email',
            'message' => 'Test message',
        ]);

        $response->assertSessionHasErrors(['email']);
    }

    /**
     * Test that contact form accepts optional company field.
     */
    public function test_contact_form_accepts_optional_company_field(): void
    {
        $response = $this->post('/contact', [
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'message' => 'Test message',
        ]);

        $response->assertRedirect(route('marketing.contact'));
        $response->assertSessionHas('success');
    }

    /**
     * Test that contact form validates message length.
     */
    public function test_contact_form_validates_message_length(): void
    {
        $longMessage = str_repeat('a', 5001); // Exceeds 5000 character limit

        $response = $this->post('/contact', [
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'message' => $longMessage,
        ]);

        $response->assertSessionHasErrors(['message']);
    }
}

