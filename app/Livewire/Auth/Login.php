<?php
// app/Livewire/Auth/Login.php

namespace App\Livewire\Auth;

use App\Models\User;
use App\Models\Profile;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Livewire\Component;

class Login extends Component
{
    // Modo: 'signin' ou 'signup'
    public string $mode = 'signin';

    // Campos comuns
    public string $email = '';
    public string $senha = '';
    public bool $showSenha = false;

    // Campos só do cadastro
    public string $nome = '';
    public string $cpf = '';
    public string $nasc = '';
    public string $tel = '';
    public string $rua = '';
    public string $numero = '';
    public string $bairro = '';
    public string $cidade = '';
    public string $senha2 = '';
    public bool $showSenha2 = false;

    public string $erro = '';
    public bool $busy = false;

    // Regras de validação dinâmicas
    protected function rules(): array
    {
        if ($this->mode === 'signin') {
            return [
                'email' => 'required|email',
                'senha' => 'required|min:6',
            ];
        }

        return [
            'nome' => 'required|min:2',
            'cpf' => 'required',
            'nasc' => 'required',
            'tel' => 'required',
            'rua' => 'required|min:2',
            'numero' => 'required',
            'bairro' => 'required|min:2',
            'cidade' => 'required|min:2',
            'email' => 'required|email|unique:users,email',
            'senha' => 'required|min:6|same:senha2',
            'senha2' => 'required|min:6',
        ];
    }

    public function setMode(string $mode): void
    {
        $this->mode = $mode;
        $this->erro = '';
        $this->reset(['email', 'senha', 'senha2', 'nome', 'cpf', 'nasc', 'tel', 'rua', 'numero', 'bairro', 'cidade']);
    }

    public function submit(): void
    {
        $this->erro = '';
        $this->validate();

        $this->busy = true;

        if ($this->mode === 'signin') {
            $this->entrar();
        } else {
            $this->cadastrar();
        }

        $this->busy = false;
    }

    private function entrar()
    {
        if (!Auth::attempt(['email' => $this->email, 'password' => $this->senha])) {
            $this->erro = 'E-mail ou senha incorretos.';
            return;
        }

        session()->regenerate();

        return redirect(route('vagas.index'));
    }

    private function cadastrar(): void
    {
        // Limpa CPF e telefone para salvar só dígitos
        $cpfLimpo = preg_replace('/\D/', '', $this->cpf);
        $telLimpo = preg_replace('/\D/', '', $this->tel);

        // Converte data BR → ISO
        $nascISO = null;
        if (preg_match('/^(\d{2})\/(\d{2})\/(\d{4})$/', $this->nasc, $m)) {
            $nascISO = "{$m[3]}-{$m[2]}-{$m[1]}";
        }

        $user = User::create([
            'name' => trim($this->nome),
            'email' => $this->email,
            'password' => Hash::make($this->senha),
        ]);

        // Cria o perfil vinculado
        Profile::create([
            'id' => $user->id,
            'tipo' => 'motoboy',
            'nome' => trim($this->nome),
            'cpf' => $cpfLimpo,
            'data_nascimento' => $nascISO,
            'telefone' => $telLimpo,
            'endereco_rua' => trim($this->rua),
            'endereco_numero' => trim($this->numero),
            'endereco_bairro' => trim($this->bairro),
            'cidade' => trim($this->cidade),
        ]);

        // Faz login direto após cadastro
        Auth::login($user);
        session()->regenerate();

        session()->flash('success', 'Cadastro realizado com sucesso!');
        $this->redirect(route('vagas.index'), navigate: true);
    }

    public function render()
    {
        return view('livewire.auth.login')
            ->layout('layouts.guest');
        // ->title('Entrar — MotoReserva');
    }
}
