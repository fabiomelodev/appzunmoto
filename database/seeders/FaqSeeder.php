<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class FaqSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::table('faqs')->insert([
            [
                'name' => 'Como aceito uma vaga?',
                'description' => 'Toque no card da vaga, confira os detalhes e use o botão "Aceitar Vaga". Ele só fica ativo se seu veículo selecionado for compatível.',
                'status' => 'active',
                'created_at' => now(),
                'updated_at' => now(),
            ],

            [
                'name' => 'Como solicito a cobertura do meu turno?',
                'description' => 'Use o botão " + " na barra inferior e escolha "Sou um Motoboy" para abrir o formulário de substituição de turno.',
                'status' => 'active',
                'created_at' => now(),
                'updated_at' => now(),
            ],

            [
                'name' => 'Onde mudo meu veículo ativo?',
                'description' => 'No Menu, abra "Veículo e Documentação" e escolha entre Moto, Moto Elétrica / Bicicleta Motorizada ou Bicicleta Convencional.',
                'status' => 'active',
                'created_at' => now(),
                'updated_at' => now(),
            ],

            [
                'name' => 'Meus documentos estão seguros?',
                'description' => 'Sim. Os arquivos enviados na seção "Documentação Profissional" ficam armazenados de forma segura e só são utilizados para validação do seu cadastro.',
                'status' => 'active',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }
}
