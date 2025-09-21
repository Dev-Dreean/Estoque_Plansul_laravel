<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\User;

class TestSetTheme extends Command
{
    protected $signature = 'theme:test {theme=dark}';
    protected $description = 'Testa atualização do campo theme no primeiro usuário';

    public function handle(): int
    {
        $user = User::query()->first();
        if (!$user) {
            $this->error('Nenhum usuário encontrado.');
            return self::FAILURE;
        }
        $theme = $this->argument('theme');
        $user->theme = $theme;
        $user->save();
        $this->info('Theme salvo: ' . $user->theme);
        return self::SUCCESS;
    }
}
