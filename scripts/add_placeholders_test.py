#!/usr/bin/env python3
"""
ONE-OFF TEST: Adicionar placeholders descritivos aos inputs de show.blade.php
Fácil reversão: restaurar de show_backup_placeholders.blade.php
"""

import os
import shutil
from datetime import datetime

SHOW_FILE = 'resources/views/patrimonios/show.blade.php'
BACKUP_FILE = 'resources/views/patrimonios/show_backup_placeholders.blade.php'

# Mapping de ID do input para placeholder
PLACEHOLDERS = {
    'NUPATRIMONIO': 'Nº Patrimônio',
    'DEPATRIMONIO': 'Descrição',
    'CDPROJETO': 'Projeto',
    'CDLOCAL': 'Local',
    'CDMATRFUNCIONARIO': 'Matrícula',
    'NOMEUSER': 'Responsável',
    'DTAQUISICAO': 'Data Aquisição',
    'DTOPERACAO': 'Data Operação',
    'SITUACAO': 'Situação',
    'USUARIO': 'Criado por',
}

# Criar backup ANTES
if not os.path.exists(BACKUP_FILE):
    shutil.copy(SHOW_FILE, BACKUP_FILE)
    print(f"✅ Backup criado: {BACKUP_FILE}")
else:
    print(f"⚠️  Backup já existe: {BACKUP_FILE}")

# Ler arquivo
with open(SHOW_FILE, 'r', encoding='utf-8') as f:
    content = f.read()

# Adicionar placeholders
for input_id, placeholder in PLACEHOLDERS.items():
    # Procurar pattern: id="XXXXX" value=... readonly
    old_pattern = f'id="{input_id}" value='
    new_pattern = f'id="{input_id}" value= placeholder="{placeholder}"'
    
    if old_pattern in content:
        content = content.replace(
            f'<input type="text" id="{input_id}" value=',
            f'<input type="text" id="{input_id}" placeholder="{placeholder}" value='
        )
        print(f"✅ Placeholder adicionado: {input_id} = '{placeholder}'")

# Salvar
with open(SHOW_FILE, 'w', encoding='utf-8') as f:
    f.write(content)

print(f"\n✅ Teste aplicado com sucesso!")
print(f"Para reverter: copiar {BACKUP_FILE} de volta para {SHOW_FILE}")
