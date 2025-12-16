<?php

namespace App\Observers;

use App\Models\LocalProjeto;
use App\Models\ObjetoPatr;
use App\Models\Patrimonio;
use App\Models\RegistroRemovido;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

class RegistroRemovidoObserver
{
    public function deleted(Model $model): void
    {
        try {
            static $tableExists = false;
            if (!$tableExists) {
                $tableExists = Schema::hasTable('registros_removidos');
            }

            if (!$tableExists) {
                return;
            }

            $user = Auth::user();
            $request = app()->bound('request') ? request() : null;
            $payload = $model->attributesToArray();

            // Evita armazenar campos sensÃ­veis (ex.: hash de senha do usuÃ¡rio)
            unset(
                $payload['SENHA'],
                $payload['senha'],
                $payload['password'],
                $payload['PASSWORD']
            );

            RegistroRemovido::create([
                'entity' => $this->entityFrom($model),
                'model_type' => $model::class,
                'model_id' => (string) $model->getKey(),
                'model_label' => $this->labelFrom($model),
                'deleted_by' => $user?->NMLOGIN,
                'deleted_by_matricula' => $user?->CDMATRFUNCIONARIO,
                'deleted_at' => now(),
                'request_path' => $request?->path(),
                'ip_address' => $request?->ip(),
                'user_agent' => $request?->userAgent(),
                'payload' => $payload,
            ]);
        } catch (\Throwable $e) {
            Log::warning('[REMOVIDOS] Falha ao registrar exclusao', [
                'model' => $model::class,
                'id' => $model->getKey(),
                'error' => $e->getMessage(),
            ]);
        }
    }

    private function entityFrom(Model $model): string
    {
        return match (true) {
            $model instanceof Patrimonio => 'patrimonios',
            $model instanceof LocalProjeto => 'locais',
            $model instanceof ObjetoPatr => 'bens',
            $model instanceof User => 'usuarios',
            default => 'outros',
        };
    }

    private function labelFrom(Model $model): ?string
    {
        if ($model instanceof Patrimonio) {
            $numero = $model->NUPATRIMONIO ?? null;
            $descricao = $model->DEPATRIMONIO ?? null;
            $label = trim(sprintf('Patrimônio %s%s', $numero ?? $model->getKey(), $descricao ? ' - ' . $descricao : ''));
            return $label !== '' ? $label : null;
        }

        if ($model instanceof LocalProjeto) {
            $codigo = $model->cdlocal ?? $model->getKey();
            $descricao = $model->delocal ?? null;
            $label = trim(sprintf('Local %s%s', $codigo, $descricao ? ' - ' . $descricao : ''));
            return $label !== '' ? $label : null;
        }

        if ($model instanceof ObjetoPatr) {
            $descricao = $model->DEOBJETO ?? null;
            $label = trim(sprintf('Bem %s%s', $model->getKey(), $descricao ? ' - ' . $descricao : ''));
            return $label !== '' ? $label : null;
        }

        if ($model instanceof User) {
            $login = $model->NMLOGIN ?? null;
            $nome = $model->NOMEUSER ?? null;
            $label = trim(sprintf('Usuário %s%s', $login ?? $model->getKey(), $nome ? ' - ' . $nome : ''));
            return $label !== '' ? $label : null;
        }

        return $model::class . ' #' . $model->getKey();
    }
}
