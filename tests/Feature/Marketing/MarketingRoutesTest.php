<?php

namespace Tests\Feature\Marketing;

use Tests\TestCase;

class MarketingRoutesTest extends TestCase
{
    /**
     * Test that marketing homepage is accessible.
     */
    public function test_home_page_is_accessible(): void
    {
        $response = $this->get('/');
        $response->assertStatus(200);
        $response->assertViewIs('marketing.home');
    }

    /**
     * Test that features page is accessible.
     */
    public function test_features_page_is_accessible(): void
    {
        $response = $this->get('/features');
        $response->assertStatus(200);
        $response->assertViewIs('marketing.features');
    }

    /**
     * Test that pricing page is accessible.
     */
    public function test_pricing_page_is_accessible(): void
    {
        $response = $this->get('/pricing');
        $response->assertStatus(200);
        $response->assertViewIs('marketing.pricing');
    }

    /**
     * Test that solutions page is accessible.
     */
    public function test_solutions_page_is_accessible(): void
    {
        $response = $this->get('/solutions');
        $response->assertStatus(200);
        $response->assertViewIs('marketing.solutions');
    }

    /**
     * Test that about page is accessible.
     */
    public function test_about_page_is_accessible(): void
    {
        $response = $this->get('/about');
        $response->assertStatus(200);
        $response->assertViewIs('marketing.about');
    }

    /**
     * Test that contact page is accessible.
     */
    public function test_contact_page_is_accessible(): void
    {
        $response = $this->get('/contact');
        $response->assertStatus(200);
        $response->assertViewIs('marketing.contact');
    }

    /**
     * Test that FAQ page is accessible.
     */
    public function test_faq_page_is_accessible(): void
    {
        $response = $this->get('/faq');
        $response->assertStatus(200);
        $response->assertViewIs('marketing.faq');
    }

    /**
     * Test that privacy page is accessible.
     */
    public function test_privacy_page_is_accessible(): void
    {
        $response = $this->get('/legal/privacy');
        $response->assertStatus(200);
        $response->assertViewIs('marketing.legal.privacy');
    }
}

