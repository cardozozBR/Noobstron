<?php

namespace Database\Seeders;

use App\Models\Permission;
use Illuminate\Database\Seeder;

class PermissionSeeder extends Seeder
{
    public function run(): void
    {
        $permissions = [
            ['name' => 'users.view', 'label' => 'Visualizar usuários'],
            ['name' => 'users.create', 'label' => 'Criar usuários'],
            ['name' => 'users.update', 'label' => 'Atualizar usuários'],
            ['name' => 'users.delete', 'label' => 'Excluir usuários'],
            ['name' => 'users.permissions', 'label' => 'Gerenciar permissões'],

            ['name' => 'leads.view', 'label' => 'Visualizar leads'],
            ['name' => 'leads.create', 'label' => 'Criar leads'],
            ['name' => 'leads.update', 'label' => 'Atualizar leads'],
            ['name' => 'leads.delete', 'label' => 'Excluir leads'],

            ['name' => 'customers.view', 'label' => 'Visualizar clientes'],
            ['name' => 'customers.create', 'label' => 'Criar clientes'],
            ['name' => 'customers.update', 'label' => 'Atualizar clientes'],
            ['name' => 'customers.delete', 'label' => 'Excluir clientes'],

            ['name' => 'pipelines.view', 'label' => 'Visualizar pipelines'],
            ['name' => 'pipelines.create', 'label' => 'Criar pipelines'],
            ['name' => 'pipelines.update', 'label' => 'Atualizar pipelines'],
            ['name' => 'pipelines.delete', 'label' => 'Excluir pipelines'],

            ['name' => 'opportunities.view', 'label' => 'Visualizar oportunidades'],
            ['name' => 'opportunities.create', 'label' => 'Criar oportunidades'],
            ['name' => 'opportunities.update', 'label' => 'Atualizar oportunidades'],
            ['name' => 'opportunities.delete', 'label' => 'Excluir oportunidades'],

            ['name' => 'activities.view', 'label' => 'Visualizar atividades'],
            ['name' => 'activities.create', 'label' => 'Criar atividades'],
            ['name' => 'activities.update', 'label' => 'Atualizar atividades'],
            ['name' => 'activities.delete', 'label' => 'Excluir atividades'],

            ['name' => 'catalog.view', 'label' => 'Visualizar catálogo'],
            ['name' => 'catalog.create', 'label' => 'Criar itens do catálogo'],
            ['name' => 'catalog.update', 'label' => 'Atualizar itens do catálogo'],
            ['name' => 'catalog.delete', 'label' => 'Excluir itens do catálogo'],

            ['name' => 'proposals.view', 'label' => 'Visualizar propostas'],
            ['name' => 'proposals.create', 'label' => 'Criar propostas'],
            ['name' => 'proposals.update', 'label' => 'Atualizar propostas'],
            ['name' => 'proposals.delete', 'label' => 'Excluir propostas'],

            ['name' => 'sales.view', 'label' => 'Visualizar vendas'],
            ['name' => 'sales.create', 'label' => 'Criar vendas'],
            ['name' => 'sales.update', 'label' => 'Atualizar vendas'],
            ['name' => 'sales.delete', 'label' => 'Excluir vendas'],

            ['name' => 'receivables.view', 'label' => 'Visualizar contas a receber'],
            ['name' => 'receivables.create', 'label' => 'Criar contas a receber'],
            ['name' => 'receivables.update', 'label' => 'Atualizar contas a receber'],
            ['name' => 'receivables.delete', 'label' => 'Excluir contas a receber'],

            ['name' => 'charges.view', 'label' => 'Visualizar cobranças'],
            ['name' => 'charges.create', 'label' => 'Criar cobranças'],
            ['name' => 'charges.update', 'label' => 'Atualizar cobranças'],
            ['name' => 'charges.delete', 'label' => 'Excluir cobranças'],

            ['name' => 'financial_indicators.view', 'label' => 'Visualizar indicadores financeiros'],
            ['name' => 'email.view', 'label' => 'Visualizar e-mails'],
            ['name' => 'email.create', 'label' => 'Criar e-mails'],
            ['name' => 'email.send', 'label' => 'Enviar e-mails'],
            ['name' => 'email.templates', 'label' => 'Gerenciar templates de e-mail'],

            ['name' => 'whatsapp.view', 'label' => 'Visualizar WhatsApp'],
            ['name' => 'whatsapp.create', 'label' => 'Criar mensagens de WhatsApp'],
            ['name' => 'whatsapp.send', 'label' => 'Enviar mensagens de WhatsApp'],
            ['name' => 'whatsapp.templates', 'label' => 'Gerenciar templates de WhatsApp'],

            ['name' => 'inbox.view', 'label' => 'Visualizar caixa de entrada'],
            ['name' => 'inbox.assign', 'label' => 'Atribuir conversas'],
            ['name' => 'inbox.manage', 'label' => 'Gerenciar conversas'],
            ['name' => 'imports.view', 'label' => 'Visualizar importações'],
            ['name' => 'imports.create', 'label' => 'Criar importações'],

            ['name' => 'audit.view', 'label' => 'Visualizar auditoria'],
            ['name' => 'settings.update', 'label' => 'Atualizar configurações'],
            ['name' => 'ai.use', 'label' => 'Usar recursos de IA'],

            ['name' => 'profile.view', 'label' => 'Visualizar perfil'],
            ['name' => 'profile.update', 'label' => 'Atualizar perfil'],
        ];

        foreach ($permissions as $permission) {
            Permission::updateOrCreate(
                ['name' => $permission['name']],
                ['label' => $permission['label']],
            );
        }
    }
}
