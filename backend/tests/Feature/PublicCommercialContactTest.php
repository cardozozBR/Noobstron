<?php

namespace Tests\Feature;

use App\Models\CommercialContact;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class PublicCommercialContactTest extends TestCase
{
    use RefreshDatabase;

    public function test_public_contact_form_persists_message(): void
    {
        Mail::fake();

        $this->post('http://localhost/contact', [
            'name' => 'Contato Comercial',
            'email' => 'contato@example.test',
            'company' => 'Empresa Teste',
            'message' => 'Quero conhecer a plataforma.',
        ])->assertRedirect('http://localhost/#contato');

        $this->assertDatabaseHas('commercial_contacts', [
            'email' => 'contato@example.test',
            'company' => 'Empresa Teste',
            'status' => 'new',
        ]);
    }

    public function test_public_contact_form_validates_required_fields(): void
    {
        $this->from('http://localhost/#contato')
            ->post('http://localhost/contact', [])
            ->assertSessionHasErrors([
                'name',
                'email',
                'message',
            ]);

        $this->assertSame(0, CommercialContact::query()->count());
    }
}
