<?php
// app/Livewire/Perfil.php

namespace App\Livewire;

use App\Models\Profile;
use App\Models\Review;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;
use Livewire\WithFileUploads;

class Perfil extends Component
{
    use WithFileUploads;

    public string $tab = 'info';
    public bool $saving = false;

    // Campos do formulário
    public string $nome = '';
    public string $cpf  = '';
    public string $dataNascimento = '';
    public string $telefone = '';
    public string $enderecoRua = '';
    public string $enderecoNumero = '';
    public string $enderecoBairro = '';
    public string $cidade = '';
    public string $bio = '';
    public bool $possuiBag = false;

    public function mount(): void
    {
        $profile = Profile::find(Auth::id());
        if ($profile) {
            $this->nome           = $profile->nome ?? '';
            $this->cpf            = $profile->cpf ? $this->formatarCPF($profile->cpf) : '';
            $this->dataNascimento = $profile->data_nascimento
                ? \Carbon\Carbon::parse($profile->data_nascimento)->format('d/m/Y')
                : '';
            $this->telefone       = $profile->telefone ?? '';
            $this->enderecoRua    = $profile->endereco_rua ?? '';
            $this->enderecoNumero = $profile->endereco_numero ?? '';
            $this->enderecoBairro = $profile->endereco_bairro ?? '';
            $this->cidade         = $profile->cidade ?? '';
            $this->bio            = $profile->bio ?? '';
            $this->possuiBag      = (bool)$profile->possui_bag;
        }
    }

    private function formatarCPF(string $cpf): string
    {
        $cpf = preg_replace('/\D/', '', $cpf);
        if (strlen($cpf) === 11) {
            return preg_replace('/(\d{3})(\d{3})(\d{3})(\d{2})/', '$1.$2.$3-$4', $cpf);
        }
        return $cpf;
    }

    public function salvar(): void
    {
        $this->validate([
            'nome'   => 'required|min:2',
            'cidade' => 'nullable|string',
        ]);

        $this->saving = true;

        // Converte data BR → ISO
        $nascISO = null;
        if (preg_match('/^(\d{2})\/(\d{2})\/(\d{4})$/', $this->dataNascimento, $m)) {
            $nascISO = "{$m[3]}-{$m[2]}-{$m[1]}";
        }

        Profile::updateOrCreate(
            ['id' => Auth::id()],
            [
                'nome'             => trim($this->nome),
                'cpf'              => preg_replace('/\D/', '', $this->cpf),
                'data_nascimento'  => $nascISO,
                'telefone'         => $this->telefone,
                'endereco_rua'     => trim($this->enderecoRua),
                'endereco_numero'  => trim($this->enderecoNumero),
                'endereco_bairro'  => trim($this->enderecoBairro),
                'cidade'           => trim($this->cidade),
                'bio'              => trim($this->bio),
                'possui_bag'       => $this->possuiBag,
            ]
        );

        // Atualiza também o name na tabela users
        Auth::user()->update(['name' => trim($this->nome)]);

        $this->saving = false;
        session()->flash('success', 'Perfil salvo com sucesso!');
    }

    public function logout(): void
    {
        Auth::logout();
        session()->invalidate();
        session()->regenerateToken();
        $this->redirect(route('login'), navigate: true);
    }

    public function render()
    {
        $profile = Profile::find(Auth::id());
        $reviews = Review::where('alvo_id', Auth::id())
            ->orderByDesc('created_at')
            ->limit(20)
            ->get();

        return view('livewire.perfil', compact('profile','reviews'))
            ->layout('layouts.app')
            ->title('Perfil — MotoReserva');
    }
}
